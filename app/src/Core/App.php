<?php

declare(strict_types=1);

namespace Teia\Core;

defined('TEIA_ROOT') || exit;

use Teia\Http\Controller\SiteController;
use Teia\Http\HttpException;
use Teia\Http\Request;
use Teia\Http\Response;
use Teia\Http\Router;
use Teia\Security\SecurityHeaders;

/** Aplicação: rotas do site público e tratamento centralizado de erros. */
final class App
{
    private static ?App $current = null;

    public readonly Config $config;
    public readonly Logger $logger;
    public readonly View $view;
    private ?Request $request = null;
    private string $nonce = '';
    private string $basePath = '';
    private bool $mapFrames = false;

    public function __construct(?Config $config = null, private readonly ?string $contentDir = null)
    {
        $this->config = $config ?? Config::load(TEIA_ROOT . '/app/config');
        $this->logger = new Logger();
        $this->view = new View(TEIA_ROOT . '/app/views');
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

    public function https(): bool
    {
        return (bool) $this->config->get('force_https') || ($this->request?->isHttps((array) $this->config->get('trusted_proxies', [])) ?? false);
    }

    /** Libera o iframe do Google Maps na CSP desta resposta. */
    public function allowMapFrames(): void
    {
        $this->mapFrames = true;
    }

    /**
     * Conteúdo de uma página (arquivo em app/content).
     * A variável de ambiente TEIA_CONTENT_DIR troca a pasta (usada só pelo teste visual).
     */
    public function content(string $name): array
    {
        if (!preg_match('/^[a-z0-9-]{1,40}$/', $name)) {
            throw new \InvalidArgumentException('Nome de conteúdo inválido.');
        }
        $dir = $this->contentDir ?? (getenv('TEIA_CONTENT_DIR') ?: TEIA_ROOT . '/app/content');
        $data = require $dir . '/' . $name . '.php';
        if (!is_array($data)) {
            throw new \RuntimeException('Conteúdo inválido: ' . $name);
        }
        return $data;
    }

    public function handle(Request $request): Response
    {
        self::$current = $this;
        $this->request = $request;
        $this->basePath = $request->basePath;
        $this->nonce = SecurityHeaders::nonce();
        $this->mapFrames = false;

        try {
            $site = new SiteController($this);
            $router = new Router();
            $router->get('/', [$site, 'home']);
            $match = $router->match($request->method, $request->path);
            $response = ($match['handler'])($request, $match['params']);
        } catch (HttpException $ex) {
            $response = $this->errorResponse($ex->status, $ex->getMessage());
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
            ]);
            $response = $this->errorResponse(500, '', $ref);
        }

        SecurityHeaders::apply($response, $this->nonce, $this->https(), $this->mapFrames);
        if ($request->method === 'HEAD') {
            $response = new Response('', $response->status(), $response->headers());
        }
        return $response;
    }

    /** 404 usa a página do site; os demais erros, uma página genérica (sem detalhes internos). */
    private function errorResponse(int $status, string $message, ?string $ref = null): Response
    {
        try {
            if ($status === 404) {
                return Response::html((new SiteController($this))->renderNotFound(), 404);
            }
            $titles = [400 => 'Requisição inválida', 405 => 'Método não permitido', 500 => 'Erro interno'];
            return Response::html($this->view->render('error', [
                'status' => $status,
                'title' => $titles[$status] ?? 'Erro',
                'message' => $status >= 500 ? 'Ocorreu um erro inesperado. Tente novamente em instantes.' : $message,
                'ref' => $ref,
            ]), $status);
        } catch (\Throwable $ex) {
            $this->logger->error('Falha ao renderizar página de erro', ['erro' => $ex->getMessage()]);
            $text = $status >= 500 ? 'Erro interno.' . ($ref ? ' Código de referência: ' . $ref : '') : 'Erro ' . $status . '.';
            return new Response(e($text), $status, ['Content-Type' => 'text/html; charset=UTF-8']);
        }
    }
}
