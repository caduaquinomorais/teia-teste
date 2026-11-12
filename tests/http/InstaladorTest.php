<?php

declare(strict_types=1);

use Teia\Tests\Fixtures;
use Teia\Tests\Http;
use Teia\Tests\Server;
use Teia\Tests\T;

use function Teia\Tests\rmtree;

$conclude = static fn (Http $c, array $over = []): \Teia\Tests\Res => $c->post('/instalar', $over + [
    'etapa' => 'concluir',
    '_csrf' => Http::csrf($c->get('/instalar')->body),
    'name' => 'Admin',
    'username' => 'admin.teia',
    'password' => 'uma frase bem longa 42',
    'password_confirm' => 'uma frase bem longa 42',
]);

return [
    'verifica requisitos e prepara estrutura (chave, banco, token)' => function (): void {
        $s = new Server();
        $c = $s->client();
        $page = $c->get('/instalar');
        T::same(200, $page->status);
        foreach (['pdo_sqlite', 'mbstring', 'fileinfo', 'openssl', 'gd', 'zip', 'sodium', 'SQLite', 'Diretório de dados gravável', 'fora da área pública'] as $check) {
            T::contains($check, $page->body);
        }
        T::same(303, $c->post('/instalar', ['etapa' => 'preparar'])->status);
        foreach (['app.key', 'install-token.txt', 'database/teia.sqlite', 'uploads', 'backups', 'logs', 'sessions', 'tmp'] as $p) {
            T::true(file_exists($s->dataDir . '/' . $p), "criou $p");
        }
        T::notContains($s->dataDir, $page->body, 'não expõe o caminho completo');
    },
    'token incorreto é recusado e tentativas são limitadas' => function () use ($conclude): void {
        $s = new Server();
        $c = $s->client();
        $c->post('/instalar', ['etapa' => 'preparar']);
        for ($i = 0; $i < 5; $i++) {
            T::same(403, $conclude($c, ['token' => str_repeat('0', 32)])->status, "tentativa $i");
        }
        $token = Fixtures::tokenFrom($s->dataDir . '/install-token.txt');
        T::same(429, $conclude($c, ['token' => $token])->status, 'bloqueado mesmo com o token certo');
    },
    'sem token CSRF válido, nada acontece' => function (): void {
        $s = new Server();
        $c = $s->client();
        $c->post('/instalar', ['etapa' => 'preparar']);
        $token = Fixtures::tokenFrom($s->dataDir . '/install-token.txt');
        $r = $c->post('/instalar', ['etapa' => 'concluir', '_csrf' => 'invalido', 'token' => $token, 'name' => 'A', 'username' => 'admin.teia', 'password' => 'uma frase bem longa 42', 'password_confirm' => 'uma frase bem longa 42']);
        T::same(403, $r->status);
    },
    'Origin de outro site é recusado' => function (): void {
        $s = new Server();
        T::same(403, $s->client()->post('/instalar', ['etapa' => 'preparar'], ['Origin' => 'https://evil.example'])->status);
    },
    'senha fraca e senha com o nome de usuário são recusadas' => function () use ($conclude): void {
        $s = new Server();
        $c = $s->client();
        $c->post('/instalar', ['etapa' => 'preparar']);
        $token = Fixtures::tokenFrom($s->dataDir . '/install-token.txt');
        $r = $conclude($c, ['token' => $token, 'password' => 'senha123456!', 'password_confirm' => 'senha123456!']);
        T::same(422, $r->status);
        $r = $conclude($c, ['token' => $token, 'password' => 'admin.teia-2026-xyz', 'password_confirm' => 'admin.teia-2026-xyz']);
        T::same(422, $r->status);
        T::contains('nome de usuário', $r->body);
    },
    'instalação concluída: token apagado e /instalar vira 404 permanente' => function (): void {
        $s = Fixtures::installedServer();
        $c = $s->client();
        T::false(is_file($s->dataDir . '/install-token.txt'));
        T::same(404, $c->get('/instalar')->status);
        T::same(404, $c->post('/instalar', ['etapa' => 'preparar'])->status);
        T::same(404, $c->post('/instalar', ['etapa' => 'concluir'])->status);
    },
    'recusa diretório de dados dentro da área pública' => function (): void {
        $inside = TEIA_ROOT . '/public/dados-teste-' . bin2hex(random_bytes(3));
        try {
            $s = new Server($inside);
            $c = $s->client();
            $page = $c->get('/instalar');
            T::contains('FALHOU', $page->body);
            T::contains('dentro da pasta pública', $page->body);
            T::same(422, $c->post('/instalar', ['etapa' => 'preparar'])->status);
            T::false(is_file($inside . '/app.key'));
            $s->stop();
        } finally {
            rmtree($inside);
        }
    },
];
