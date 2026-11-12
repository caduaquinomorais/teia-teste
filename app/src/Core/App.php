<?php

declare(strict_types=1);

namespace Teia\Core;

defined('TEIA_ROOT') || exit;

use Teia\Auth\Audit;
use Teia\Auth\Auth;
use Teia\Auth\SessionManager;
use Teia\Auth\UserRepository;
use Teia\Backup\BackupService;
use Teia\Cms\ContentRepository;
use Teia\Cms\MediaService;
use Teia\Cms\PageRegistry;
use Teia\Cms\Schema;
use Teia\Database\Database;
use Teia\Database\Migrator;
use Teia\Http\Controller\AdminBackupsController;
use Teia\Http\Controller\AdminController;
use Teia\Http\Controller\AdminPagesController;
use Teia\Http\Controller\AdminUsersController;
use Teia\Http\Controller\InstallController;
use Teia\Http\Controller\SiteController;
use Teia\Http\HttpException;
use Teia\Http\Request;
use Teia\Http\Response;
use Teia\Http\Router;
use Teia\Install\Installer;
use Teia\Security\Keys;
use Teia\Security\RateLimiter;
use Teia\Security\SecurityHeaders;

/** Aplicação: monta os serviços, registra as rotas e trata erros de forma centralizada. */
final class App
{
    private static ?App $current = null;

    public readonly Config $config;
    public readonly Logger $logger;
    public readonly View $view;

    private ?Database $db = null;
    private ?Keys $keys = null;
    private ?PageRegistry $pages = null;
    private ?MediaService $media = null;
    private ?SessionManager $session = null;
    private ?Auth $auth = null;
    private ?bool $installed = null;
    private ?Request $request = null;
    private string $nonce = '';
    private string $basePath = '';
    private bool $mapFrames = false;

    public function __construct(public readonly Paths $paths, ?Config $config = null)
    {
        $this->config = $config ?? Config::load($paths);
        $this->logger = new Logger($paths->logs());
        $this->view = new View($paths->app . '/views');
        self::$current = $this;
    }

    public static function instance(): ?self
    {
        return self::$current;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function cspNonce(): string
    {
        return $this->nonce;
    }

    public function request(): Request
    {
        if ($this->request === null) {
            throw new \LogicException('Sem requisição ativa.');
        }
        return $this->request;
    }

    public function https(): bool
    {
        return (bool) $this->config->get('force_https') || ($this->request?->isHttps($this->trustedProxies()) ?? false);
    }

    public function trustedProxies(): array
    {
        return (array) $this->config->get('trusted_proxies', []);
    }

    public function clientIp(): string
    {
        return $this->request()->ip($this->trustedProxies());
    }

    /** Libera o iframe do Google Maps na CSP desta resposta. */
    public function allowMapFrames(): void
    {
        $this->mapFrames = true;
    }

    // ------------------------------------------------------------------
    // Serviços
    // ------------------------------------------------------------------

    public function installer(): Installer
    {
        return new Installer($this->paths, (string) ($this->request?->server['DOCUMENT_ROOT'] ?? ($_SERVER['DOCUMENT_ROOT'] ?? '')));
    }

    public function isInstalled(): bool
    {
        return $this->installed ??= $this->installer()->isInstalled();
    }

    public function markInstalled(): void
    {
        $this->installed = true;
    }

    public function migrator(): Migrator
    {
        return new Migrator($this->db(), $this->paths->app . '/migrations');
    }

    public function db(): Database
    {
        if ($this->db !== null) {
            return $this->db;
        }
        $this->db = new Database($this->paths->database());
        // Migrações pendentes rodam automaticamente, com backup de segurança antes.
        $migrator = new Migrator($this->db, $this->paths->app . '/migrations');
        if ($this->db->userVersion() > 0 && $migrator->pending() !== []) {
            $migrator->migrate(function (int $from, int $to): void {
                $name = $this->backups()->create('pre-migracao');
                $this->logger->info("Migração $from → $to; backup $name");
            });
        }
        return $this->db;
    }

    public function closeDb(): void
    {
        $this->db = null;
        $this->media = null;
        $this->auth = null;
        gc_collect_cycles();
    }

    public function keys(): Keys
    {
        return $this->keys ??= Keys::fromFile($this->paths->keyFile());
    }

    public function pages(): PageRegistry
    {
        return $this->pages ??= new PageRegistry($this->paths->app . '/content');
    }

    public function content(): ContentRepository
    {
        return new ContentRepository($this->db());
    }

    public function media(): MediaService
    {
        return $this->media ??= new MediaService(
            $this->db(),
            $this->paths->uploads(),
            $this->paths->public . '/assets',
            $this->config->int('upload_max_image_bytes'),
            $this->config->int('upload_max_pdf_bytes'),
            $this->config->int('image_max_width'),
            $this->config->int('image_max_pixels'),
        );
    }

    public function users(): UserRepository
    {
        return new UserRepository($this->db());
    }

    public function audit(): Audit
    {
        return new Audit($this->db(), $this->keys());
    }

    public function limiter(): RateLimiter
    {
        return new RateLimiter($this->db(), $this->keys());
    }

    public function backups(): BackupService
    {
        $db = $this->db();
        $latest = (new Migrator($db, $this->paths->app . '/migrations'))->latestVersion();
        return new BackupService(
            $db,
            $this->paths->database(),
            $this->paths->uploads(),
            $this->paths->backups(),
            $this->paths->tmp(),
            $latest,
        );
    }

    public function session(): SessionManager
    {
        return $this->session ??= new SessionManager(
            $this->paths->sessions(),
            ($this->basePath === '' ? '' : $this->basePath) . '/admin',
            $this->https(),
            $this->config->int('session_idle_timeout', 1800),
            $this->config->int('session_absolute_timeout', 28800),
        );
    }

    public function auth(): Auth
    {
        return $this->auth ??= new Auth(
            $this->users(),
            $this->session(),
            $this->limiter(),
            $this->audit(),
            $this->config->int('login_max_per_user', 5),
            $this->config->int('login_max_per_ip', 20),
            $this->config->int('login_window', 900),
        );
    }

    /**
     * Conteúdo final de uma página (padrões do Figma + valores publicados).
     * Antes da instalação (ou se o banco falhar), usa apenas os padrões.
     */
    public function pageContent(string $slug): array
    {
        $page = $this->pages()->get($slug);
        if ($page === null) {
            throw new \InvalidArgumentException('Página inexistente: ' . $slug);
        }
        $stored = null;
        if ($this->isInstalled()) {
            try {
                $stored = $this->content()->current($slug)['data'];
            } catch (\Throwable $ex) {
                $this->logger->error('Falha ao ler conteúdo; usando padrões.', ['slug' => $slug, 'erro' => $ex->getMessage()]);
            }
        }
        return Schema::resolve($page, $stored);
    }

    // ------------------------------------------------------------------
    // Ciclo da requisição
    // ------------------------------------------------------------------

    public function handle(Request $request): Response
    {
        self::$current = $this;
        $this->request = $request;
        $this->basePath = $request->basePath;
        $this->nonce = SecurityHeaders::nonce();
        $this->mapFrames = false;
        $this->session = null;
        $this->auth = null;
        $this->view->share('app', $this);

        try {
            $router = new Router();
            $this->routes($router);
            $match = $router->match($request->method, $request->path);
            $response = ($match['handler'])($request, $match['params']);
        } catch (HttpException $ex) {
            $response = $ex->status >= 300 && $ex->status < 400
                ? new Response('', $ex->status)
                : $this->errorResponse($ex->status, $ex->getMessage(), $request);
            foreach ($ex->headers as $name => $value) {
                $response->header($name, $value);
            }
        } catch (\Throwable $ex) {
            $ref = strtoupper(bin2hex(random_bytes(4)));
            $this->logger->error('Erro não tratado', [
                'ref' => $ref,
                'tipo' => get_class($ex),
                'mensagem' => $ex->getMessage(),
                'arquivo' => $ex->getFile() . ':' . $ex->getLine(),
                'rota' => $request->method . ' ' . $request->path,
                'trace' => array_slice(explode("\n", $ex->getTraceAsString()), 0, 12),
            ]);
            $response = $this->errorResponse(500, '', $request, $ref);
        }

        if (str_starts_with($request->path, '/admin') || str_starts_with($request->path, '/instalar')) {
            $response->header('Cache-Control', 'no-store');
            $response->header('X-Robots-Tag', 'noindex, nofollow');
        }
        SecurityHeaders::apply($response, $this->nonce, $this->https(), $this->mapFrames);
        if ($request->method === 'HEAD') {
            $response = new Response('', $response->status(), $response->headers());
        }
        return $response;
    }

    private function routes(Router $r): void
    {
        $site = new SiteController($this);
        $install = new InstallController($this);
        $admin = new AdminController($this);
        $pages = new AdminPagesController($this);
        $users = new AdminUsersController($this);
        $backups = new AdminBackupsController($this);

        $r->get('/', [$site, 'home']);
        $r->get('/midia/{file:[a-f0-9]{32}\.(?:webp|jpg|png|pdf)}', [$site, 'media']);

        $r->get('/instalar', [$install, 'show']);
        $r->post('/instalar', [$install, 'submit']);

        $r->get('/cron/backup', [$backups, 'cron']);

        $r->get('/admin', [$admin, 'dashboard']);
        $r->get('/admin/login', [$admin, 'loginForm']);
        $r->post('/admin/login', [$admin, 'login']);
        $r->post('/admin/sair', [$admin, 'logout']);
        $r->get('/admin/senha', [$admin, 'passwordForm']);
        $r->post('/admin/senha', [$admin, 'passwordSave']);
        $r->get('/admin/recuperar', [$admin, 'recoverForm']);
        $r->post('/admin/recuperar', [$admin, 'recover']);
        $r->get('/admin/auditoria', [$admin, 'auditLog']);

        $slug = '{slug:[a-z0-9-]{1,40}}';
        $r->get("/admin/paginas/$slug", [$pages, 'edit']);
        $r->post("/admin/paginas/$slug", [$pages, 'save']);
        $r->get("/admin/paginas/$slug/historico", [$pages, 'history']);
        $r->post("/admin/paginas/$slug/restaurar/{version:[0-9]{1,9}}", [$pages, 'restore']);

        $r->get('/admin/usuarios', [$users, 'index']);
        $r->get('/admin/usuarios/novo', [$users, 'createForm']);
        $r->post('/admin/usuarios/novo', [$users, 'create']);
        $r->get('/admin/usuarios/{id:[0-9]{1,9}}', [$users, 'edit']);
        $r->post('/admin/usuarios/{id:[0-9]{1,9}}', [$users, 'update']);
        $r->post('/admin/usuarios/{id:[0-9]{1,9}}/senha', [$users, 'resetPassword']);
        $r->post('/admin/usuarios/{id:[0-9]{1,9}}/status', [$users, 'toggleActive']);

        $r->get('/admin/backups', [$backups, 'index']);
        $r->post('/admin/backups/criar', [$backups, 'create']);
        $r->post('/admin/backups/configurar', [$backups, 'settings']);
        $r->get('/admin/backups/baixar/{name:backup-[0-9-]+-[a-z-]+-[a-f0-9]{6}\.zip}', [$backups, 'download']);
        $r->post('/admin/backups/restaurar', [$backups, 'restore']);
        $r->post('/admin/backups/excluir', [$backups, 'delete']);
    }

    /** Resposta de erro: página 404 do site para visitantes; telas genéricas para os demais casos. */
    public function errorResponse(int $status, string $message, Request $request, ?string $ref = null): Response
    {
        $isAdmin = str_starts_with($request->path, '/admin') || str_starts_with($request->path, '/instalar');
        try {
            if ($status === 404 && !$isAdmin) {
                return Response::html((new SiteController($this))->renderNotFound(), 404);
            }
            $titles = [
                400 => 'Requisição inválida',
                403 => 'Acesso negado',
                404 => 'Página não encontrada',
                405 => 'Método não permitido',
                409 => 'Conflito',
                413 => 'Arquivo grande demais',
                429 => 'Muitas tentativas',
                500 => 'Erro interno',
                503 => 'Serviço indisponível',
            ];
            $html = $this->view->render('error', [
                'status' => $status,
                'title' => $titles[$status] ?? 'Erro',
                'message' => $status >= 500 ? 'Ocorreu um erro inesperado. Tente novamente em instantes.' : $message,
                'ref' => $ref,
                'isAdmin' => $isAdmin,
            ]);
            return Response::html($html, $status);
        } catch (\Throwable $ex) {
            $this->logger->error('Falha ao renderizar página de erro', ['erro' => $ex->getMessage()]);
            $text = $status >= 500 ? 'Erro interno.' . ($ref ? ' Código de referência: ' . $ref : '') : 'Erro ' . $status . '.';
            return new Response(e($text), $status, ['Content-Type' => 'text/html; charset=UTF-8']);
        }
    }
}
