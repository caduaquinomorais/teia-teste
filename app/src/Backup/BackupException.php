<?php

declare(strict_types=1);

namespace Teia\Backup;

defined('TEIA_ROOT') || exit;

/** Erro de backup/restauração com mensagem segura para exibir ao administrador. */
final class BackupException extends \RuntimeException
{
}
