<?php

declare(strict_types=1);

namespace Teia\Core;

defined('TEIA_ROOT') || exit;

/**
 * Renderizador de templates PHP (app/views). Os templates usam e() para escapar.
 * Um template pode definir um layout via $this->layout('site/layout').
 */
final class View
{
    private ?string $layout = null;
    private array $layoutData = [];
    private array $shared = [];

    public function __construct(private readonly string $dir)
    {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function render(string $template, array $data = []): string
    {
        $this->layout = null;
        $content = $this->renderFile($template, $data);
        while ($this->layout !== null) {
            $layout = $this->layout;
            $this->layout = null;
            $content = $this->renderFile($layout, array_merge($data, $this->layoutData, ['content' => $content]));
        }
        return $content;
    }

    /** Renderiza um parcial (sem layout). */
    public function partial(string $template, array $data = []): string
    {
        $saved = $this->layout;
        $out = $this->renderFile($template, $data);
        $this->layout = $saved;
        return $out;
    }

    public function layout(string $template, array $data = []): void
    {
        $this->layout = $template;
        $this->layoutData = $data;
    }

    private function renderFile(string $template, array $data): string
    {
        if (!preg_match('#^[a-z0-9_/-]+$#', $template)) {
            throw new \InvalidArgumentException('Nome de template inválido.');
        }
        $file = $this->dir . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Template não encontrado: ' . $template);
        }
        $data = array_merge($this->shared, $data);
        $view = $this;
        ob_start();
        try {
            (static function (string $__file, array $__data, View $view): void {
                extract($__data, EXTR_SKIP);
                require $__file;
            })($file, $data, $view);
        } catch (\Throwable $ex) {
            ob_end_clean();
            throw $ex;
        }
        return (string) ob_get_clean();
    }
}
