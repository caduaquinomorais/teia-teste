<?php

declare(strict_types=1);

namespace Teia\Http\Controller;

defined('TEIA_ROOT') || exit;

use Teia\Cms\ConflictException;
use Teia\Cms\Render;
use Teia\Cms\Schema;
use Teia\Cms\UploadException;
use Teia\Http\HttpException;
use Teia\Http\Request;
use Teia\Http\Response;

/** Edição de conteúdo (formulários gerados a partir das definições em app/content). */
final class AdminPagesController extends Controller
{
    public function edit(Request $request, array $params): Response
    {
        [$user, $page] = $this->authorize($request, $params['slug']);
        $current = $this->app->content()->current($page['slug']);
        return $this->form($user, $page, Schema::resolve($page, $current['data']), $current['version'], []);
    }

    public function save(Request $request, array $params): Response
    {
        [$user, $page] = $this->authorize($request, $params['slug']);
        $expected = (int) $request->str('version', '-1');

        // 1) Uploads (processados antes, para não se perderem se outro campo tiver erro).
        $errors = [];
        $uploads = [];
        foreach ($this->uploadedFiles($request) as $path => $file) {
            $field = Schema::fieldAt($page, $path);
            if ($field === null || !in_array($field['type'], ['image', 'file'], true)) {
                continue; // Lista branca: caminhos desconhecidos são ignorados.
            }
            try {
                $uploads[$path] = $this->app->media()->store($file, $field['type'], (int) $user['id']);
            } catch (UploadException $ex) {
                $errors[$path] = '“' . $field['label'] . '”: ' . $ex->getMessage();
            }
        }
        $removes = [];
        foreach ((array) ($request->post['r'] ?? []) as $path => $flag) {
            if (is_string($path) && $flag === '1') {
                $removes[$path] = true;
            }
        }

        // 2) Validação no servidor (somente campos declarados).
        $input = is_array($request->post['c'] ?? null) ? $request->post['c'] : [];
        [$data, $fieldErrors] = Schema::normalize($page, $input, $uploads, $removes, fn (string $ref) => $this->app->media()->refExists($ref));
        $errors += $fieldErrors;

        if ($errors !== []) {
            return $this->form($user, $page, $data, $expected, $errors, 422);
        }

        // 3) Publicação imediata com controle otimista de concorrência.
        try {
            $version = $this->app->content()->save($page['slug'], $data, $expected, (int) $user['id']);
        } catch (ConflictException $ex) {
            $this->audit($user, 'pagina.conflito', 'pagina:' . $page['slug'], ['esperada' => $expected, 'atual' => $ex->currentVersion]);
            return $this->form($user, $page, $data, $expected, [], 409, $ex->currentVersion);
        }
        $this->audit($user, 'pagina.publicada', 'pagina:' . $page['slug'], ['versao' => $version]);
        $this->flash('success', 'Alterações publicadas (versão ' . $version . ').');
        return $this->redirect('/admin/paginas/' . $page['slug']);
    }

    public function history(Request $request, array $params): Response
    {
        [$user, $page] = $this->authorize($request, $params['slug']);
        $current = $this->app->content()->current($page['slug']);
        return $this->adminView('admin/history', [
            'page' => $page,
            'revisions' => $this->app->content()->revisions($page['slug']),
            'currentVersion' => $current['version'],
        ], $user);
    }

    public function restore(Request $request, array $params): Response
    {
        [$user, $page] = $this->authorize($request, $params['slug']);
        $version = (int) $params['version'];
        $expected = (int) $request->str('current_version', '-1');
        $repo = $this->app->content();
        $revision = $repo->revision($page['slug'], $version);
        if ($revision === null) {
            throw HttpException::notFound();
        }
        // Revalida a revisão contra a definição atual (campos removidos são descartados).
        $data = Schema::resolve($page, $revision['data']);
        try {
            $new = $repo->save($page['slug'], $data, $expected, (int) $user['id'], 'Restauração da versão ' . $version);
        } catch (ConflictException $ex) {
            $this->flash('error', 'A página foi alterada por outra pessoa enquanto você consultava o histórico (versão atual: ' . $ex->currentVersion . '). Confira antes de restaurar.');
            return $this->adminView('admin/history', [
                'page' => $page,
                'revisions' => $repo->revisions($page['slug']),
                'currentVersion' => $ex->currentVersion,
            ], $user, 409);
        }
        $this->audit($user, 'pagina.restaurada', 'pagina:' . $page['slug'], ['de' => $version, 'nova' => $new]);
        $this->flash('success', 'Versão ' . $version . ' restaurada como versão ' . $new . '.');
        return $this->redirect('/admin/paginas/' . $page['slug']);
    }

    // ------------------------------------------------------------------

    /** @return array{0:array,1:array} [usuário, definição da página] */
    private function authorize(Request $request, string $slug): array
    {
        $user = $this->requireUser($request);
        $page = $this->app->pages()->get($slug);
        if ($page === null) {
            throw HttpException::notFound();
        }
        if (!$this->app->auth()->canEditPage($user, $slug)) {
            $this->audit($user, 'acesso.negado', 'pagina:' . $slug, ['metodo' => $request->method]);
            throw HttpException::forbidden('Você não tem permissão para editar esta página.');
        }
        return [$user, $page];
    }

    private function form(array $user, array $page, array $values, int $version, array $errors, int $status = 200, ?int $conflictVersion = null): Response
    {
        return $this->adminView('admin/page-edit', [
            'page' => $page,
            'values' => $values,
            'version' => $version,
            'errors' => $errors,
            'conflictVersion' => $conflictVersion,
            'h' => new Render($this->app->media()),
        ], $user, $status);
    }

    /**
     * Arquivos enviados em u[caminho]. Retorna caminho => arquivo no formato de $_FILES.
     * @return array<string,array>
     */
    private function uploadedFiles(Request $request): array
    {
        $u = $request->files['u'] ?? null;
        if (!is_array($u) || !is_array($u['name'] ?? null)) {
            return [];
        }
        $out = [];
        foreach ($u['name'] as $path => $name) {
            if (!is_string($path) || !preg_match('/^[a-z0-9_.]{1,200}$/', $path) || is_array($name)) {
                continue;
            }
            $error = (int) ($u['error'][$path] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[$path] = [
                'name' => (string) $name,
                'tmp_name' => (string) ($u['tmp_name'][$path] ?? ''),
                'error' => $error,
                'size' => (int) ($u['size'][$path] ?? 0),
            ];
        }
        return $out;
    }
}
