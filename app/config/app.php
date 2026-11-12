<?php

/*
 * Configuração padrão (versionada). Para sobrescrever em um servidor, crie
 * app/config/app.local.php (fora do Git) retornando um array só com as chaves desejadas.
 */
defined('TEIA_ROOT') || exit;

return [
    // Caminho base da URL (ex.: '/teia'). null = detectar automaticamente pelo SCRIPT_NAME.
    'base_path' => null,

    // Considera a requisição como HTTPS (HSTS) mesmo atrás de proxy que não informa HTTPS.
    'force_https' => false,

    // Considerar X-Forwarded-Proto apenas destes IPs de proxy.
    'trusted_proxies' => [],
];
