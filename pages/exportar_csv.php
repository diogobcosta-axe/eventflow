<?php
/**
 * EventFlow - Exportar lista de inscritos em CSV
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireOrganizador();

$id = (int)($_GET['id'] ?? 0);
$db = getDB();

$evStmt = $db->prepare("SELECT * FROM eventos WHERE id=?");
$evStmt->execute([$id]);
$ev = $evStmt->fetch();

if (!$ev) { header('Location: /errors/404.php'); exit; }
if (!isAdmin() && $ev['organizador_id'] !== getCurrentUserId()) { header('Location: /errors/403.php'); exit; }

$stmt = $db->prepare("
    SELECT u.nome, u.email, i.estado, i.criado_em
    FROM inscricoes i
    JOIN utilizadores u ON i.utilizador_id=u.id
    WHERE i.evento_id=?
    ORDER BY i.criado_em ASC
");
$stmt->execute([$id]);
$rows = $stmt->fetchAll();

$filename = 'inscritos_' . preg_replace('/[^a-z0-9]/', '_', strtolower($ev['titulo'])) . '_' . date('Ymd') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

fputcsv($out, ['Nome', 'Email', 'Estado', 'Data de Inscrição', 'Evento', 'Local', 'Data Evento'], ';');
foreach ($rows as $r) {
    fputcsv($out, [
        $r['nome'], $r['email'], ucfirst($r['estado']), $r['criado_em'],
        $ev['titulo'], $ev['local'], $ev['data_inicio']
    ], ';');
}
fclose($out);
exit;
