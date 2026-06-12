<?php
// pages/inscricao.php — Processar inscrição ou cancelamento num evento (via formulário POST)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

// Só aceita pedidos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/eventos.php');
    exit;
}

validateCsrf();

$evento_id = (int)($_POST['evento_id'] ?? 0);
$action    = $_POST['action'] ?? '';

// Validar os dados recebidos
if (!$evento_id || !in_array($action, ['inscrever', 'cancelar'])) {
    redirectWith('/pages/eventos.php', 'error', 'Pedido inválido.');
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM eventos WHERE id = ? AND estado = 'ativo'");
$stmt->execute([$evento_id]);
$evento = $stmt->fetch();

if (!$evento) {
    redirectWith('/pages/eventos.php', 'error', 'Evento não encontrado ou inativo.');
}

$user_id  = getCurrentUserId();
$inscricao = isInscrito($user_id, $evento_id);

if ($action === 'inscrever') {
    // Verificar se há vagas
    if (vagasDisponiveis($evento) <= 0) {
        redirectWith("/pages/evento.php?id=$evento_id", 'error', 'Não há vagas disponíveis.');
    }
    // Verificar se já está inscrito
    if ($inscricao && $inscricao['estado'] === 'confirmada') {
        redirectWith("/pages/evento.php?id=$evento_id", 'error', 'Já estás inscrito neste evento.');
    }

    if ($inscricao) {
        // Reativar inscrição cancelada
        $db->prepare("UPDATE inscricoes SET estado = 'confirmada' WHERE id = ?")
           ->execute([$inscricao['id']]);
    } else {
        // Nova inscrição
        $db->prepare("INSERT INTO inscricoes (utilizador_id, evento_id, estado) VALUES (?, ?, 'confirmada')")
           ->execute([$user_id, $evento_id]);
    }

    redirectWith("/pages/evento.php?id=$evento_id", 'success', 'Inscrição confirmada! Até breve! 🎉');

} else { // cancelar
    if (!$inscricao || $inscricao['estado'] === 'cancelada') {
        redirectWith("/pages/evento.php?id=$evento_id", 'error', 'Não estás inscrito neste evento.');
    }

    $db->prepare("UPDATE inscricoes SET estado = 'cancelada' WHERE id = ?")
       ->execute([$inscricao['id']]);

    redirectWith("/pages/evento.php?id=$evento_id", 'success', 'Inscrição cancelada com sucesso.');
}
