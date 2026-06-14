<?php
// pages/inscricao.php — Processa inscrição ou cancelamento num evento (via formulário POST)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Garante que o utilizador está autenticado; redireciona para login se não estiver
requireLogin();

// Este ficheiro só aceita pedidos POST (não deve ser acedido diretamente pelo browser)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/eventos.php');
    exit;
}

// Valida o token CSRF para garantir que o pedido vem do nosso formulário
validateCsrf();

// Lê os dados enviados pelo formulário
$evento_id = (int)($_POST['evento_id'] ?? 0); // (int) garante que é um número inteiro
$action    = $_POST['action'] ?? '';          // 'inscrever' ou 'cancelar'

// Valida que o evento_id é válido e que a ação é reconhecida
if (!$evento_id || !in_array($action, ['inscrever', 'cancelar'])) {
    redirectWith('/pages/eventos.php', 'error', 'Pedido inválido.');
}

$db = getDB();

// Verifica se o evento existe e está ativo (só se pode inscrever em eventos ativos)
$stmt = $db->prepare("SELECT * FROM eventos WHERE id = ? AND estado = 'ativo'");
$stmt->execute([$evento_id]);
$evento = $stmt->fetch();

// Se o evento não existir ou não estiver ativo, redireciona com erro
if (!$evento) {
    redirectWith('/pages/eventos.php', 'error', 'Evento não encontrado ou inativo.');
}

// Obtém o ID do utilizador autenticado e verifica se já está inscrito
$user_id   = getCurrentUserId();
$inscricao = isInscrito($user_id, $evento_id); // Devolve a linha da inscrição ou null


// ===================== AÇÃO: INSCREVER =====================
if ($action === 'inscrever') {

    // Verifica se ainda há vagas disponíveis no evento
    if (vagasDisponiveis($evento) <= 0) {
        redirectWith("/pages/evento.php?id=$evento_id", 'error', 'Não há vagas disponíveis.');
    }

    // Verifica se o utilizador já tem uma inscrição confirmada neste evento
    if ($inscricao && $inscricao['estado'] === 'confirmada') {
        redirectWith("/pages/evento.php?id=$evento_id", 'error', 'Já estás inscrito neste evento.');
    }

    if ($inscricao) {
        // Já existe uma inscrição (mas está cancelada) → reativa-a alterando o estado
        $db->prepare("UPDATE inscricoes SET estado = 'confirmada' WHERE id = ?")
           ->execute([$inscricao['id']]);
    } else {
        // Não existe nenhuma inscrição → cria uma nova
        $db->prepare("INSERT INTO inscricoes (utilizador_id, evento_id, estado) VALUES (?, ?, 'confirmada')")
           ->execute([$user_id, $evento_id]);
    }

    // Redireciona para a página do evento com mensagem de sucesso
    redirectWith("/pages/evento.php?id=$evento_id", 'success', 'Inscrição confirmada! Até breve! 🎉');


// ===================== AÇÃO: CANCELAR =====================
} else {

    // Verifica se o utilizador está de facto inscrito (com inscrição ativa)
    if (!$inscricao || $inscricao['estado'] === 'cancelada') {
        redirectWith("/pages/evento.php?id=$evento_id", 'error', 'Não estás inscrito neste evento.');
    }

    // Atualiza o estado da inscrição para 'cancelada' (não apaga o registo)
    $db->prepare("UPDATE inscricoes SET estado = 'cancelada' WHERE id = ?")
       ->execute([$inscricao['id']]);

    redirectWith("/pages/evento.php?id=$evento_id", 'success', 'Inscrição cancelada com sucesso.');
}
