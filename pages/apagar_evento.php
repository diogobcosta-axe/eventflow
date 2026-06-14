<?php
// pages/apagar_evento.php — Apaga um evento da base de dados (ação POST apenas)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Só organizadores e admins podem apagar eventos
requireOrganizador();

// Este ficheiro só aceita pedidos POST (segurança: não apagar por acidente com GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

// Valida o token CSRF (proteção contra ataques de Cross-Site Request Forgery)
validateCsrf();

// Lê o ID do evento a apagar (enviado como campo escondido no formulário)
$id = (int)($_POST['id'] ?? 0);
$db = getDB();

// Procura o evento na base de dados
$ev = $db->prepare("SELECT * FROM eventos WHERE id = ?");
$ev->execute([$id]);
$evento = $ev->fetch();

// Se o evento não existir, redireciona com erro
if (!$evento) {
    redirectWith('/pages/meus_eventos.php', 'error', 'Evento não encontrado.');
}

// Verifica que o utilizador é o dono do evento, ou que é admin
// (organizador só pode apagar os seus próprios eventos)
if (!isAdmin() && $evento['organizador_id'] !== getCurrentUserId()) {
    header('Location: /errors/403.php');
    exit;
}

// Apaga o evento — as inscrições associadas são apagadas automaticamente
// por causa do ON DELETE CASCADE definido na tabela inscricoes
$db->prepare("DELETE FROM eventos WHERE id = ?")->execute([$id]);

// Redireciona para a lista de eventos do organizador com mensagem de sucesso
redirectWith('/pages/meus_eventos.php', 'success', 'Evento apagado.');
