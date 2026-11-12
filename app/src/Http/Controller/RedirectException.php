<?php

declare(strict_types=1);

namespace Teia\Http\Controller;

defined('TEIA_ROOT') || exit;

use Teia\Http\HttpException;

/** Interrompe a ação com um redirecionamento 303. */
final class RedirectException extends HttpException
{
    public function __construct(string $location)
    {
        parent::__construct(303, '', ['Location' => $location]);
    }
}
