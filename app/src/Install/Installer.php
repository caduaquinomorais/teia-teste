<?php

declare(strict_types=1);

namespace Teia\Install;

defined('TEIA_ROOT') || exit;

use Teia\Core\Paths;
use Teia\Database\Database;
use Teia\Database\Migrator;
use Teia\Security\Keys;

/**
 * Instalação do zero:
 *  1. verifica requisitos (PHP, extensões, SQLite, diretório de dados gravável e FORA do document root);
 *  2. cria a estrutura de diretórios, a chave app.key, o banco e o arquivo install-token.txt;
 *  3. cria a primeira conta administrativa somente com o token (lido pelo administrador via FTP).
 * Após concluir, /instalar responde 404 permanentemente.
 */
final class Installer
{
    public const MIN_PHP = '8.1.0';
    public const MIN_SQLITE = '3.24.0'; // UPSERT (ON CONFLICT DO UPDATE)

    public function __construct(
        private readonly Paths $paths,
        private readonly string $documentRoot,
    ) {
    }

    /** Instalado = banco existe e tem a marca installed_at. */
    public function isInstalled(): bool
    {
        $file = $this->paths->database();
        if (!is_file($file) || filesize($file) === 0) {
            return false;
        }
        try {
            $db = new Database($file);
            if ($db->userVersion() < 1) {
                return false;
            }
            return $db->fetchValue("SELECT value FROM settings WHERE key = 'installed_at'") !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public function isPrepared(): bool
    {
        return is_file($this->paths->keyFile()) && is_file($this->paths->database()) && is_file($this->paths->installToken());
    }

    /**
     * @return array<int,array{label:string,ok:bool,detail:string,required:bool}>
     */
    public function requirements(): array
    {
        $checks = [];
        $add = static function (string $label, bool $ok, string $detail, bool $required = true) use (&$checks): void {
            $checks[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail, 'required' => $required];
        };

        $add('PHP ' . self::MIN_PHP . ' ou superior', version_compare(PHP_VERSION, self::MIN_PHP, '>='), 'Versão atual: ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION);
        foreach (['pdo_sqlite' => 'Banco de dados SQLite', 'mbstring' => 'Textos em UTF-8', 'fileinfo' => 'Validação de uploads', 'openssl' => 'Criptografia', 'gd' => 'Processamento de imagens', 'zip' => 'Backups'] as $ext => $why) {
            $add("Extensão $ext", extension_loaded($ext), $why);
        }
        $add('Extensão sodium', extension_loaded('sodium'), 'Recomendada (criptografia moderna)', false);
        $add('Suporte a WebP no GD', function_exists('imagewebp'), 'Recomendado: sem ele, as imagens enviadas ficam só em JPG/PNG', false);
        $add('Hash de senha Argon2id', defined('PASSWORD_ARGON2ID'), 'Recomendado: sem ele, usa bcrypt', false);

        $sqlite = '';
        try {
            $sqlite = Database::sqliteVersion();
        } catch (\Throwable) {
        }
        $add('SQLite ' . self::MIN_SQLITE . ' ou superior', $sqlite !== '' && version_compare($sqlite, self::MIN_SQLITE, '>='), $sqlite !== '' ? 'Versão: ' . $sqlite : 'Não disponível');

        $data = $this->paths->data;
        $inside = $this->dataInsideDocumentRoot();
        $add(
            'Diretório de dados fora da área pública',
            !$inside,
            $inside
                ? 'O diretório de dados está dentro da pasta pública do site. Defina outro caminho (veja docs/INSTALACAO.md).'
                : 'Local: ' . $this->displayPath($data)
        );
        $add('Diretório de dados gravável', $this->writeTest(), 'Teste real de criação e escrita de arquivo');

        return $checks;
    }

    public function requirementsOk(): bool
    {
        foreach ($this->requirements() as $check) {
            if ($check['required'] && !$check['ok']) {
                return false;
            }
        }
        return true;
    }

    public function dataInsideDocumentRoot(): bool
    {
        if ($this->documentRoot === '') {
            return false;
        }
        return Paths::isInside($this->paths->data, $this->documentRoot)
            || Paths::isInside($this->paths->data, $this->paths->public);
    }

    /** Etapa 2: cria diretórios, chave, banco e token. Idempotente. */
    public function prepare(string $migrationsDir): void
    {
        if (!$this->requirementsOk()) {
            throw new InstallException('Os requisitos mínimos não foram atendidos.');
        }
        foreach ($this->paths->requiredDataDirs() as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
                throw new InstallException('Não foi possível criar um diretório de dados.');
            }
        }
        // Defesa extra caso o diretório de dados seja exposto por engano.
        $deny = "Require all denied\nDeny from all\n";
        @file_put_contents($this->paths->data('.htaccess'), $deny);

        if (!is_file($this->paths->keyFile())) {
            Keys::generate($this->paths->keyFile());
        }
        $db = new Database($this->paths->database());
        (new Migrator($db, $migrationsDir))->migrate();
        @chmod($this->paths->database(), 0640);

        if (!is_file($this->paths->installToken())) {
            $this->writeToken($this->paths->installToken(), bin2hex(random_bytes(16)));
        }
    }

    public function verifyToken(string $token): bool
    {
        return $this->tokenMatches($this->paths->installToken(), $token);
    }

    /** Marca a instalação como concluída e apaga o token. */
    public function finish(Database $db): void
    {
        $db->execute("INSERT INTO settings (key, value) VALUES ('installed_at', ?) ON CONFLICT (key) DO UPDATE SET value = excluded.value", [gmdate('c')]);
        @unlink($this->paths->installToken());
    }

    // ---------- Token de recuperação (/admin/recuperar) ----------

    /** Cria (ou mantém, se ainda válido) o token de recuperação. Retorna true se criou um novo. */
    public function issueRecoveryToken(int $ttl): bool
    {
        $file = $this->paths->recoveryToken();
        if (is_file($file) && time() - (int) filemtime($file) < $ttl) {
            return false;
        }
        $this->writeToken($file, bin2hex(random_bytes(16)));
        return true;
    }

    public function verifyRecoveryToken(string $token, int $ttl): bool
    {
        $file = $this->paths->recoveryToken();
        if (!is_file($file)) {
            return false;
        }
        if (time() - (int) filemtime($file) >= $ttl) {
            @unlink($file);
            return false;
        }
        return $this->tokenMatches($file, $token);
    }

    public function consumeRecoveryToken(): void
    {
        @unlink($this->paths->recoveryToken());
    }

    // ------------------------------------------------------------------

    private function tokenMatches(string $file, string $token): bool
    {
        if (!is_file($file)) {
            return false;
        }
        $expected = $this->readToken($file);
        $token = strtolower(trim($token));
        return $expected !== '' && preg_match('/^[a-f0-9]{32}$/', $token) === 1 && hash_equals($expected, $token);
    }

    private function writeToken(string $file, string $token): void
    {
        $content = "# Token de uso único do site TEIA. Copie apenas a linha abaixo.\n" . $token . "\n";
        if (file_put_contents($file, $content, LOCK_EX) === false) {
            throw new InstallException('Não foi possível gravar o arquivo de token.');
        }
        @chmod($file, 0600);
    }

    private function readToken(string $file): string
    {
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if (preg_match('/^[a-f0-9]{32}$/', $line)) {
                return $line;
            }
        }
        return '';
    }

    private function writeTest(): bool
    {
        $dir = $this->paths->data;
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            return false;
        }
        $probe = $dir . '/.teste-escrita-' . bin2hex(random_bytes(4));
        $payload = random_bytes(16);
        if (@file_put_contents($probe, $payload) !== 16) {
            return false;
        }
        $ok = @file_get_contents($probe) === $payload;
        @unlink($probe);
        return $ok;
    }

    /** Mostra o caminho sem expor a estrutura completa do servidor. */
    private function displayPath(string $path): string
    {
        $parts = preg_split('#[\\\\/]#', $path) ?: [];
        return '…/' . implode('/', array_slice(array_filter($parts), -2));
    }
}
