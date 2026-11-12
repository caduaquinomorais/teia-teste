<?php

declare(strict_types=1);

namespace Teia\Cms;

defined('TEIA_ROOT') || exit;

/** Edição concorrente: a página mudou desde que o formulário foi aberto (HTTP 409). */
final class ConflictException extends \RuntimeException
{
    public function __construct(public readonly int $currentVersion)
    {
        parent::__construct('Conflito de edição.');
    }
}
