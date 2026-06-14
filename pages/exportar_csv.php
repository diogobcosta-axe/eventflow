<?php
// pages/exportar_csv.php — Gera e envia um ficheiro CSV com a lista de inscritos num evento
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Só organizadores e admins podem exportar a lista de inscritos
requireOrganizador();

// Lê o ID do evento passado na URL (ex: /exportar_csv.php?id=5)
$id = (int)($_GET['id'] ?? 0);
$db = getDB();

// Procura o evento na base de dados
$evStmt = $db->prepare("SELECT * FROM eventos WHERE id = ?");
$evStmt->execute([$id]);
$ev = $evStmt->fetch();

// Se o evento não existir, redireciona para a página de erro 404
if (!$ev) { header('Location: /errors/404.php'); exit; }

// Verifica que o organizador é o dono do evento (ou é admin)
if (!isAdmin() && $ev['organizador_id'] !== getCurrentUserId()) {
    header('Location: /errors/403.php'); exit;
}

// Obtém todos os inscritos neste evento, com o nome e email de cada um
$stmt = $db->prepare("
    SELECT u.nome, u.email, i.estado, i.criado_em
    FROM inscricoes i
    JOIN utilizadores u ON i.utilizador_id = u.id
    WHERE i.evento_id = ?
    ORDER BY i.criado_em ASC  -- Ordena por data de inscrição (mais antigas primeiro)
");
$stmt->execute([$id]);
$rows = $stmt->fetchAll();

// Gera o nome do ficheiro: remove caracteres especiais e acrescenta a data
// preg_replace() substitui tudo o que não seja letras/números por underscore
$filename = 'inscritos_' . preg_replace('/[^a-z0-9]/', '_', strtolower($ev['titulo'])) . '_' . date('Ymd') . '.csv';

// Define os cabeçalhos HTTP para download de ficheiro CSV
header('Content-Type: text/csv; charset=UTF-8');   // Tipo de conteúdo: CSV com UTF-8
header('Content-Disposition: attachment; filename="' . $filename . '"'); // Forçar download com este nome
header('Pragma: no-cache');  // Não guardar em cache
header('Expires: 0');        // Expirado imediatamente

// Abre um "ficheiro" de saída que escreve diretamente para o browser
$out = fopen('php://output', 'w');

// Escreve o BOM (Byte Order Mark) UTF-8 para garantir que o Excel abre o CSV com acentos correctos
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Escreve a linha de cabeçalho do CSV (separado por ponto-e-vírgula, compatível com Excel português)
fputcsv($out, ['Nome', 'Email', 'Estado', 'Data de Inscrição', 'Evento', 'Local', 'Data Evento'], ';');

// Escreve uma linha por cada inscrito
foreach ($rows as $r) {
    fputcsv($out, [
        $r['nome'],             // Nome do participante
        $r['email'],            // Email do participante
        ucfirst($r['estado']),  // Estado com primeira letra maiúscula (ex: "Confirmada")
        $r['criado_em'],        // Data em que se inscreveu
        $ev['titulo'],          // Título do evento (igual para todas as linhas)
        $ev['local'],           // Local do evento
        $ev['data_inicio']      // Data do evento
    ], ';');
}

fclose($out); // Fecha o stream de saída
exit;         // Para a execução (evita enviar HTML depois do CSV)
