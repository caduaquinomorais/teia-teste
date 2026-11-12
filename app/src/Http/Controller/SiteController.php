<?php

declare(strict_types=1);

namespace Teia\Http\Controller;

defined('TEIA_ROOT') || exit;

use Teia\Core\App;
use Teia\Http\Request;
use Teia\Http\Response;
use Teia\View\Render;

/** Site público do TEIA (sem sessão e sem cookies). */
final class SiteController
{
    public function __construct(private readonly App $app)
    {
    }

    public function home(Request $request): Response
    {
        $this->app->allowMapFrames();
        $html = $this->app->view->render('site/teia', $this->shared() + ['c' => $this->app->content('teia')]);
        $response = Response::html($html);
        $response->header('Cache-Control', 'no-cache');
        return $response;
    }

    public function renderNotFound(): string
    {
        return $this->app->view->render('site/404', $this->shared() + [
            'c' => $this->app->content('erro-404'),
            'teia' => $this->app->content('teia'),
        ]);
    }

    private function shared(): array
    {
        return ['h' => new Render(TEIA_ROOT . '/public/assets'), 'layout' => $this->app->content('layout')];
    }
}
