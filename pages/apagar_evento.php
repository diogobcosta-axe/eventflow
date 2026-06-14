<?php
// pages/apagar_evento.php — Apaga um evento da base de dados (ação POST apenas)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Só organizadores e admins podem apagar eventos
requireOrganizador();

// Este ficheiro só aceita pedidos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

// Valida o token CSRF
validateCsrf();

// Lê o ID do evento a apagar
$id = (int)($_POST['id'] ?? 0);
$db = getDB();

// Procura o evento na base de dados
$ev = $db->prepare("SELECT * FROM eventos WHERE id = ?");
$ev->execute([$id]);
$evento = $ev->fetch();

if (!$evento) {
    redirectWith('/pages/meus_eventos.php', 'error', 'Evento nao encontrado.');
}

// Verifica que o utilizador é o dono do evento, ou que é admin
if (!isAdmin() && $evento['organizador_id'] !== getCurrentUserId()) {
    header('Location: /errors/403.php');
    exit;
}

// Apaga o evento — as inscrições associadas são apagadas automaticamente
// por causa do ON DELETE CASCADE definido na tabela inscricoes
$db->prepare("DELETE FROM eventos WHERE id = ?")->execute([$id]);

redirectWith('/pages/meus_eventos.php', 'success', 'Evento apagado.');
