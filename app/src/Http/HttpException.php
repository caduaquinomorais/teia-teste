<?php

declare(strict_types=1);

namespace Teia\Http;

defined('TEIA_ROOT') || exit;

/** Exceção que vira uma resposta HTTP com o status indicado. A mensagem é segura para exibição. */
class HttpException extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $publicMessage = '',
        public readonly array $headers = [],
    ) {
        parent::__construct($publicMessage, $status);
    }

    public static function notFound(): self
    {
        return new self(404, 'Página não encontrada.');
    }

    public static function methodNotAllowed(array $allowed): self
    {
        return new self(405, 'Método não permitido.', ['Allow' => implode(', ', $allowed)]);
    }
}
