<?php
// pages/ajax_presenca.php — Endpoint AJAX para marcar/desmarcar presença num evento
// Chamado pelo JavaScript (fetch API) na página de presenças; devolve JSON
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Define que a resposta é JSON
header('Content-Type: application/json');

// Verifica se o utilizador tem permissão de organizador (ou admin)
if (!isOrganizador()) {
    echo json_encode(['success' => false, 'msg' => 'Sem permissao.']);
    exit;
}

// Lê o ID da inscrição enviado pelo formulário AJAX
$inscricao_id = (int)($_POST['inscricao_id'] ?? 0);

if (!$inscricao_id) {
    echo json_encode(['success' => false, 'msg' => 'ID invalido.']);
    exit;
}

$db = getDB();

// Procura a inscrição na base de dados
$stmt = $db->prepare("SELECT * FROM inscricoes WHERE id = ?");
$stmt->execute([$inscricao_id]);
$ins  = $stmt->fetch();

if (!$ins) {
    echo json_encode(['success' => false, 'msg' => 'Inscricao nao encontrada.']);
    exit;
}

// Verifica que o organizador é o dono do evento (segurança extra)
// O admin pode marcar presenças em qualquer evento
if (!isAdmin()) {
    $ev = $db->prepare("SELECT organizador_id FROM eventos WHERE id = ?");
    $ev->execute([$ins['evento_id']]);
    $evento = $ev->fetch();

    if (!$evento || $evento['organizador_id'] !== getCurrentUserId()) {
        echo json_encode(['success' => false, 'msg' => 'Sem permissao.']);
        exit;
    }
}

// Alterna o estado da inscrição:
// Se estava 'presenca' → volta a 'confirmada' (desmarca)
// Se estava 'confirmada' → muda para 'presenca' (marca)
$newEstado = $ins['estado'] === 'presenca' ? 'confirmada' : 'presenca';

// Atualiza o estado na base de dados
$db->prepare("UPDATE inscricoes SET estado = ? WHERE id = ?")
   ->execute([$newEstado, $inscricao_id]);

// Devolve JSON com o resultado para o JavaScript processar
echo json_encode([
    'success'  => true,
    'presenca' => $newEstado === 'presenca',
    'msg'      => $newEstado === 'presenca' ? 'Presenca marcada' : 'Presenca removida',
]);
