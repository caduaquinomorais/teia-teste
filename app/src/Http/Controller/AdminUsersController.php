<?php

declare(strict_types=1);

namespace Teia\Http\Controller;

defined('TEIA_ROOT') || exit;

use Teia\Auth\UserRepository;
use Teia\Database\Database;
use Teia\Http\HttpException;
use Teia\Http\Request;
use Teia\Http\Response;
use Teia\Security\PasswordPolicy;

/** Gestão de usuários (somente administradores). */
final class AdminUsersController extends Controller
{
    public function index(Request $request): Response
    {
        $admin = $this->requireAdmin($request);
        $users = $this->app->users();
        $list = [];
        foreach ($users->all() as $u) {
            $u['permissions'] = $users->pagePermissions((int) $u['id']);
            $list[] = $u;
        }
        return $this->adminView('admin/users', ['list' => $list], $admin);
    }

    public function createForm(Request $request): Response
    {
        $admin = $this->requireAdmin($request);
        return $this->formView($admin, null, ['role' => 'editor', 'permissions' => []], []);
    }

    public function create(Request $request): Response
    {
        $admin = $this->requireAdmin($request);
        $input = $this->input($request);
        $errors = $this->validate($input);
        $users = $this->app->users();
        if ($users->findByUsername($input['username']) !== null) {
            $errors[] = 'Já existe um usuário com esse nome de acesso.';
        }
        if ($errors !== []) {
            return $this->formView($admin, null, $input, $errors, 422);
        }
        $temporary = PasswordPolicy::generateTemporary();
        $id = (int) $this->app->db()->transaction(function () use ($users, $input, $temporary, $admin): int {
            $id = $users->create($input['username'], $input['name'], $input['email'], $temporary, $input['role'], true);
            $users->setPagePermissions($id, $input['role'] === 'admin' ? [] : $input['permissions'], (int) $admin['id']);
            return $id;
        });
        $this->audit($admin, 'usuario.criado', 'usuario:' . $id, ['papel' => $input['role'], 'paginas' => $input['permissions']]);
        return $this->adminView('admin/temp-password', [
            'target' => $users->find($id),
            'temporary' => $temporary,
            'reason' => 'Conta criada.',
        ], $admin);
    }

    public function edit(Request $request, array $params): Response
    {
        $admin = $this->requireAdmin($request);
        $target = $this->target((int) $params['id']);
        $input = [
            'username' => $target['username'],
            'name' => $target['name'],
            'email' => (string) $target['email'],
            'role' => $target['role'],
            'permissions' => $this->app->users()->pagePermissions((int) $target['id']),
        ];
        return $this->formView($admin, $target, $input, []);
    }

    public function update(Request $request, array $params): Response
    {
        $admin = $this->requireAdmin($request);
        $target = $this->target((int) $params['id']);
        $input = $this->input($request);
        $input['username'] = $target['username']; // nome de acesso não muda
        $errors = $this->validate($input, false);
        $users = $this->app->users();

        if ($target['role'] === 'admin' && $input['role'] !== 'admin' && (int) $target['active'] === 1 && $users->countActiveAdmins() <= 1) {
            $errors[] = 'Esta é a última conta de administrador ativa e não pode deixar de ser administradora.';
        }
        if ($errors !== []) {
            return $this->formView($admin, $target, $input, $errors, 422);
        }
        [$granted, $revoked] = $this->app->db()->transaction(function () use ($users, $target, $input, $admin): array {
            $users->updateProfile((int) $target['id'], $input['name'], $input['email'], $input['role']);
            $result = $users->setPagePermissions((int) $target['id'], $input['role'] === 'admin' ? [] : $input['permissions'], (int) $admin['id']);
            if ($target['role'] !== $input['role']) {
                // Mudança de papel encerra as sessões abertas do usuário.
                $users->bumpSessionVersion((int) $target['id']);
            }
            return $result;
        });
        $this->audit($admin, 'usuario.atualizado', 'usuario:' . $target['id'], [
            'papel' => $input['role'],
            'concedidas' => $granted,
            'revogadas' => $revoked,
        ]);
        if ((int) $target['id'] === (int) $admin['id']) {
            $this->app->auth()->refreshSessionVersion();
        }
        $this->flash('success', 'Usuário atualizado.' . ($revoked !== [] ? ' As sessões abertas dessa pessoa foram encerradas.' : ''));
        return $this->redirect('/admin/usuarios');
    }

    public function resetPassword(Request $request, array $params): Response
    {
        $admin = $this->requireAdmin($request);
        $target = $this->target((int) $params['id']);
        if ((int) $target['id'] === (int) $admin['id']) {
            throw HttpException::forbidden('Para trocar a sua própria senha, use “Minha senha”.');
        }
        $temporary = PasswordPolicy::generateTemporary();
        $this->app->users()->setPassword((int) $target['id'], $temporary, true);
        $this->audit($admin, 'usuario.senha_redefinida', 'usuario:' . $target['id']);
        return $this->adminView('admin/temp-password', [
            'target' => $this->app->users()->find((int) $target['id']),
            'temporary' => $temporary,
            'reason' => 'Senha redefinida. As sessões abertas dessa pessoa foram encerradas.',
        ], $admin);
    }

    public function toggleActive(Request $request, array $params): Response
    {
        $admin = $this->requireAdmin($request);
        $target = $this->target((int) $params['id']);
        $activate = $request->str('ativo') === '1';
        if (!$activate) {
            if ((int) $target['id'] === (int) $admin['id']) {
                throw HttpException::forbidden('Você não pode desativar a sua própria conta.');
            }
            $blocked = $this->app->db()->transaction(function (Database $db) use ($target): bool {
                $users = new UserRepository($db);
                if ($target['role'] === 'admin' && $users->countActiveAdmins() <= 1) {
                    return true;
                }
                $users->setActive((int) $target['id'], false);
                return false;
            });
            if ($blocked) {
                throw HttpException::forbidden('Não é possível desativar o último administrador ativo.');
            }
            $this->audit($admin, 'usuario.desativado', 'usuario:' . $target['id']);
            $this->flash('success', 'Conta desativada. As sessões abertas foram encerradas.');
        } else {
            $this->app->users()->setActive((int) $target['id'], true);
            $this->audit($admin, 'usuario.reativado', 'usuario:' . $target['id']);
            $this->flash('success', 'Conta reativada.');
        }
        return $this->redirect('/admin/usuarios');
    }

    // ------------------------------------------------------------------

    private function target(int $id): array
    {
        $target = $this->app->users()->find($id);
        if ($target === null) {
            throw HttpException::notFound();
        }
        return $target;
    }

    private function input(Request $request): array
    {
        $role = $request->str('role') === 'admin' ? 'admin' : 'editor';
        $valid = $this->app->pages()->slugs();
        $permissions = [];
        foreach ((array) ($request->post['permissions'] ?? []) as $slug) {
            if (is_string($slug) && in_array($slug, $valid, true)) {
                $permissions[] = $slug; // lista branca de páginas
            }
        }
        return [
            'username' => mb_strtolower(trim($request->str('username')), 'UTF-8'),
            'name' => trim($request->str('name')),
            'email' => trim($request->str('email')),
            'role' => $role,
            'permissions' => array_values(array_unique($permissions)),
        ];
    }

    private function validate(array $input, bool $checkUsername = true): array
    {
        $errors = [];
        if ($checkUsername && !UserRepository::validUsername($input['username'])) {
            $errors[] = 'Nome de acesso: de 3 a 40 caracteres (letras minúsculas, números, ponto, hífen ou sublinhado).';
        }
        if ($input['name'] === '' || mb_strlen($input['name']) > 100) {
            $errors[] = 'Informe o nome (até 100 caracteres).';
        }
        if ($input['email'] !== '' && filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'E-mail inválido.';
        }
        return $errors;
    }

    private function formView(array $admin, ?array $target, array $input, array $errors, int $status = 200): Response
    {
        return $this->adminView('admin/user-form', [
            'target' => $target,
            'input' => $input + ['username' => '', 'name' => '', 'email' => ''],
            'errors' => $errors,
            'isLastAdmin' => $target !== null && $target['role'] === 'admin' && $this->app->users()->countActiveAdmins() <= 1,
        ], $admin, $status);
    }
}
