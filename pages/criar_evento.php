<?php
// pages/criar_evento.php — Formulário para criar um novo evento
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireOrganizador();

$db         = getDB();
$categorias = $db->query("SELECT * FROM categorias_evento ORDER BY nome")->fetchAll();
$erros      = [];

$titulo       = '';
$descricao    = '';
$local        = '';
$data_inicio  = '';
$data_fim     = '';
$vagas        = 50;
$categoria_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $titulo       = trim($_POST['titulo'] ?? '');
    $descricao    = trim($_POST['descricao'] ?? '');
    $local        = trim($_POST['local'] ?? '');
    $data_inicio  = trim($_POST['data_inicio'] ?? '');
    $data_fim     = trim($_POST['data_fim'] ?? '');
    $vagas        = (int)($_POST['vagas'] ?? 0);
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);

    // Validações no servidor
    if (strlen($titulo) < 3)     $erros['titulo']       = 'Titulo deve ter pelo menos 3 caracteres.';
    if (strlen($descricao) < 10) $erros['descricao']    = 'Descricao deve ter pelo menos 10 caracteres.';
    if (empty($local))           $erros['local']         = 'Local obrigatorio.';
    if (empty($data_inicio))     $erros['data_inicio']   = 'Data de inicio obrigatoria.';
    if ($vagas < 1)              $erros['vagas']         = 'Numero de vagas deve ser pelo menos 1.';
    if (!$categoria_id)          $erros['categoria_id']  = 'Seleciona uma categoria.';

    // Processamento do upload de imagem
    $imagem = null;
    if (!empty($_FILES['imagem']['name']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES['imagem']['tmp_name']);

        if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
            $erros['imagem'] = 'Tipo de ficheiro nao permitido. Use JPEG, PNG, WebP ou GIF.';
        } elseif ($_FILES['imagem']['size'] > UPLOAD_MAX_SIZE) {
            $erros['imagem'] = 'Ficheiro demasiado grande. Maximo 5MB.';
        } else {
            $ext    = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
            $nome   = uniqid('ev_') . '.' . strtolower($ext);
            move_uploaded_file($_FILES['imagem']['tmp_name'], UPLOAD_DIR . $nome);
            $imagem = $nome;
        }
    }

    if (empty($erros)) {
        $db->prepare("
            INSERT INTO eventos (titulo, descricao, local, data_inicio, data_fim, vagas, imagem, organizador_id, categoria_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $titulo, $descricao, $local,
            $data_inicio,
            $data_fim ?: null,
            $vagas, $imagem,
            getCurrentUserId(),
            $categoria_id
        ]);

        $novo_id = $db->lastInsertId();
        redirectWith("/pages/evento.php?id=$novo_id", 'success', 'Evento criado com sucesso!');
    }
}

$pageTitle = 'Criar evento';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding:48px 24px; max-width:700px">
    <div style="margin-bottom:32px">
        <h1 style="font-size:2rem">Criar novo evento</h1>
        <p style="color:var(--clr-muted)">Preenche os detalhes do teu evento</p>
    </div>

    <form method="POST" enctype="multipart/form-data"
          style="background:var(--clr-bg2);border:1px solid var(--clr-border);border-radius:20px;padding:36px">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="form-group">
            <label for="titulo">Titulo do evento *</label>
            <input type="text" id="titulo" name="titulo" class="form-control"
                   placeholder="Ex: Festival de Musica do Porto 2026" value="<?= e($titulo) ?>" required>
            <?php if (!empty($erros['titulo'])): ?>
            <span class="form-error"><?= e($erros['titulo']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="descricao">Descricao *</label>
            <textarea id="descricao" name="descricao" class="form-control" rows="5"
                      placeholder="Descreve o teu evento em detalhe..." required><?= e($descricao) ?></textarea>
            <?php if (!empty($erros['descricao'])): ?>
            <span class="form-error"><?= e($erros['descricao']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="categoria_id">Categoria *</label>
            <select id="categoria_id" name="categoria_id" class="form-control" required>
                <option value="">Seleciona uma categoria</option>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $categoria_id == $cat['id'] ? 'selected' : '' ?>>
                    <?= e($cat['icone']) ?> <?= e($cat['nome']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($erros['categoria_id'])): ?>
            <span class="form-error"><?= e($erros['categoria_id']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="local">Local *</label>
            <input type="text" id="local" name="local" class="form-control"
                   placeholder="Ex: Parque da Cidade, Porto" value="<?= e($local) ?>" required>
            <?php if (!empty($erros['local'])): ?>
            <span class="form-error"><?= e($erros['local']) ?></span>
            <?php endif; ?>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div class="form-group">
                <label for="data_inicio">Data de inicio *</label>
                <input type="datetime-local" id="data_inicio" name="data_inicio" class="form-control"
                       value="<?= e($data_inicio) ?>" required>
                <?php if (!empty($erros['data_inicio'])): ?>
                <span class="form-error"><?= e($erros['data_inicio']) ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="data_fim">Data de fim</label>
                <input type="datetime-local" id="data_fim" name="data_fim" class="form-control"
                       value="<?= e($data_fim) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="vagas">Numero de vagas *</label>
            <input type="number" id="vagas" name="vagas" class="form-control"
                   min="1" value="<?= e($vagas) ?>" required>
            <?php if (!empty($erros['vagas'])): ?>
            <span class="form-error"><?= e($erros['vagas']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Imagem do evento</label>
            <div class="file-input-wrap" onclick="document.getElementById('imagem').click()">
                <input type="file" id="imagem" name="imagem" accept="image/*">
                <div class="upload-label">Clica para carregar uma imagem</div>
                <p style="font-size:.78rem;color:var(--clr-muted);margin-top:4px">JPEG, PNG, WebP ou GIF &middot; Max. 5MB</p>
                <div class="upload-preview"></div>
            </div>
            <?php if (!empty($erros['imagem'])): ?>
            <span class="form-error"><?= e($erros['imagem']) ?></span>
            <?php endif; ?>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px">
            <button type="submit" class="btn btn--primary btn--lg">Publicar evento</button>
            <a href="/pages/meus_eventos.php" class="btn btn--ghost btn--lg">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
