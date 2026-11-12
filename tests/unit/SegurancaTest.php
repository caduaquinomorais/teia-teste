<?php

declare(strict_types=1);

use Teia\Database\Database;
use Teia\Database\Migrator;
use Teia\Security\Csrf;
use Teia\Security\Keys;
use Teia\Security\PasswordPolicy;
use Teia\Security\RateLimiter;
use Teia\Security\RichText;
use Teia\Security\UrlValidator;
use Teia\Tests\T;

use function Teia\Tests\tmpdir;

$keys = static fn (): Keys => new Keys(random_bytes(32));

return [
    'e(): escapa aspas simples, duplas e tags' => function (): void {
        T::same('&lt;script&gt;alert(&apos;x&apos;)&lt;/script&gt; &quot;a&quot; &amp;', e("<script>alert('x')</script> \"a\" &"));
        T::same('', e(['array']));
    },
    'URLs: só http, https, mailto, tel e âncoras' => function (): void {
        foreach (['https://app.adesampa.com.br/welcome', 'http://exemplo.com', 'mailto:contato@adesampa.com.br', 'tel:+551130000000', '#mapa'] as $ok) {
            T::true(UrlValidator::isAllowed($ok), $ok);
        }
        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', ' javascript:alert(1)', "java\tscript:alert(1)", 'data:text/html,<b>', 'vbscript:x', 'file:///etc/passwd',
                  '//evil.com', '/admin', 'https://user:pass@evil.com', 'https://', '#', '#1abc', 'mailto:naoemail', 'ftp://x.com'] as $bad) {
            T::false(UrlValidator::isAllowed($bad), "deveria recusar: $bad");
        }
    },
    'URL de incorporação do mapa: só Google Maps via https' => function (): void {
        T::true(UrlValidator::isGoogleMapsEmbed('https://www.google.com/maps/d/embed?mid=abc'));
        T::false(UrlValidator::isGoogleMapsEmbed('http://www.google.com/maps/d/embed?mid=abc'));
        T::false(UrlValidator::isGoogleMapsEmbed('https://evil.com/maps'));
        T::false(UrlValidator::isGoogleMapsEmbed('https://www.google.com.evil.com/maps'));
        T::false(UrlValidator::isGoogleMapsEmbed('https://www.google.com/search?q=x'));
    },
    'texto rico: HTML nunca passa (XSS)' => function (): void {
        $html = RichText::toHtml('<img src=x onerror=alert(1)> <script>alert(1)</script> **ok**');
        T::notContains('<img', $html);
        T::notContains('<script', $html);
        T::contains('&lt;img src=x onerror=alert(1)&gt;', $html);
        T::contains('<strong>ok</strong>', $html);
    },
    'texto rico: link javascript: vira texto; link https vira <a> seguro' => function (): void {
        $html = RichText::toHtml('[clique](javascript:alert(1)) e [site](https://adesampa.com.br)');
        T::notContains('href="javascript', $html);
        T::contains('<a href="https://adesampa.com.br" rel="noopener noreferrer" target="_blank">site</a>', $html);
    },
    'texto rico: atributo não escapa do href' => function (): void {
        $html = RichText::toHtml('[x](https://a.com/"onmouseover="alert(1))');
        T::notContains('onmouseover="alert', $html);
    },
    'texto rico: negrito, itálico, quebra de linha e parágrafo' => function (): void {
        T::same("<p><strong>a</strong> <em>b</em><br>\nc</p>\n<p>d</p>", RichText::toHtml("**a** *b*\nc\n\nd"));
    },
    'texto rico: caracteres de controle (marcadores internos) são removidos' => function (): void {
        $html = RichText::toHtml("\x010\x02 [a](https://a.com)");
        T::same(1, substr_count($html, '<a '));
    },
    'senha: mínimo de 12 caracteres' => function (): void {
        T::true(PasswordPolicy::validate('curta123') !== []);
        T::same([], PasswordPolicy::validate('cavalo bateria grampo', 'maria'));
    },
    'senha: comuns e variações triviais são bloqueadas' => function (): void {
        T::true(PasswordPolicy::validate('123456789012') !== []);
        T::true(PasswordPolicy::validate('password1234') !== []);
        T::true(PasswordPolicy::validate('Senha123456!') !== [], 'variação de "senha"');
        T::true(PasswordPolicy::validate('adesampa2026!!') !== [], 'variação de "adesampa"');
    },
    'senha: não pode conter o nome de usuário' => function (): void {
        T::true(PasswordPolicy::validate('xx-maria.silva-xx-2026', 'maria.silva') !== []);
    },
    'senha: poucos caracteres distintos são recusados' => function (): void {
        T::true(PasswordPolicy::validate('abababababababab') !== []);
    },
    'senha: hash Argon2id (ou bcrypt de reserva) verificável' => function (): void {
        $hash = PasswordPolicy::hash('cavalo bateria grampo');
        T::true(password_verify('cavalo bateria grampo', $hash));
        T::true(defined('PASSWORD_ARGON2ID') ? str_starts_with($hash, '$argon2id$') : str_starts_with($hash, '$2y$'));
        T::false(PasswordPolicy::needsRehash($hash));
    },
    'senha temporária: 16 caracteres aleatórios' => function (): void {
        $a = PasswordPolicy::generateTemporary();
        T::same(16, strlen($a));
        T::true($a !== PasswordPolicy::generateTemporary());
    },
    'CSRF sem estado: válido, expirado, adulterado e de outra finalidade' => function () use ($keys): void {
        $csrf = new Csrf($keys());
        $token = $csrf->issueStateless('instalar');
        T::true($csrf->validStateless('instalar', $token));
        T::false($csrf->validStateless('recuperar', $token), 'outra finalidade');
        T::false($csrf->validStateless('instalar', substr($token, 0, -1) . (str_ends_with($token, 'a') ? 'b' : 'a')), 'adulterado');
        T::false($csrf->validStateless('instalar', $csrf->issueStateless('instalar', time() - 10000)), 'expirado');
        T::false($csrf->validStateless('instalar', (new Csrf($keys()))->issueStateless('instalar')), 'outra chave');
        T::false($csrf->validStateless('instalar', ['array']));
    },
    'limitador: bloqueia após o máximo, por chave HMAC, e libera após a janela' => function () use ($keys): void {
        $db = new Database(tmpdir() . '/t.sqlite');
        (new Migrator($db, TEIA_ROOT . '/app/migrations'))->migrate();
        $rl = new RateLimiter($db, $keys());
        $now = 1_000_000;
        for ($i = 0; $i < 5; $i++) {
            T::false($rl->tooMany('login:ip:203.0.113.9', 5, 900, $now));
            $rl->hit('login:ip:203.0.113.9', 900, $now);
        }
        T::true($rl->tooMany('login:ip:203.0.113.9', 5, 900, $now + 10));
        T::false($rl->tooMany('login:ip:203.0.113.10', 5, 900, $now + 10), 'outro IP');
        T::false($rl->tooMany('login:ip:203.0.113.9', 5, 900, $now + 901), 'janela expirada');
        $raw = json_encode($db->fetchAll('SELECT * FROM rate_limits'));
        T::notContains('203.0.113.9', (string) $raw, 'IP puro não pode ser gravado');
    },
];
