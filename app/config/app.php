<?php

/*
 * Configuração padrão (versionada). Para sobrescrever em um ambiente,
 * crie <diretório de dados>/config.local.php retornando um array com as chaves desejadas.
 * Nunca coloque segredos aqui.
 */
defined('TEIA_ROOT') || exit;

return [
    // Nome exibido no painel.
    'app_name' => 'TEIA | ADE SAMPA',

    // Caminho base da URL (ex.: '/teia'). null = detectar automaticamente pelo SCRIPT_NAME.
    'base_path' => null,

    // Força cookies Secure e HSTS mesmo atrás de proxy que não informa HTTPS.
    'force_https' => false,

    // Considerar X-Forwarded-Proto/X-Forwarded-For apenas destes IPs de proxy.
    'trusted_proxies' => [],

    // Sessão administrativa (segundos).
    'session_idle_timeout' => 1800,      // 30 minutos sem atividade
    'session_absolute_timeout' => 28800, // 8 horas no máximo

    // Força bruta no login.
    'login_max_per_user' => 5,     // tentativas por usuário na janela
    'login_max_per_ip' => 20,      // tentativas por IP na janela
    'login_window' => 900,         // janela de 15 minutos

    // Instalador e recuperação.
    'token_max_attempts' => 5,
    'token_window' => 900,
    'recovery_token_ttl' => 1800,  // token de recuperação vale 30 minutos

    // Uploads.
    'upload_max_image_bytes' => 8 * 1024 * 1024,
    'upload_max_pdf_bytes' => 15 * 1024 * 1024,
    'image_max_width' => 2400,
    'image_max_pixels' => 40_000_000,

    // Backups.
    'backup_retention' => 14,          // quantidade de backups automáticos mantidos
    'backup_interval_hours' => 24,
    'backup_max_upload_bytes' => 200 * 1024 * 1024,
];
