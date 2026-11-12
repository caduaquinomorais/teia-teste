<?php

declare(strict_types=1);

use Teia\Tests\Server;
use Teia\Tests\T;

$server = null;
$srv = static function () use (&$server): Server {
    return $server ??= new Server();
};

return [
    'home 200 com os textos do Figma' => function () use ($srv): void {
        $r = $srv()->client()->get('/');
        T::same(200, $r->status);
        foreach (['Coworkings públicos', 'Estúdios de podcast', 'Estúdios de fotografia', 'Estúdios de música', 'Encontre o Teia mais próximo de você',
                  'Dúvidas frequentes', 'Normas de uso dos espaços', 'Manual de conduta', 'Uso para gravações', 'ADE SAMPA – Agência São Paulo de Desenvolvimento'] as $text) {
            T::contains($text, $r->body);
        }
        T::contains('<html lang="pt-BR"', $r->body);
        T::contains('href="https://app.adesampa.com.br/welcome"', $r->body);
        T::notContains('Lorem', $r->body);
    },
    'visitante não recebe cookie' => function () use ($srv): void {
        T::same([], $srv()->client()->get('/')->setCookies());
        T::same([], $srv()->client()->get('/nao-existe')->setCookies());
    },
    'cabeçalhos de segurança presentes e X-Powered-By ausente' => function () use ($srv): void {
        $r = $srv()->client()->get('/');
        $csp = (string) $r->header('Content-Security-Policy');
        T::matches("/script-src 'self' 'nonce-[A-Za-z0-9_-]{20,}'/", $csp);
        T::contains("frame-ancestors 'none'", $csp);
        T::contains("object-src 'none'", $csp);
        T::same('nosniff', $r->header('X-Content-Type-Options'));
        T::same('strict-origin-when-cross-origin', $r->header('Referrer-Policy'));
        T::true($r->header('Permissions-Policy') !== null);
        T::same(null, $r->header('X-Powered-By'));
        T::same(null, $r->header('Strict-Transport-Security'), 'HSTS só em HTTPS');
    },
    'nonce da CSP muda a cada requisição e é usado no script' => function () use ($srv): void {
        $a = $srv()->client()->get('/');
        $b = $srv()->client()->get('/');
        preg_match("/'nonce-([^']+)'/", (string) $a->header('Content-Security-Policy'), $m);
        T::contains('nonce="' . $m[1] . '"', $a->body);
        T::true($a->header('Content-Security-Policy') !== $b->header('Content-Security-Policy'));
    },
    'página 404 personalizada, sem detalhes internos' => function () use ($srv): void {
        $r = $srv()->client()->get('/pagina-que-nao-existe');
        T::same(404, $r->status);
        T::contains('Página não encontrada', $r->body);
        T::notContains('Stack', $r->body);
        T::notContains(DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR, $r->body);
    },
    'código-fonte e dados não são servidos' => function () use ($srv): void {
        $c = $srv()->client();
        foreach (['/app/bootstrap.php', '/app/config/app.php', '/../app/bootstrap.php', '/.htaccess', '/index.php/../../app/bootstrap.php', '/midia/../../app.key', '/midia/' . str_repeat('a', 32) . '.php'] as $path) {
            $r = $c->get($path);
            T::true(in_array($r->status, [400, 404], true), "$path → {$r->status}");
            T::notContains('<?php', $r->body);
        }
    },
    'assets estáticos servidos (CSS, fonte, imagem WebP)' => function () use ($srv): void {
        $c = $srv()->client();
        T::same(200, $c->get('/assets/css/site.css')->status);
        T::same(200, $c->get('/assets/fonts/geist-latin.woff2')->status);
        T::same(200, $c->get('/assets/images/foto-coworking.webp')->status);
    },
    'método não permitido retorna 405' => function () use ($srv): void {
        T::same(405, $srv()->client()->get('/admin/sair')->status);
    },
    'antes da instalação: site funciona com conteúdo do Figma e painel leva ao instalador' => function () use ($srv): void {
        $r = $srv()->client()->get('/admin');
        T::same(303, $r->status);
        T::contains('/instalar', $r->location());
    },
];
