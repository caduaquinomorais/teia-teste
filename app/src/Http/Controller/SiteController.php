<?php

declare(strict_types=1);

namespace Teia\Http\Controller;

defined('TEIA_ROOT') || exit;

use Teia\Cms\Render;
use Teia\Http\HttpException;
use Teia\Http\Request;
use Teia\Http\Response;

/** Site público do TEIA. Nunca inicia sessão (visitantes não recebem cookies). */
final class SiteController extends Controller
{
    public function home(Request $request): Response
    {
        $this->app->allowMapFrames();
        $html = $this->app->view->render('site/teia', $this->siteData() + [
            'c' => $this->app->pageContent('teia'),
        ]);
        $response = Response::html($html);
        $response->header('Cache-Control', 'no-cache');
        return $response;
    }

    public function renderNotFound(): string
    {
        return $this->app->view->render('site/404', $this->siteData() + [
            'c' => $this->app->pageContent('erro-404'),
            'teia' => $this->app->pageContent('teia'),
        ]);
    }

    /** Serve arquivos enviados pelo painel (fora de public/), com cabeçalhos restritivos. */
    public function media(Request $request, array $params): Response
    {
        if (!$this->app->isInstalled()) {
            throw HttpException::notFound();
        }
        $file = $params['file'];
        $path = $this->app->media()->storedPath($file);
        if ($path === null) {
            throw HttpException::notFound();
        }
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $types = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];
        $headers = [
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ];
        if ($ext === 'pdf') {
            $media = $this->app->media()->find(substr($file, 0, 32));
            $name = $media['original_name'] ?? 'documento.pdf';
            $ascii = preg_replace('/[^A-Za-z0-9._-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: 'documento.pdf') ?: 'documento.pdf';
            $headers['Content-Disposition'] = 'attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name);
        }
        return Response::file($path, $types[$ext], $headers);
    }

    private function siteData(): array
    {
        $media = null;
        if ($this->app->isInstalled()) {
            try {
                $media = $this->app->media();
            } catch (\Throwable $ex) {
                $this->app->logger->error('Mídia indisponível', ['erro' => $ex->getMessage()]);
            }
        }
        return [
            'h' => new Render($media),
            'layout' => $this->app->pageContent('layout'),
        ];
    }
}
