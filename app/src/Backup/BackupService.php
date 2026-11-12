<?php

declare(strict_types=1);

namespace Teia\Backup;

defined('TEIA_ROOT') || exit;

use PDO;
use Teia\Database\Database;
use ZipArchive;

/**
 * Backups em ZIP contendo:
 *   manifest.json         — aplicação, formato, versão do esquema, data, hashes SHA-256
 *   database/teia.sqlite  — cópia consistente do banco (VACUUM INTO)
 *   uploads/<arquivos>    — imagens e PDFs enviados pelo painel
 *
 * Restauração valida integridade, versão, nomes das entradas (proteção contra Zip Slip),
 * existência de administrador ativo e cria um backup de segurança antes de substituir os dados.
 */
final class BackupService
{
    public const APP_ID = 'teia-ade-sampa';
    public const FORMAT = 1;
    public const TYPES = ['auto', 'manual', 'seguranca', 'pre-migracao'];
    private const ENTRY_PATTERN = '#^(manifest\.json|database/teia\.sqlite|uploads/[a-f0-9]{32}\.(webp|jpg|png|pdf))$#';
    private const NAME_PATTERN = '/^backup-\d{8}-\d{6}-(auto|manual|seguranca|pre-migracao)-[a-f0-9]{6}\.zip$/';

    public function __construct(
        private ?Database $db,
        private readonly string $dbPath,
        private readonly string $uploadDir,
        private readonly string $backupDir,
        private readonly string $tmpDir,
        private readonly int $latestSchemaVersion,
    ) {
    }

    // ------------------------------------------------------------------
    // Criação
    // ------------------------------------------------------------------

    public function create(string $type = 'manual'): string
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException('Tipo de backup inválido.');
        }
        if (!class_exists(ZipArchive::class)) {
            throw new BackupException('A extensão zip do PHP não está disponível.');
        }
        $this->ensureDir($this->backupDir);
        $this->ensureDir($this->tmpDir);

        $snapshot = $this->tmpDir . '/snapshot-' . bin2hex(random_bytes(6)) . '.sqlite';
        $this->snapshotDatabase($snapshot);

        $name = sprintf('backup-%s-%s-%s.zip', date('Ymd-His'), $type, bin2hex(random_bytes(3)));
        $target = $this->backupDir . '/' . $name;
        $partial = $target . '.part';

        $zip = new ZipArchive();
        if ($zip->open($partial, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($snapshot);
            throw new BackupException('Não foi possível criar o arquivo de backup.');
        }
        $hashes = ['database/teia.sqlite' => hash_file('sha256', $snapshot)];
        $zip->addFile($snapshot, 'database/teia.sqlite');
        foreach ($this->uploadFiles() as $file) {
            $entry = 'uploads/' . basename($file);
            $hashes[$entry] = hash_file('sha256', $file);
            $zip->addFile($file, $entry);
        }
        $manifest = [
            'app' => self::APP_ID,
            'format' => self::FORMAT,
            'schema_version' => $this->conn()->userVersion(),
            'type' => $type,
            'created_at' => gmdate('c'),
            'php' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            'files' => $hashes,
        ];
        $zip->addFromString('manifest.json', (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $ok = $zip->close();
        @unlink($snapshot);
        if (!$ok || !rename($partial, $target)) {
            @unlink($partial);
            throw new BackupException('Falha ao finalizar o backup.');
        }
        @chmod($target, 0640);
        $this->setSetting('last_backup_at', (string) time());
        if ($type === 'auto') {
            $this->setSetting('last_auto_backup_at', (string) time());
        }
        return $name;
    }

    /** Cópia consistente do banco, sem travar leitores por muito tempo. */
    public function snapshotDatabase(string $target): void
    {
        @unlink($target);
        try {
            $this->conn()->pdo()->exec('VACUUM INTO ' . $this->conn()->pdo()->quote($target));
        } catch (\PDOException) {
            // SQLite < 3.27: cópia do arquivo dentro de uma transação de leitura+escrita.
            $this->conn()->transaction(function () use ($target): void {
                if (!copy($this->dbPath, $target)) {
                    throw new BackupException('Falha ao copiar o banco de dados.');
                }
            });
        }
    }

    /** Backup diário automático: executa se o último tiver mais de $intervalHours horas. */
    public function runIfDue(int $intervalHours, int $retention): ?string
    {
        $last = (int) ($this->getSetting('last_auto_backup_at') ?? 0);
        if (time() - $last < $intervalHours * 3600) {
            return null;
        }
        $name = $this->create('auto');
        $this->prune($retention);
        return $name;
    }

    /** Mantém apenas os $retention backups automáticos mais recentes. */
    public function prune(int $retention): int
    {
        $retention = max(1, $retention);
        $auto = array_values(array_filter($this->list(), static fn ($b) => $b['type'] === 'auto'));
        $removed = 0;
        foreach (array_slice($auto, $retention) as $backup) {
            if (@unlink($this->backupDir . '/' . $backup['name'])) {
                $removed++;
            }
        }
        return $removed;
    }

    /** @return array<int,array{name:string,size:int,created:int,type:string}> mais recentes primeiro */
    public function list(): array
    {
        $out = [];
        foreach (glob($this->backupDir . '/backup-*.zip') ?: [] as $file) {
            $name = basename($file);
            if (!preg_match(self::NAME_PATTERN, $name, $m)) {
                continue;
            }
            $out[] = ['name' => $name, 'size' => (int) filesize($file), 'created' => (int) filemtime($file), 'type' => $m[1]];
        }
        usort($out, static fn ($a, $b) => strcmp($b['name'], $a['name']));
        return $out;
    }

    public function path(string $name): ?string
    {
        if (!preg_match(self::NAME_PATTERN, $name)) {
            return null;
        }
        $path = $this->backupDir . '/' . $name;
        return is_file($path) ? $path : null;
    }

    public function delete(string $name): bool
    {
        $path = $this->path($name);
        return $path !== null && unlink($path);
    }

    // ------------------------------------------------------------------
    // Validação e restauração
    // ------------------------------------------------------------------

    /**
     * Valida um ZIP de backup. Extrai o banco para um arquivo temporário e o verifica.
     * @return array{manifest:array,tmpDb:string}
     * @throws BackupException
     */
    public function validate(string $zipPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::RDONLY) !== true) {
            throw new BackupException('Arquivo de backup inválido ou corrompido.');
        }
        try {
            $names = [];
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = (string) ($stat['name'] ?? '');
                // Zip Slip: só nomes exatamente no formato esperado (sem "..", sem caminho absoluto, sem "\").
                if (!preg_match(self::ENTRY_PATTERN, $name)) {
                    throw new BackupException('O backup contém um arquivo não permitido: ' . mb_substr($name, 0, 80));
                }
                if (isset($names[$name])) {
                    throw new BackupException('O backup contém arquivos duplicados.');
                }
                $names[$name] = true;
                $total += (int) ($stat['size'] ?? 0);
            }
            if ($total > 2 * 1024 * 1024 * 1024) {
                throw new BackupException('O conteúdo do backup é grande demais.');
            }
            if (!isset($names['manifest.json'], $names['database/teia.sqlite'])) {
                throw new BackupException('O backup não contém o manifesto ou o banco de dados.');
            }
            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
            if (!is_array($manifest) || ($manifest['app'] ?? null) !== self::APP_ID || (int) ($manifest['format'] ?? 0) !== self::FORMAT) {
                throw new BackupException('Este arquivo não é um backup do site do Teia.');
            }
            $schema = (int) ($manifest['schema_version'] ?? -1);
            if ($schema < 1 || $schema > $this->latestSchemaVersion) {
                throw new BackupException('A versão do backup é incompatível com esta versão do site.');
            }
            $files = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];
            $expectedEntries = array_keys($names);
            sort($expectedEntries);
            $listed = array_merge(array_keys($files), ['manifest.json']);
            sort($listed);
            if ($listed !== $expectedEntries) {
                throw new BackupException('O manifesto não corresponde aos arquivos do backup.');
            }
            foreach ($files as $entry => $hash) {
                $ctx = hash_init('sha256');
                $stream = $zip->getStream($entry);
                if ($stream === false) {
                    throw new BackupException('Não foi possível ler ' . $entry . '.');
                }
                while (!feof($stream)) {
                    hash_update($ctx, (string) fread($stream, 65536));
                }
                fclose($stream);
                if (!is_string($hash) || !hash_equals($hash, hash_final($ctx))) {
                    throw new BackupException('Falha de integridade em ' . $entry . '.');
                }
            }

            // Verifica o banco em um arquivo temporário.
            $this->ensureDir($this->tmpDir);
            $tmpDb = $this->tmpDir . '/restore-' . bin2hex(random_bytes(6)) . '.sqlite';
            $this->extractEntry($zip, 'database/teia.sqlite', $tmpDb);
        } finally {
            $zip->close();
        }

        try {
            $pdo = new PDO('sqlite:' . $tmpDb, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            if ((string) $pdo->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
                throw new BackupException('O banco de dados do backup está corrompido.');
            }
            if ((int) $pdo->query('PRAGMA user_version')->fetchColumn() !== $schema) {
                throw new BackupException('A versão do banco não corresponde ao manifesto.');
            }
            $admins = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1")->fetchColumn();
            if ($admins < 1) {
                throw new BackupException('O backup não tem nenhum administrador ativo e não pode ser restaurado.');
            }
            $pdo = null;
        } catch (BackupException $ex) {
            $pdo = null;
            @unlink($tmpDb);
            throw $ex;
        } catch (\Throwable) {
            $pdo = null;
            @unlink($tmpDb);
            throw new BackupException('Não foi possível abrir o banco de dados do backup.');
        }

        return ['manifest' => $manifest, 'tmpDb' => $tmpDb];
    }

    /**
     * Restaura um backup. $closeDb deve fechar a conexão atual (necessário no Windows) e
     * $reopenDb reabrir após a troca.
     * @return string nome do backup de segurança criado antes da restauração
     */
    public function restore(string $zipPath, callable $closeDb, callable $reopenDb): string
    {
        $validated = $this->validate($zipPath);
        $safety = $this->create('seguranca');

        // Extrai uploads em pasta temporária (nomes já validados).
        $stage = $this->tmpDir . '/restore-uploads-' . bin2hex(random_bytes(6));
        $this->ensureDir($stage);
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::RDONLY);
        foreach (array_keys($validated['manifest']['files']) as $entry) {
            if (str_starts_with($entry, 'uploads/')) {
                $this->extractEntry($zip, $entry, $stage . '/' . basename($entry));
            }
        }
        $zip->close();

        // Libera a conexão (no Windows, um arquivo aberto não pode ser substituído).
        $this->db = null;
        $closeDb();
        gc_collect_cycles();
        try {
            // Troca o banco.
            $old = $this->dbPath . '.antes-restauracao';
            @unlink($old);
            if (is_file($this->dbPath) && !rename($this->dbPath, $old)) {
                throw new BackupException('Não foi possível substituir o banco de dados.');
            }
            if (!rename($validated['tmpDb'], $this->dbPath)) {
                if (is_file($old)) {
                    rename($old, $this->dbPath);
                }
                throw new BackupException('Não foi possível substituir o banco de dados.');
            }
            @unlink($old);
            foreach (['-journal', '-wal', '-shm'] as $suffix) {
                @unlink($this->dbPath . $suffix);
            }

            // Troca os uploads.
            $previous = $this->tmpDir . '/uploads-antigos-' . bin2hex(random_bytes(6));
            if (is_dir($this->uploadDir) && !rename($this->uploadDir, $previous)) {
                throw new BackupException('Não foi possível substituir os arquivos enviados.');
            }
            if (!rename($stage, $this->uploadDir)) {
                throw new BackupException('Não foi possível substituir os arquivos enviados.');
            }
            $this->removeDir($previous);
        } finally {
            $reopenDb();
            if (is_dir($stage)) {
                $this->removeDir($stage);
            }
        }
        return $safety;
    }

    // ------------------------------------------------------------------
    // Configurações
    // ------------------------------------------------------------------

    public function getSetting(string $key): ?string
    {
        $value = $this->conn()->fetchValue('SELECT value FROM settings WHERE key = ?', [$key]);
        return $value === null ? null : (string) $value;
    }

    public function setSetting(string $key, string $value): void
    {
        $this->conn()->execute(
            'INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT (key) DO UPDATE SET value = excluded.value',
            [$key, $value]
        );
    }

    // ------------------------------------------------------------------

    private function conn(): Database
    {
        if ($this->db === null) {
            throw new BackupException('Conexão com o banco encerrada durante a restauração.');
        }
        return $this->db;
    }

    private function extractEntry(ZipArchive $zip, string $entry, string $target): void
    {
        $in = $zip->getStream($entry);
        $out = fopen($target, 'wb');
        if ($in === false || $out === false) {
            throw new BackupException('Falha ao extrair ' . $entry . '.');
        }
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);
    }

    /** @return string[] */
    private function uploadFiles(): array
    {
        $files = [];
        foreach (glob($this->uploadDir . '/*') ?: [] as $file) {
            if (is_file($file) && preg_match('/^[a-f0-9]{32}\.(webp|jpg|png|pdf)$/', basename($file))) {
                $files[] = $file;
            }
        }
        sort($files);
        return $files;
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new BackupException('Diretório indisponível.');
        }
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') ?: [] as $file) {
            is_dir($file) ? $this->removeDir($file) : @unlink($file);
        }
        @rmdir($dir);
    }
}
