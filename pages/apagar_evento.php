<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireOrganizador();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /index.php'); exit; }
validateCsrf();
$id = (int)($_POST['id'] ?? 0);
$db = getDB();
$ev = $db->prepare("SELECT * FROM eventos WHERE id=?");
$ev->execute([$id]);
$evento = $ev->fetch();
if (!$evento) redirectWith('/pages/meus_eventos.php', 'error', 'Evento não encontrado.');
if (!isAdmin() && $evento['organizador_id'] !== getCurrentUserId()) { header('Location: /errors/403.php'); exit; }
$db->prepare("DELETE FROM eventos WHERE id=?")->execute([$id]);
redirectWith('/pages/meus_eventos.php', 'success', 'Evento apagado.');
