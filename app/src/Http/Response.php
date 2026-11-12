<?php

declare(strict_types=1);

namespace Teia\Http;

defined('TEIA_ROOT') || exit;

/** Resposta HTTP. */
final class Response
{
    /** @var array<string,string> */
    private array $headers = [];

    public function __construct(
        private string $body = '',
        private int $status = 200,
        array $headers = [],
    ) {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function header(string $name, string $value): self
    {
        // Proteção contra injeção de cabeçalho.
        $this->headers[$name] = str_replace(["\r", "\n", "\0"], '', $value);
        return $this;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            header_remove('X-Powered-By');
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }
        echo $this->body;
    }
}
