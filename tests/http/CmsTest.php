<?php

declare(strict_types=1);

use Teia\Tests\Fixtures;
use Teia\Tests\Http;
use Teia\Tests\T;

$state = null;
$setup = static function () use (&$state): array {
    if ($state === null) {
        $s = Fixtures::installedServer();
        $state = ['s' => $s, 'admin' => Fixtures::login($s)];
    }
    return $state;
};

/** Envia o formulário da página com alterações. */
$publish = static function (Http $c, string $slug, array $overrides): \Teia\Tests\Res {
    $page = $c->get('/admin/paginas/' . $slug);
    T::same(200, $page->status);
    $form = Fixtures::cmsForm($page->body, $overrides);
    return $c->request('POST', '/admin/paginas/' . $slug, Fixtures::encode($form), ['Content-Type' => 'application/x-www-form-urlencoded']);
};

return [
    'formulário é gerado a partir da definição (todas as seções)' => function () use ($setup): void {
        ['admin' => $a] = $setup();
        $r = $a->get('/admin/paginas/teia');
        foreach (['Banner (topo da página)', 'Espaços (coworking e estúdios)', 'Mapa das unidades', 'Dúvidas frequentes', 'Normas de uso dos espaços', 'Links gerais'] as $label) {
            T::contains($label, $r->body);
        }
        T::contains('name="c[banner][titulo]"', $r->body);
        T::contains('name="c[espacos][itens][i0][titulo]"', $r->body);
        T::contains('<template data-template>', $r->body);
    },
    'publicação imediata: alteração aparece no site' => function () use ($setup, $publish): void {
        ['admin' => $a, 's' => $s] = $setup();
        $r = $publish($a, 'teia', ['c[banner][texto]' => 'Texto novo publicado pelo CMS.']);
        T::same(303, $r->status);
        T::contains('Texto novo publicado pelo CMS.', $s->client()->get('/')->body);
    },
    'XSS: conteúdo do CMS é sempre escapado no site' => function () use ($setup, $publish): void {
        ['admin' => $a, 's' => $s] = $setup();
        $payload = '"><script>alert(1)</script><img src=x onerror=alert(2)>';
        $r = $publish($a, 'teia', [
            'c[banner][titulo]' => 'T' . substr($payload, 0, 25),
            'c[espacos][itens][i0][texto]' => $payload . ' [x](javascript:alert(3))',
            'c[mapa][unidades][i0][nome]' => '</iframe><script>alert(4)</script>',
        ]);
        T::same(303, $r->status);
        $home = $s->client()->get('/')->body;
        T::notContains('<script>alert', $home);
        T::notContains('<img src=x', $home);
        T::notContains('href="javascript', $home);
        T::contains('&lt;script&gt;alert(1)&lt;/script&gt;', $home);
    },
    'validação no servidor: erro não publica nada (422)' => function () use ($setup, $publish): void {
        ['admin' => $a, 's' => $s] = $setup();
        $strip = static fn (string $html): string => (string) preg_replace('/nonce="[^"]+"/', '', $html);
        $before = $strip($s->client()->get('/')->body);
        $r = $publish($a, 'teia', ['c[banner][titulo]' => '', 'c[geral][link_agendamento]' => 'javascript:alert(1)', 'c[banner][tag]' => 'NÃO DEVE APARECER']);
        T::same(422, $r->status);
        T::contains('Nada foi publicado', $r->body);
        T::contains('“Título” é obrigatório.', $r->body);
        T::contains('link inválido', $r->body);
        T::same($before, $strip($s->client()->get('/')->body));
    },
    'campos não declarados são ignorados (lista branca)' => function () use ($setup, $publish): void {
        ['admin' => $a] = $setup();
        $r = $publish($a, 'teia', ['c[banner][campo_falso]' => 'x', 'c[secao_falsa][a]' => 'y', 'c[banner][titulo]' => 'Teia']);
        T::same(303, $r->status);
        $edit = $a->get('/admin/paginas/teia')->body;
        T::notContains('campo_falso', $edit);
        T::notContains('secao_falsa', $edit);
    },
    'listas repetíveis: adicionar, remover e reordenar' => function () use ($setup, $publish): void {
        ['admin' => $a, 's' => $s] = $setup();
        $r = $publish($a, 'teia', [
            'c[duvidas][itens][nova1][pergunta]' => 'Pergunta adicionada pelo teste?',
            'c[duvidas][itens][nova1][resposta]' => 'Resposta com **negrito**.',
            'c[duvidas][itens][i1][pergunta]' => null,
            'c[duvidas][itens][i1][resposta]' => null,
        ]);
        T::same(303, $r->status);
        $home = $s->client()->get('/')->body;
        T::contains('Pergunta adicionada pelo teste?', $home);
        T::contains('Resposta com <strong>negrito</strong>.', $home);
        $edit = $a->get('/admin/paginas/teia')->body;
        T::notContains('Preciso ter um CNPJ para utilizar?', $edit, 'item removido');
        // Perguntas sem resposta não aparecem no site.
        T::notContains('O uso do Teia é totalmente gratuito?', $home);
    },
    'conflito de edição: segundo envio com versão antiga → 409 e nada é sobrescrito' => function () use ($setup): void {
        ['admin' => $a, 's' => $s] = $setup();
        $other = Fixtures::login($s);
        $pageA = $a->get('/admin/paginas/teia')->body;
        $pageB = $other->get('/admin/paginas/teia')->body;
        $formA = Fixtures::cmsForm($pageA, ['c[banner][tag]' => 'Versão de A']);
        $formB = Fixtures::cmsForm($pageB, ['c[banner][tag]' => 'Versão de B']);
        T::same(303, $a->request('POST', '/admin/paginas/teia', Fixtures::encode($formA), ['Content-Type' => 'application/x-www-form-urlencoded'])->status);
        $r = $other->request('POST', '/admin/paginas/teia', Fixtures::encode($formB), ['Content-Type' => 'application/x-www-form-urlencoded']);
        T::same(409, $r->status);
        T::contains('Conflito de edição', $r->body);
        T::contains('Versão de B', $r->body, 'alterações preservadas no formulário');
        $home = $s->client()->get('/')->body;
        T::contains('Versão de A', $home);
        T::notContains('Versão de B', $home);
    },
    'histórico lista versões e restauração volta o conteúdo' => function () use ($setup, $publish): void {
        ['admin' => $a, 's' => $s] = $setup();
        T::same(303, $publish($a, 'erro-404', ['c[mensagem][titulo]' => 'Título A'])->status);
        T::same(303, $publish($a, 'erro-404', ['c[mensagem][titulo]' => 'Título B'])->status);
        $hist = $a->get('/admin/paginas/erro-404/historico');
        T::same(200, $hist->status);
        preg_match_all('#/admin/paginas/erro-404/restaurar/(\d+)#', $hist->body, $m);
        T::same(['1'], $m[1]);
        preg_match('/name="current_version" value="(\d+)"/', $hist->body, $cv);
        $r = $a->post('/admin/paginas/erro-404/restaurar/1', ['_csrf' => Http::csrf($hist->body), 'current_version' => $cv[1]]);
        T::same(303, $r->status);
        T::contains('Título A', $s->client()->get('/nao-existe')->body);
        // Restaurar com versão desatualizada → 409.
        $r = $a->post('/admin/paginas/erro-404/restaurar/2', ['_csrf' => Http::csrf($hist->body), 'current_version' => $cv[1]]);
        T::same(409, $r->status);
    },
    'upload de imagem: reprocessada e servida por rota própria com cabeçalhos restritivos' => function () use ($setup): void {
        ['admin' => $a, 's' => $s] = $setup();
        $tmp = tempnam(sys_get_temp_dir(), 'img') . '.png';
        $im = imagecreatetruecolor(300, 200);
        imagefill($im, 0, 0, imagecolorallocate($im, 10, 120, 200));
        imagepng($im, $tmp);
        $page = $a->get('/admin/paginas/teia');
        $form = Fixtures::cmsForm($page->body);
        $form['u[banner.fundo]'] = new CURLFile($tmp, 'image/png', 'fundo.png');
        $r = $a->request('POST', '/admin/paginas/teia', $form);
        T::same(303, $r->status);
        $home = $s->client()->get('/')->body;
        T::true((bool) preg_match('#/midia/([a-f0-9]{32})\.webp#', $home, $m), 'imagem nova no site');
        $img = $s->client()->get('/midia/' . $m[1] . '.webp');
        T::same(200, $img->status);
        T::same('image/webp', $img->header('Content-Type'));
        T::same('nosniff', $img->header('X-Content-Type-Options'));
        T::contains('sandbox', (string) $img->header('Content-Security-Policy'));
        T::false(is_file(TEIA_ROOT . '/public/' . $m[1] . '.webp'), 'fora de public/');
        @unlink($tmp);
    },
    'upload malicioso: PHP como imagem e SVG são recusados (nada publicado)' => function () use ($setup): void {
        ['admin' => $a] = $setup();
        $php = tempnam(sys_get_temp_dir(), 'x');
        file_put_contents($php, '<?php echo "pwned"; ?>');
        $svg = tempnam(sys_get_temp_dir(), 'y');
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        foreach ([[$php, 'image/jpeg', 'foto.jpg'], [$svg, 'image/svg+xml', 'logo.svg']] as [$file, $mime, $name]) {
            $page = $a->get('/admin/paginas/teia');
            $form = Fixtures::cmsForm($page->body);
            $form['u[banner.fundo]'] = new CURLFile($file, $mime, $name);
            $r = $a->request('POST', '/admin/paginas/teia', $form);
            T::same(422, $r->status, $name);
            T::contains('Formato não permitido', $r->body);
        }
        @unlink($php);
        @unlink($svg);
    },
    'PDF enviado aparece com botão de download (Content-Disposition: attachment)' => function () use ($setup): void {
        ['admin' => $a, 's' => $s] = $setup();
        $pdf = tempnam(sys_get_temp_dir(), 'p');
        file_put_contents($pdf, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n");
        $page = $a->get('/admin/paginas/teia');
        $form = Fixtures::cmsForm($page->body);
        $form['u[normas.documentos.i0.arquivo]'] = new CURLFile($pdf, 'application/pdf', 'Manual de conduta.pdf');
        T::same(303, $a->request('POST', '/admin/paginas/teia', $form)->status);
        $home = $s->client()->get('/')->body;
        T::true((bool) preg_match('#href="(/midia/[a-f0-9]{32}\.pdf)" download#', $home, $m));
        $file = $s->client()->get($m[1]);
        T::same('application/pdf', $file->header('Content-Type'));
        T::contains('attachment', (string) $file->header('Content-Disposition'));
        @unlink($pdf);
    },
    'cabeçalho e rodapé: link pendente (TODO-LINK) aparece sem href; link preenchido funciona' => function () use ($setup, $publish): void {
        ['admin' => $a, 's' => $s] = $setup();
        T::contains('data-link-pendente="true"', $s->client()->get('/')->body);
        T::same(303, $publish($a, 'layout', ['c[rodape][redes][instagram]' => 'https://www.instagram.com/adesampa'])->status);
        T::contains('href="https://www.instagram.com/adesampa"', $s->client()->get('/')->body);
    },
];
