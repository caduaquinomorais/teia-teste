<?php

declare(strict_types=1);

namespace Teia\Tests;

/** Cenários reutilizáveis dos testes HTTP. */
final class Fixtures
{
    public const ADMIN_USER = 'admin.teia';
    public const ADMIN_PASS = 'uma frase bem longa 42';

    /** Instala o site via HTTP (fluxo real do /instalar) e retorna o servidor. */
    public static function installedServer(): Server
    {
        $server = new Server();
        $http = $server->client();
        $r = $http->post('/instalar', ['etapa' => 'preparar']);
        T::same(303, $r->status, 'preparar');
        $token = self::tokenFrom($server->dataDir . '/install-token.txt');
        $page = $http->get('/instalar');
        $r = $http->post('/instalar', [
            'etapa' => 'concluir',
            '_csrf' => Http::csrf($page->body),
            'token' => $token,
            'name' => 'Admin Teia',
            'username' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
            'password_confirm' => self::ADMIN_PASS,
        ]);
        T::same(200, $r->status, 'concluir instalação');
        T::contains('Instalação concluída', $r->body);
        return $server;
    }

    public static function tokenFrom(string $file): string
    {
        foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (preg_match('/^[a-f0-9]{32}$/', trim($line))) {
                return trim($line);
            }
        }
        throw new AssertionFailed('Token não encontrado em ' . basename($file));
    }

    /** Faz login e retorna o cliente autenticado. */
    public static function login(Server $server, string $user = self::ADMIN_USER, string $pass = self::ADMIN_PASS): Http
    {
        $http = $server->client();
        $form = $http->get('/admin/login');
        $r = $http->post('/admin/login', ['_csrf' => Http::csrf($form->body), 'username' => $user, 'password' => $pass]);
        T::same(303, $r->status, "login de $user");
        return $http;
    }

    /** Página do painel + token CSRF. @return array{0:Res,1:string} */
    public static function page(Http $http, string $path): array
    {
        $r = $http->get($path);
        T::same(200, $r->status, "GET $path");
        return [$r, Http::csrf($r->body)];
    }

    /**
     * Cria um editor com as páginas indicadas, troca a senha temporária e retorna [cliente do editor, id].
     * @return array{0:Http,1:int}
     */
    public static function editor(Server $server, Http $admin, string $username, array $pages, string $finalPassword = 'editor senha forte 2026'): array
    {
        [, $csrf] = self::page($admin, '/admin/usuarios/novo');
        $r = $admin->post('/admin/usuarios/novo', [
            '_csrf' => $csrf, 'username' => $username, 'name' => 'Editor ' . $username, 'email' => '',
            'role' => 'editor', 'permissions' => $pages,
        ]);
        T::same(200, $r->status, 'criar editor');
        if (!preg_match('/credentials__secret">([^<]+)</', $r->body, $m)) {
            throw new AssertionFailed('Senha temporária não exibida.');
        }
        $temporary = html_entity_decode($m[1]);
        $list = $admin->get('/admin/usuarios')->body;
        preg_match_all('#<code>' . preg_quote($username, '#') . '</code>.*?/admin/usuarios/(\d+)#s', $list, $ids);
        $id = (int) ($ids[1][0] ?? 0);

        $ed = self::login($server, $username, $temporary);
        $r = $ed->get('/admin');
        T::same(303, $r->status, 'senha temporária força troca');
        T::contains('/admin/senha', $r->location());
        [, $csrf] = self::page($ed, '/admin/senha');
        $r = $ed->post('/admin/senha', ['_csrf' => $csrf, 'current_password' => $temporary, 'password' => $finalPassword, 'password_confirm' => $finalPassword]);
        T::same(303, $r->status, 'troca de senha temporária');
        return [$ed, $id];
    }

    /**
     * Monta o POST do formulário do CMS a partir da página de edição (todos os campos atuais),
     * permitindo sobrescrever valores pelo nome do input.
     */
    public static function cmsForm(string $html, array $overrides = []): array
    {
        $fields = [];
        // Remove os modelos <template> (não são enviados pelo navegador).
        $html = preg_replace('#<template\b.*?</template>#s', '', $html) ?? $html;
        preg_match_all('#<input\b[^>]*>#', $html, $inputs);
        foreach ($inputs[0] as $tag) {
            if (!preg_match('/name="([^"]+)"/', $tag, $n)) {
                continue;
            }
            $name = html_entity_decode($n[1]);
            $type = preg_match('/type="([^"]+)"/', $tag, $t) ? $t[1] : 'text';
            if ($type === 'file' || str_starts_with($name, 'r[')) {
                continue;
            }
            if ($type === 'checkbox' && !str_contains($tag, 'checked')) {
                continue;
            }
            $value = preg_match('/value="([^"]*)"/', $tag, $v) ? html_entity_decode($v[1], ENT_QUOTES | ENT_HTML5) : '';
            $fields[] = [$name, $value];
        }
        preg_match_all('#<textarea\b[^>]*name="([^"]+)"[^>]*>(.*?)</textarea>#s', $html, $areas, PREG_SET_ORDER);
        foreach ($areas as $a) {
            $fields[] = [html_entity_decode($a[1]), html_entity_decode($a[2], ENT_QUOTES | ENT_HTML5)];
        }
        // Converte para string de query preservando a ordem; últimos valores ganham (checkbox depois do hidden).
        $flat = [];
        foreach ($fields as [$name, $value]) {
            $flat[$name] = $value;
        }
        foreach ($overrides as $name => $value) {
            if ($value === null) {
                unset($flat[$name]);
            } else {
                $flat[$name] = $value;
            }
        }
        return $flat;
    }

    /** Converte o mapa nome=>valor (nomes com colchetes) em corpo urlencoded. */
    public static function encode(array $flat): string
    {
        $parts = [];
        foreach ($flat as $name => $value) {
            $parts[] = rawurlencode((string) $name) . '=' . rawurlencode((string) $value);
        }
        return implode('&', $parts);
    }
}
