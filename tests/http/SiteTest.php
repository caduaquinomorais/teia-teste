<?php

declare(strict_types=1);

use Teia\Tests\Server;
use Teia\Tests\T;

use function Teia\Tests\tmpdir;

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
        T::contains('data-link-pendente="true"', $r->body, 'links institucionais pendentes (TODO-LINK)');
        T::contains('https://www.google.com/maps?q=', $r->body);
        T::notContains('Lorem', $r->body);
    },
    'perguntas sem resposta e documentos sem PDF não exibem conteúdo provisório' => function () use ($srv): void {
        $body = $srv()->client()->get('/')->body;
        T::notContains('O uso do Teia é totalmente gratuito?', $body);
        T::notContains('Baixar PDF', $body);
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
        T::contains('frame-src https://www.google.com https://maps.google.com', $csp);
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
    'página 404 personalizada; iframe do mapa bloqueado fora da home' => function () use ($srv): void {
        $r = $srv()->client()->get('/pagina-que-nao-existe');
        T::same(404, $r->status);
        T::contains('Página não encontrada', $r->body);
        T::contains("frame-src 'none'", (string) $r->header('Content-Security-Policy'));
        T::notContains(DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR, $r->body);
    },
    'painel, instalador e rotas de mídia não existem mais (404)' => function () use ($srv): void {
        $c = $srv()->client();
        foreach (['/admin', '/admin/login', '/instalar', '/cron/backup', '/midia/' . str_repeat('a', 32) . '.webp'] as $path) {
            T::same(404, $c->get($path)->status, $path);
        }
    },
    'código-fonte não é servido' => function () use ($srv): void {
        $c = $srv()->client();
        foreach (['/app/bootstrap.php', '/app/config/app.php', '/../app/bootstrap.php', '/.htaccess', '/index.php/../../app/bootstrap.php', '/app/content/teia.php'] as $path) {
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
    'POST no site retorna 405' => function () use ($srv): void {
        T::same(405, $srv()->client()->post('/', ['a' => 'b'])->status);
    },
    'erro interno: página genérica com código de referência, sem detalhes' => function (): void {
        $dir = tmpdir();
        foreach (['layout', 'erro-404'] as $f) {
            copy(TEIA_ROOT . "/app/content/$f.php", "$dir/$f.php");
        }
        file_put_contents("$dir/teia.php", "<?php return ['quebrado' => true];\n");
        $s = new Server($dir);
        $r = $s->client()->get('/');
        T::same(500, $r->status);
        T::matches('/Código de referência: <strong>[0-9A-F]{8}<\/strong>/', $r->body);
        T::notContains('quebrado', $r->body);
        T::notContains('.php', $r->body);
        $s->stop();
    },
    'conteúdo editado no arquivo aparece escapado (sem XSS)' => function (): void {
        $dir = tmpdir();
        foreach (['layout', 'erro-404', 'teia'] as $f) {
            copy(TEIA_ROOT . "/app/content/$f.php", "$dir/$f.php");
        }
        $src = (string) file_get_contents("$dir/teia.php");
        $src = str_replace("'resposta' => ''],\n            ['pergunta' => 'Preciso", "'resposta' => '<script>alert(1)</script> Sim, é gratuito.'],\n            ['pergunta' => 'Preciso", $src);
        file_put_contents("$dir/teia.php", $src);
        $s = new Server($dir);
        $body = $s->client()->get('/')->body;
        T::contains('1. O uso do Teia é totalmente gratuito?', $body);
        T::contains('&lt;script&gt;alert(1)&lt;/script&gt; Sim, é gratuito.', $body);
        T::notContains('<script>alert(1)', $body);
        $s->stop();
    },
];
