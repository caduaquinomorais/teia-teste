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

$publishTag = static function (Http $a, string $tag): void {
    $page = $a->get('/admin/paginas/teia');
    $form = Fixtures::cmsForm($page->body, ['c[banner][tag]' => $tag]);
    T::same(303, $a->request('POST', '/admin/paginas/teia', Fixtures::encode($form), ['Content-Type' => 'application/x-www-form-urlencoded'])->status);
};

return [
    'backup automático diário é criado ao usar o painel' => function () use ($setup): void {
        ['admin' => $a] = $setup();
        $r = $a->get('/admin/backups');
        T::contains('Automático', $r->body);
    },
    'criar, baixar (ZIP) e restaurar: conteúdo volta ao estado do backup' => function () use ($setup, $publishTag): void {
        ['admin' => $a, 's' => $s] = $setup();
        $publishTag($a, 'Estado antes do backup');
        [, $csrf] = Fixtures::page($a, '/admin/backups');
        T::same(303, $a->post('/admin/backups/criar', ['_csrf' => $csrf])->status);
        $page = $a->get('/admin/backups')->body;
        preg_match('#/admin/backups/baixar/(backup-[0-9-]+-manual-[a-f0-9]{6}\.zip)#', $page, $m);
        $name = $m[1];
        $zip = $a->get('/admin/backups/baixar/' . $name);
        T::same(200, $zip->status);
        T::same('application/zip', $zip->header('Content-Type'));
        T::same("PK\x03\x04", substr($zip->body, 0, 4));

        $publishTag($a, 'Estado depois do backup');
        T::contains('Estado depois do backup', $s->client()->get('/')->body);

        [, $csrf] = Fixtures::page($a, '/admin/backups');
        T::same(303, $a->post('/admin/backups/restaurar', ['_csrf' => $csrf, 'name' => $name, 'confirmacao' => 'nao'])->status);
        T::contains('Estado depois do backup', $s->client()->get('/')->body, 'sem confirmação nada muda');

        T::same(303, $a->post('/admin/backups/restaurar', ['_csrf' => $csrf, 'name' => $name, 'confirmacao' => 'RESTAURAR'])->status);
        T::contains('Estado antes do backup', $s->client()->get('/')->body);
        $list = Fixtures::login($s)->get('/admin/backups')->body;
        T::contains('Segurança (antes de restaurar)', $list);
    },
    'restaurar ZIP malicioso enviado (Zip Slip) é recusado' => function () use ($setup): void {
        ['admin' => $a, 's' => $s] = $setup();
        $a = Fixtures::login($s);
        $zipPath = tempnam(sys_get_temp_dir(), 'z') . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFromString('manifest.json', json_encode(['app' => 'teia-ade-sampa', 'format' => 1, 'schema_version' => 1, 'files' => []]));
        $zip->addFromString('../../public/x.php', '<?php echo 1;');
        $zip->close();
        [, $csrf] = Fixtures::page($a, '/admin/backups');
        $r = $a->request('POST', '/admin/backups/restaurar', ['_csrf' => $csrf, 'confirmacao' => 'RESTAURAR', 'arquivo' => new CURLFile($zipPath, 'application/zip', 'b.zip')]);
        T::same(303, $r->status);
        T::contains('Restauração recusada', $a->get('/admin/backups')->body);
        T::false(is_file(TEIA_ROOT . '/public/x.php'));
        @unlink($zipPath);
    },
    'download de backup: nome com caminho é recusado' => function () use ($setup): void {
        ['s' => $s] = $setup();
        $a = Fixtures::login($s);
        T::same(404, $a->get('/admin/backups/baixar/..%2F..%2Fapp.key')->status);
        T::same(404, $a->get('/admin/backups/baixar/backup-20260101-000000-manual-aaaaaa.zip')->status);
    },
    'cron (KingHost): 404 sem código configurado, 403 com código errado, 200 com o certo' => function () use ($setup): void {
        ['s' => $s] = $setup();
        $a = Fixtures::login($s);
        T::same(404, $s->client()->get('/cron/backup')->status);
        [, $csrf] = Fixtures::page($a, '/admin/backups');
        T::same(303, $a->post('/admin/backups/configurar', ['_csrf' => $csrf, 'retention' => '10', 'cron_secret' => 'codigo-do-cron-kinghost-123'])->status);
        T::same(403, $s->client()->get('/cron/backup', ['X-Cron-Auth' => 'errado-errado-errado'])->status);
        $r = $s->client()->get('/cron/backup', ['X-Cron-Auth' => 'codigo-do-cron-kinghost-123']);
        T::same(200, $r->status);
        T::contains('"ok":true', $r->body);
    },
    'recuperação: token errado recusado; token do arquivo redefine a senha e é de uso único' => function () use ($setup): void {
        ['s' => $s] = $setup();
        $c = $s->client();
        $page = $c->get('/admin/recuperar');
        T::same(200, $page->status);
        T::same(200, $c->post('/admin/recuperar', ['_csrf' => Http::csrf($page->body), 'etapa' => 'gerar'])->status);
        $file = $s->dataDir . '/recovery-token.txt';
        T::true(is_file($file));
        $token = Fixtures::tokenFrom($file);
        $data = static fn (string $t) => ['_csrf' => Http::csrf($c->get('/admin/recuperar')->body), 'etapa' => 'redefinir', 'token' => $t, 'username' => Fixtures::ADMIN_USER, 'password' => 'nova senha recuperada 9', 'password_confirm' => 'nova senha recuperada 9'];
        T::same(403, $c->post('/admin/recuperar', $data(str_repeat('a', 32)))->status);
        $r = $c->post('/admin/recuperar', $data($token));
        T::same(200, $r->status);
        T::contains('Senha redefinida', $r->body);
        T::false(is_file($file), 'token apagado');
        T::same(403, $c->post('/admin/recuperar', $data($token))->status, 'uso único');
        Fixtures::login($s, Fixtures::ADMIN_USER, 'nova senha recuperada 9');
    },
    'recuperação: tentativas limitadas por IP' => function () use ($setup): void {
        ['s' => $s] = $setup();
        $c = $s->client();
        $last = null;
        for ($i = 0; $i < 7; $i++) {
            $page = $c->get('/admin/recuperar');
            $last = $c->post('/admin/recuperar', ['_csrf' => Http::csrf($page->body), 'etapa' => 'redefinir', 'token' => str_repeat('b', 32), 'username' => 'x', 'password' => 'p', 'password_confirm' => 'p']);
        }
        T::same(429, $last->status);
    },
];
