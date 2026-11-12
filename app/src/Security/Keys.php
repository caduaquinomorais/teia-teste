<?php

declare(strict_types=1);

namespace Teia\Security;

defined('TEIA_ROOT') || exit;

/**
 * Chave mestra da aplicação (<dados>/app.key, 32 bytes aleatórios em base64, fora do Git).
 * Subchaves são derivadas por finalidade (HMAC-SHA256), nunca reutilizadas entre usos.
 */
final class Keys
{
    private string $master;

    public function __construct(string $master)
    {
        if (strlen($master) < 32) {
            throw new \RuntimeException('Chave da aplicação inválida.');
        }
        $this->master = $master;
    }

    public static function fromFile(string $file): self
    {
        $raw = is_file($file) ? trim((string) file_get_contents($file)) : '';
        $decoded = base64_decode($raw, true);
        if ($decoded === false || strlen($decoded) < 32) {
            throw new \RuntimeException('Arquivo app.key ausente ou inválido.');
        }
        return new self($decoded);
    }

    public static function generate(string $file): void
    {
        $key = base64_encode(random_bytes(32));
        if (file_put_contents($file, $key . "\n", LOCK_EX) === false) {
            throw new \RuntimeException('Não foi possível gravar app.key.');
        }
        @chmod($file, 0600);
    }

    public function derive(string $purpose): string
    {
        return hash_hmac('sha256', 'teia:' . $purpose, $this->master, true);
    }

    public function hmac(string $purpose, string $data): string
    {
        return hash_hmac('sha256', $data, $this->derive($purpose));
    }
}
