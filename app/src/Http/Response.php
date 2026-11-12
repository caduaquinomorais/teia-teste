<?php

declare(strict_types=1);

namespace Teia\Http;

defined('TEIA_ROOT') || exit;

/** Resposta HTTP. O corpo pode ser string ou um arquivo (enviado em streaming). */
final class Response
{
    /** @var array<string,string> */
    private array $headers = [];
    private ?string $file = null;

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

    public static function json(array $data, int $status = 200): self
    {
        return new self(
            (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public static function redirect(string $location, int $status = 303): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public static function file(string $path, string $contentType, array $headers = []): self
    {
        $response = new self('', 200, $headers);
        $response->header('Content-Type', $contentType);
        $response->header('Content-Length', (string) filesize($path));
        $response->file = $path;
        return $response;
    }

    public function header(string $name, string $value): self
    {
        // Proteção contra injeção de cabeçalho.
        $value = str_replace(["\r", "\n", "\0"], '', $value);
        $this->headers[$name] = $value;
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

    public function body(): string
    {
        return $this->body;
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
        if ($this->file !== null) {
            $handle = fopen($this->file, 'rb');
            if ($handle !== false) {
                fpassthru($handle);
                fclose($handle);
            }
            return;
        }
        echo $this->body;
    }
}
