<?php

declare(strict_types=1);

namespace Teia\Security;

defined('TEIA_ROOT') || exit;

/**
 * Política de senhas (OWASP ASVS 2.1):
 *  - mínimo de 12 caracteres (máximo 128);
 *  - bloqueio de senhas comuns (lista em app/config/common-passwords.txt);
 *  - bloqueio de senha que contém o nome de usuário;
 *  - hash Argon2id, com bcrypt como alternativa quando Argon2id não existir.
 */
final class PasswordPolicy
{
    public const MIN = 12;
    public const MAX = 128;

    private static ?array $common = null;

    /** @return string[] mensagens de erro (vazio = senha aceita) */
    public static function validate(string $password, string $username = '', array $extraWords = []): array
    {
        $errors = [];
        $length = mb_strlen($password, 'UTF-8');
        if ($length < self::MIN) {
            $errors[] = 'A senha deve ter pelo menos ' . self::MIN . ' caracteres.';
        }
        if ($length > self::MAX) {
            $errors[] = 'A senha deve ter no máximo ' . self::MAX . ' caracteres.';
        }
        $lower = mb_strtolower($password, 'UTF-8');
        $user = mb_strtolower(trim($username), 'UTF-8');
        if ($user !== '' && mb_strlen($user, 'UTF-8') >= 3 && str_contains($lower, $user)) {
            $errors[] = 'A senha não pode conter o nome de usuário.';
        }
        foreach ($extraWords as $word) {
            $word = mb_strtolower(trim((string) $word), 'UTF-8');
            if (mb_strlen($word, 'UTF-8') >= 4 && $lower === $word) {
                $errors[] = 'Escolha uma senha menos previsível.';
                break;
            }
        }
        if (self::isCommon($lower)) {
            $errors[] = 'Esta senha é muito comum. Escolha outra.';
        }
        if ($length >= self::MIN && count(array_unique(mb_str_split($password, 1, 'UTF-8'))) < 4) {
            $errors[] = 'A senha repete poucos caracteres diferentes. Escolha outra.';
        }
        return $errors;
    }

    public static function isCommon(string $lowerPassword): bool
    {
        if (self::$common === null) {
            $file = TEIA_ROOT . '/app/config/common-passwords.txt';
            $list = is_file($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
            self::$common = array_fill_keys(array_map(static fn ($l) => mb_strtolower(trim($l), 'UTF-8'), $list ?: []), true);
        }
        if (isset(self::$common[$lowerPassword])) {
            return true;
        }
        // Variações triviais: sufixos numéricos/símbolos sobre uma senha comum (ex.: "senha123456!").
        $stripped = preg_replace('/[\d\W_]+$/u', '', $lowerPassword) ?? $lowerPassword;
        return $stripped !== '' && $stripped !== $lowerPassword && isset(self::$common[$stripped]);
    }

    public static function hash(string $password): string
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID);
        }
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function needsRehash(string $hash): bool
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return password_needs_rehash($hash, PASSWORD_ARGON2ID);
        }
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function generateTemporary(): string
    {
        // 16 caracteres sem ambiguidades (sem 0/O, 1/l/I).
        $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < 16; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }
}
