<?php
// pages/inscricao.php — Processa inscrição ou cancelamento num evento (via formulário POST)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Garante que o utilizador está autenticado
requireLogin();

// Este ficheiro só aceita pedidos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/eventos.php');
    exit;
}

// Valida o token CSRF
validateCsrf();

$evento_id = (int)($_POST['evento_id'] ?? 0);
$action    = $_POST['action'] ?? '';

// Valida que o evento_id é válido e que a ação é reconhecida
if (!$evento_id || !in_array($action, ['inscrever', 'cancelar'])) {
    redirectWith('/pages/eventos.php', 'error', 'Pedido invalido.');
}

$db = getDB();

// Verifica se o evento existe e está ativo (só se pode inscrever em eventos ativos)
$stmt = $db->prepare("SELECT * FROM eventos WHERE id = ? AND estado = 'ativo'");
$stmt->execute([$evento_id]);
$evento = $stmt->fetch();

if (!$evento) {
    redirectWith('/pages/eventos.php', 'error', 'Evento nao encontrado ou inativo.');
}

$user_id   = getCurrentUserId();
$inscricao = isInscrito($user_id, $evento_id);


// ===================== ACAO: INSCREVER =====================
if ($action === 'inscrever') {

    // Verifica se ainda há vagas disponíveis
    if (vagasDisponiveis($evento) <= 0) {
        redirectWith("/pages/evento.php?id=$evento_id", 'error', 'Nao ha vagas disponiveis.');
    }

    // Verifica se já está inscrito com inscrição confirmada
    if ($inscricao && $inscricao['estado'] === 'confirmada') {
        redirectWith("/pages/evento.php?id=$evento_id", 'error', 'Ja estas inscrito neste evento.');
    }

    if ($inscricao) {
        // Reativa inscrição cancelada
        $db->prepare("UPDATE inscricoes SET estado = 'confirmada' WHERE id = ?")
           ->execute([$inscricao['id']]);
    } else {
        // Cria nova inscrição
        $db->prepare("INSERT INTO inscricoes (utilizador_id, evento_id, estado) VALUES (?, ?, 'confirmada')")
           ->execute([$user_id, $evento_id]);
    }

    redirectWith("/pages/evento.php?id=$evento_id", 'success', 'Inscricao confirmada! Ate breve!');


// ===================== ACAO: CANCELAR =====================
} else {

    // Verifica se o utilizador está de facto inscrito
    if (!$inscricao || $inscricao['estado'] === 'cancelada') {
        redirectWith("/pages/evento.php?id=$evento_id", 'error', 'Nao estas inscrito neste evento.');
    }

    // Atualiza o estado da inscrição para 'cancelada'
    $db->prepare("UPDATE inscricoes SET estado = 'cancelada' WHERE id = ?")
       ->execute([$inscricao['id']]);

    redirectWith("/pages/evento.php?id=$evento_id", 'success', 'Inscricao cancelada com sucesso.');
}
