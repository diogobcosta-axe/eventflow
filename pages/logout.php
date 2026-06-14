<?php
// pages/logout.php — Termina a sessão do utilizador e redireciona para a página inicial
require_once __DIR__ . '/../includes/auth.php';    // Precisa da função logoutUser()
require_once __DIR__ . '/../includes/helpers.php'; // Precisa da função redirectWith()

// Destrói a sessão (apaga $_SESSION e elimina o cookie de sessão)
logoutUser();

// Redireciona para a página inicial com uma mensagem de confirmação
redirectWith('/index.php', 'success', 'Sessão terminada com sucesso.');
