<?php
// config/app.php — Configurações gerais da aplicação

// Iniciar sessão de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false, // mudar para true em produção com HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Timezone Portugal
date_default_timezone_set('Europe/Lisbon');

// Controlo de erros (desligar em produção com: ini_set('display_errors', 0))
ini_set('display_errors', 1);
error_reporting(E_ALL);
