<?php
/**
 * EventFlow - AJAX toggle presença
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

if (!isOrganizador()) {
    echo json_encode(['success'=>false, 'msg'=>'Sem permissão.']);
    exit;
}

$inscricao_id = (int)($_POST['inscricao_id'] ?? 0);
if (!$inscricao_id) {
    echo json_encode(['success'=>false, 'msg'=>'ID inválido.']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM inscricoes WHERE id=?");
$stmt->execute([$inscricao_id]);
$ins  = $stmt->fetch();

if (!$ins) {
    echo json_encode(['success'=>false, 'msg'=>'Inscrição não encontrada.']);
    exit;
}

// Verificar que o organizador é dono do evento
if (!isAdmin()) {
    $ev = $db->prepare("SELECT organizador_id FROM eventos WHERE id=?");
    $ev->execute([$ins['evento_id']]);
    $evento = $ev->fetch();
    if (!$evento || $evento['organizador_id'] !== getCurrentUserId()) {
        echo json_encode(['success'=>false, 'msg'=>'Sem permissão.']);
        exit;
    }
}

$newEstado = $ins['estado'] === 'presenca' ? 'confirmada' : 'presenca';
$db->prepare("UPDATE inscricoes SET estado=? WHERE id=?")
   ->execute([$newEstado, $inscricao_id]);

echo json_encode([
    'success'  => true,
    'presenca' => $newEstado === 'presenca',
    'msg'      => $newEstado === 'presenca' ? '✓ Presença marcada' : 'Presença removida',
]);
