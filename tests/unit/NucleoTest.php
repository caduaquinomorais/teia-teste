<?php

declare(strict_types=1);

use Teia\Core\App;
use Teia\Http\HttpException;
use Teia\Http\Request;
use Teia\Http\Router;
use Teia\Security\UrlValidator;
use Teia\Tests\T;
use Teia\View\Render;

return [
    'roteador: parâmetros com regex' => function (): void {
        $r = new Router();
        $r->get('/secao/{slug:[a-z0-9-]{1,40}}', fn () => 'ok');
        T::same(['slug' => 'teia'], $r->match('GET', '/secao/teia')['params']);
    },
    'roteador: 404 para caminho inválido (regex não aceita ../)' => function (): void {
        $r = new Router();
        $r->get('/secao/{slug:[a-z0-9-]{1,40}}', fn () => 'ok');
        T::same(404, T::throws(HttpException::class, fn () => $r->match('GET', '/secao/../../etc'))->status);
        T::same(404, T::throws(HttpException::class, fn () => $r->match('GET', '/secao/TEIA'))->status);
    },
    'roteador: 405 com cabeçalho Allow; HEAD usa a rota GET' => function (): void {
        $r = new Router();
        $r->get('/', fn () => 'x');
        $ex = T::throws(HttpException::class, fn () => $r->match('POST', '/'));
        T::same(405, $ex->status);
        T::same('GET', $ex->headers['Allow']);
        T::same([], $r->match('HEAD', '/')['params']);
    },
    'request: detecção do base path (subpasta /teia)' => function (): void {
        T::same('', Request::detectBasePath('/index.php'));
        T::same('/teia', Request::detectBasePath('/teia/public/index.php'));
        T::same('/teia', Request::detectBasePath('/teia/index.php'));
        T::same('/', Request::stripBasePath('/teia', '/teia'));
        T::same('/x', Request::stripBasePath('/teia/x', '/teia'));
        T::same('/x', Request::stripBasePath('/teia/public/x', '/teia'));
        T::same('/teiax', Request::stripBasePath('/teiax', '/teia'));
    },
    'e(): escapa aspas simples, duplas e tags' => function (): void {
        T::same('&lt;script&gt;alert(&apos;x&apos;)&lt;/script&gt; &quot;a&quot; &amp;', e("<script>alert('x')</script> \"a\" &"));
        T::same('', e(['array']));
    },
    'URLs: só http, https, mailto, tel e âncoras' => function (): void {
        foreach (['https://app.adesampa.com.br/welcome', 'http://exemplo.com', 'mailto:contato@adesampa.com.br', 'tel:+551130000000', '#mapa'] as $ok) {
            T::true(UrlValidator::isAllowed($ok), $ok);
        }
        foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', ' javascript:alert(1)', "java\tscript:alert(1)", 'data:text/html,<b>', 'vbscript:x',
                  'file:///etc/passwd', '//evil.com', 'https://user:pass@evil.com', 'https://', '#', '#1abc', 'mailto:naoemail', 'ftp://x.com'] as $bad) {
            T::false(UrlValidator::isAllowed($bad), "deveria recusar: $bad");
        }
    },
    'mapa personalizado: só incorporação do Google Maps via https' => function (): void {
        T::true(UrlValidator::isGoogleMapsEmbed('https://www.google.com/maps/d/embed?mid=abc'));
        T::false(UrlValidator::isGoogleMapsEmbed('http://www.google.com/maps/d/embed?mid=abc'));
        T::false(UrlValidator::isGoogleMapsEmbed('https://www.google.com.evil.com/maps'));
        T::false(UrlValidator::isGoogleMapsEmbed('https://www.google.com/search?q=x'));
        T::false(UrlValidator::isGoogleMapsEmbed(''));
    },
    'render: link vazio ou perigoso fica sem href; externo ganha rel=noopener' => function (): void {
        $h = new Render(TEIA_ROOT . '/public/assets');
        T::same(' data-link-pendente="true"', $h->href(''));
        T::same(' data-link-pendente="true"', $h->href('javascript:alert(1)'));
        T::same(' href="#mapa"', $h->href('#mapa'));
        T::same(' href="https://a.com/?x=1&amp;y=&quot;2&quot;" rel="noopener noreferrer"', $h->href('https://a.com/?x=1&y="2"'));
    },
    'render: parágrafos sempre escapados' => function (): void {
        $h = new Render(TEIA_ROOT . '/public/assets');
        T::same("<p>a &lt;b&gt;<br>\nc</p>\n<p>d</p>", $h->paragraphs("a <b>\nc\n\nd"));
        T::same('', $h->paragraphs("  \n "));
    },
    'render: imagem com WebP + fallback; nome com caminho é ignorado' => function (): void {
        $h = new Render(TEIA_ROOT . '/public/assets');
        $html = $h->picture('foto-coworking', 'Foto "x"');
        T::contains('type="image/webp"', $html);
        T::contains('foto-coworking.jpg', $html);
        T::contains('alt="Foto &quot;x&quot;"', $html);
        T::same('', $h->picture('../app/bootstrap', 'x'));
        T::same('', $h->picture('nao-existe', 'x'));
    },
    'render: PDF só se o arquivo existir (ou URL https)' => function (): void {
        $h = new Render(TEIA_ROOT . '/public/assets');
        T::same(null, $h->fileUrl(''));
        T::same(null, $h->fileUrl('inexistente.pdf'));
        T::same(null, $h->fileUrl('../../app/config/app.php'));
        T::same(null, $h->fileUrl('http://inseguro.com/a.pdf'));
        T::same('https://exemplo.com/manual.pdf', $h->fileUrl('https://exemplo.com/manual.pdf'));
    },
    'conteúdo: textos exatos do Figma e nenhum Lorem ipsum' => function (): void {
        $app = new App();
        $teia = $app->content('teia');
        T::same('Teia', $teia['banner']['titulo']);
        T::same('Espaços públicos de trabalho', $teia['banner']['tag']);
        T::same(['Coworkings públicos', 'Estúdios de podcast', 'Estúdios de fotografia', 'Estúdios de música'], array_column($teia['espacos'], 'titulo'));
        T::same('https://app.adesampa.com.br/welcome', $teia['geral']['link_agendamento']);
        $layout = $app->content('layout');
        T::same(['Home', 'Sobre', 'Programas', 'Comunicação', 'Transparência'], array_column($layout['cabecalho']['menu'], 'rotulo'));
        T::same('Atendimenro ao empreendedor', $layout['rodape']['colunas']['col_empreende']['links'][0]['rotulo'], 'grafia exata do Figma');
        foreach (['teia', 'layout', 'erro-404'] as $name) {
            T::notContains('lorem', mb_strtolower((string) json_encode($app->content($name))));
        }
    },
    'conteúdo: nome de arquivo com caminho é recusado' => function (): void {
        T::throws(InvalidArgumentException::class, fn () => (new App())->content('../config/app'));
    },
];
