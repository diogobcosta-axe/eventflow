<?php
/**
 * EventFlow - Funções de Autenticação e Sessões
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_nome']  = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_papel'] = $user['papel'];
}

function logoutUser(): void {
    $_SESSION = [];
    session_destroy();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getUserRole(): string {
    return $_SESSION['user_papel'] ?? 'guest';
}

function isAdmin(): bool {
    return getUserRole() === 'admin';
}

function isOrganizador(): bool {
    return in_array(getUserRole(), ['admin', 'organizador']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireOrganizador(): void {
    requireLogin();
    if (!isOrganizador()) {
        header('Location: /errors/403.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /errors/403.php');
        exit;
    }
}

function getCurrentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}
