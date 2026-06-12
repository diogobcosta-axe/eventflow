<?php
// pages/editar_evento.php — Editar um evento existente
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireOrganizador();

$id = (int)($_GET['id'] ?? 0);
$db = getDB();

// Buscar o evento
$stmt = $db->prepare("SELECT * FROM eventos WHERE id = ?");
$stmt->execute([$id]);
$ev = $stmt->fetch();

if (!$ev) {
    header('Location: /errors/404.php');
    exit;
}

// Só o dono ou o admin pode editar
if (!isAdmin() && $ev['organizador_id'] !== getCurrentUserId()) {
    header('Location: /errors/403.php');
    exit;
}

$categorias = $db->query("SELECT * FROM categorias_evento ORDER BY nome")->fetchAll();
$erros      = [];

// Preencher com os dados atuais do evento
$titulo       = $ev['titulo'];
$descricao    = $ev['descricao'];
$local        = $ev['local'];
$data_inicio  = str_replace(' ', 'T', $ev['data_inicio']);
$data_fim     = str_replace(' ', 'T', $ev['data_fim'] ?? '');
$vagas        = $ev['vagas'];
$categoria_id = $ev['categoria_id'];
$estado       = $ev['estado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $titulo       = trim($_POST['titulo'] ?? '');
    $descricao    = trim($_POST['descricao'] ?? '');
    $local        = trim($_POST['local'] ?? '');
    $data_inicio  = trim($_POST['data_inicio'] ?? '');
    $data_fim     = trim($_POST['data_fim'] ?? '');
    $vagas        = (int)($_POST['vagas'] ?? 0);
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $estado       = $_POST['estado'] ?? 'ativo';

    // Validações
    if (strlen($titulo) < 3)    $erros['titulo']    = 'Título demasiado curto.';
    if (strlen($descricao) < 10) $erros['descricao'] = 'Descrição demasiado curta.';
    if (empty($local))           $erros['local']     = 'Local obrigatório.';
    if ($vagas < 1)              $erros['vagas']     = 'Vagas devem ser pelo menos 1.';

    // Processar nova imagem (se foi carregada)
    $imagem = $ev['imagem']; // manter a imagem atual por defeito
    if (!empty($_FILES['imagem']['name']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES['imagem']['tmp_name']);

        if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
            $erros['imagem'] = 'Tipo de ficheiro não permitido. Use JPEG, PNG, WebP ou GIF.';
        } elseif ($_FILES['imagem']['size'] > UPLOAD_MAX_SIZE) {
            $erros['imagem'] = 'Ficheiro demasiado grande. Máximo 5MB.';
        } else {
            $ext    = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
            $nome   = uniqid('ev_') . '.' . strtolower($ext);
            move_uploaded_file($_FILES['imagem']['tmp_name'], UPLOAD_DIR . $nome);
            $imagem = $nome;
        }
    }

    if (empty($erros)) {
        $db->prepare("
            UPDATE eventos
            SET titulo = ?, descricao = ?, local = ?, data_inicio = ?, data_fim = ?,
                vagas = ?, imagem = ?, categoria_id = ?, estado = ?
            WHERE id = ?
        ")->execute([
            $titulo, $descricao, $local,
            $data_inicio, $data_fim ?: null,
            $vagas, $imagem, $categoria_id, $estado, $id
        ]);

        redirectWith("/pages/evento.php?id=$id", 'success', 'Evento atualizado!');
    }
}

$pageTitle = 'Editar ' . $ev['titulo'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding:48px 24px; max-width:700px">
    <h1 style="font-size:2rem; margin-bottom:8px">✏️ Editar evento</h1>
    <p style="color:var(--clr-muted); margin-bottom:32px"><?= e($ev['titulo']) ?></p>

    <form method="POST" enctype="multipart/form-data"
          style="background:var(--clr-bg2);border:1px solid var(--clr-border);border-radius:20px;padding:36px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="form-group">
            <label>Título *</label>
            <input type="text" name="titulo" class="form-control" value="<?= e($titulo) ?>" required>
            <?php if (!empty($erros['titulo'])): ?>
            <span class="form-error"><?= e($erros['titulo']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Descrição *</label>
            <textarea name="descricao" class="form-control" rows="5" required><?= e($descricao) ?></textarea>
            <?php if (!empty($erros['descricao'])): ?>
            <span class="form-error"><?= e($erros['descricao']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Categoria</label>
            <select name="categoria_id" class="form-control">
                <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $categoria_id == $cat['id'] ? 'selected' : '' ?>>
                    <?= $cat['icone'] ?> <?= e($cat['nome']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Local *</label>
            <input type="text" name="local" class="form-control" value="<?= e($local) ?>" required>
            <?php if (!empty($erros['local'])): ?>
            <span class="form-error"><?= e($erros['local']) ?></span>
            <?php endif; ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div class="form-group">
                <label>Data início *</label>
                <input type="datetime-local" name="data_inicio" class="form-control"
                       value="<?= e($data_inicio) ?>" required>
            </div>
            <div class="form-group">
                <label>Data fim</label>
                <input type="datetime-local" name="data_fim" class="form-control"
                       value="<?= e($data_fim) ?>">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div class="form-group">
                <label>Vagas *</label>
                <input type="number" name="vagas" class="form-control" min="1" value="<?= e($vagas) ?>" required>
                <?php if (!empty($erros['vagas'])): ?>
                <span class="form-error"><?= e($erros['vagas']) ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="ativo"     <?= $estado === 'ativo'     ? 'selected' : '' ?>>✅ Ativo</option>
                    <option value="encerrado" <?= $estado === 'encerrado' ? 'selected' : '' ?>>🔒 Encerrado</option>
                    <option value="cancelado" <?= $estado === 'cancelado' ? 'selected' : '' ?>>❌ Cancelado</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Imagem <?php if ($ev['imagem']): ?>(atual: <?= e($ev['imagem']) ?>)<?php endif; ?></label>
            <div class="file-input-wrap" onclick="document.getElementById('imagem').click()">
                <input type="file" id="imagem" name="imagem" accept="image/*">
                <div class="upload-icon">🖼️</div>
                <div class="upload-label">Clica para substituir a imagem</div>
                <div class="upload-preview"></div>
            </div>
            <?php if (!empty($erros['imagem'])): ?>
            <span class="form-error"><?= e($erros['imagem']) ?></span>
            <?php endif; ?>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
            <button type="submit" class="btn btn--primary">💾 Guardar alterações</button>
            <a href="/pages/evento.php?id=<?= $id ?>" class="btn btn--ghost">Cancelar</a>

            <form method="POST" action="/pages/apagar_evento.php" style="margin:0"
                  onsubmit="return confirm('Apagar este evento permanentemente?')">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn--danger">🗑️ Apagar</button>
            </form>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
