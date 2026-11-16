<?php

declare(strict_types=1);

/*
 * Front controller de RESERVA, na raiz do repositório.
 *
 * Normalmente nunca é executado: o .htaccess da raiz encaminha tudo para public/.
 * Ele existe para hospedagens que ignoram o .htaccess da raiz (caso observado na KingHost
 * em /teia): sem ele, /teia/ responderia 403. Executado por aqui, o app aponta os
 * arquivos estáticos para /teia/public/assets, que é onde eles existem de fato.
 */
require __DIR__ . '/public/index.php';
