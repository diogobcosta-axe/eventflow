<?php
/**
 * EventFlow - AJAX handler para inscrições
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success'=>false, 'msg'=>'Precisas de estar autenticado.']);
    exit;
}

validateCsrf();

$evento_id = (int)($_POST['evento_id'] ?? 0);
$action    = $_POST['action'] ?? '';
$user_id   = getCurrentUserId();

if (!$evento_id || !in_array($action, ['inscrever','cancelar'])) {
    echo json_encode(['success'=>false, 'msg'=>'Pedido inválido.']);
    exit;
}

$db = getDB();
$ev = $db->prepare("SELECT * FROM eventos WHERE id=? AND estado='ativo'");
$ev->execute([$evento_id]);
$evento = $ev->fetch();

if (!$evento) {
    echo json_encode(['success'=>false, 'msg'=>'Evento não encontrado ou inativo.']);
    exit;
}

$existing = isInscrito($user_id, $evento_id);

if ($action === 'inscrever') {
    $vagas_disp = vagasDisponiveis($evento);
    if ($vagas_disp <= 0) {
        echo json_encode(['success'=>false, 'msg'=>'Não há vagas disponíveis.']);
        exit;
    }
    if ($existing && $existing['estado'] === 'confirmada') {
        echo json_encode(['success'=>false, 'msg'=>'Já estás inscrito neste evento.']);
        exit;
    }
    if ($existing) {
        $db->prepare("UPDATE inscricoes SET estado='confirmada' WHERE id=?")
           ->execute([$existing['id']]);
    } else {
        $db->prepare("INSERT INTO inscricoes (utilizador_id, evento_id, estado) VALUES (?,?,'confirmada')")
           ->execute([$user_id, $evento_id]);
    }
    echo json_encode(['success'=>true, 'msg'=>'🎉 Inscrição confirmada! Até breve!']);
} else {
    if (!$existing || $existing['estado'] === 'cancelada') {
        echo json_encode(['success'=>false, 'msg'=>'Não estás inscrito neste evento.']);
        exit;
    }
    $db->prepare("UPDATE inscricoes SET estado='cancelada' WHERE id=?")
       ->execute([$existing['id']]);
    echo json_encode(['success'=>true, 'msg'=>'Inscrição cancelada com sucesso.']);
}
