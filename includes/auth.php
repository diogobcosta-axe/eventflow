<?php
// includes/auth.php — Funções de autenticação e controlo de sessões PHP

// Inicia a sessão se ainda não foi iniciada (evita erros de "session already started")
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Guarda os dados do utilizador na sessão após login bem-sucedido
function loginUser(array $user): void {
    // Regenera o ID da sessão para prevenir ataques de "session fixation"
    // (um atacante que conheça o ID antigo não consegue usar a sessão)
    session_regenerate_id(true);

    // Guarda os dados essenciais do utilizador na sessão
    $_SESSION['user_id']    = $user['id'];     // ID na base de dados
    $_SESSION['user_nome']  = $user['nome'];   // Nome para mostrar na navbar
    $_SESSION['user_email'] = $user['email'];  // Email do utilizador
    $_SESSION['user_papel'] = $user['papel'];  // Papel: admin / organizador / participante
}


// Termina a sessão do utilizador (logout)
function logoutUser(): void {
    $_SESSION = []; // Apaga todos os dados da sessão
    session_destroy(); // Destrói a sessão no servidor
}


// Verifica se existe um utilizador autenticado (há um ID na sessão)
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}


// Devolve o papel do utilizador atual ('admin', 'organizador', 'participante' ou 'guest')
function getUserRole(): string {
    return $_SESSION['user_papel'] ?? 'guest'; // 'guest' se não estiver autenticado
}


// Verifica se o utilizador atual é administrador
function isAdmin(): bool {
    return getUserRole() === 'admin';
}


// Verifica se o utilizador atual é organizador OU admin
// (admin tem todas as permissões de organizador, mais as suas próprias)
function isOrganizador(): bool {
    return in_array(getUserRole(), ['admin', 'organizador']);
}


// Garante que o utilizador está autenticado; caso contrário, redireciona para o login
function requireLogin(): void {
    if (!isLoggedIn()) {
        // Guarda o URL atual para redirecionar de volta após o login
        header('Location: /pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit; // Para a execução do script para o redirect funcionar
    }
}


// Garante que o utilizador é organizador ou admin; caso contrário, redireciona para erro 403
function requireOrganizador(): void {
    requireLogin(); // Primeiro verifica se está autenticado
    if (!isOrganizador()) {
        header('Location: /errors/403.php'); // Acesso negado
        exit;
    }
}


// Garante que o utilizador é administrador; caso contrário, redireciona para erro 403
function requireAdmin(): void {
    requireLogin(); // Primeiro verifica se está autenticado
    if (!isAdmin()) {
        header('Location: /errors/403.php'); // Acesso negado
        exit;
    }
}


// Devolve o ID do utilizador autenticado, ou null se não estiver autenticado
function getCurrentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}
