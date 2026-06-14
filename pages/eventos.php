<?php
// pages/eventos.php — Listagem de eventos com pesquisa, filtros e paginação
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = getDB();

// Parâmetros da pesquisa / filtros vindos do URL
$pesquisa  = trim($_GET['q'] ?? '');
$categoria = (int)($_GET['categoria'] ?? 0);
$ordem     = $_GET['ordem'] ?? 'data_asc';
$pagina    = max(1, (int)($_GET['page'] ?? 1));

// Construir as condições WHERE dinamicamente
$condicoes = ["e.estado = 'ativo'", "e.data_inicio >= datetime('now')"];
$params    = [];

if ($pesquisa !== '') {
    $condicoes[] = "(e.titulo LIKE ? OR e.descricao LIKE ? OR e.local LIKE ?)";
    $params[]    = "%$pesquisa%";
    $params[]    = "%$pesquisa%";
    $params[]    = "%$pesquisa%";
}
if ($categoria > 0) {
    $condicoes[] = "e.categoria_id = ?";
    $params[]    = $categoria;
}

$whereSQL = implode(' AND ', $condicoes);

// Ordenação
if ($ordem === 'data_desc') {
    $orderSQL = 'e.data_inicio DESC';
} elseif ($ordem === 'vagas') {
    $orderSQL = 'vagas_disp DESC';
} else {
    $orderSQL = 'e.data_inicio ASC';
}

// Contar total de resultados (para a paginação)
$stmt_total = $db->prepare("SELECT COUNT(*) FROM eventos e WHERE $whereSQL");
$stmt_total->execute($params);
$total = (int)$stmt_total->fetchColumn();

// Calcular paginação
$por_pagina   = 9;
$total_paginas = max(1, (int)ceil($total / $por_pagina));
$pagina        = max(1, min($pagina, $total_paginas));
$offset        = ($pagina - 1) * $por_pagina;

// Buscar os eventos desta página
$params_pag = $params;
$params_pag[] = $por_pagina;
$params_pag[] = $offset;

$stmt = $db->prepare("
    SELECT e.*, c.nome AS cat_nome, c.icone AS cat_icone, c.cor AS cat_cor, u.nome AS org_nome,
           (e.vagas - (SELECT COUNT(*) FROM inscricoes i WHERE i.evento_id = e.id AND i.estado = 'confirmada')) AS vagas_disp
    FROM eventos e
    JOIN categorias_evento c ON e.categoria_id = c.id
    JOIN utilizadores u ON e.organizador_id = u.id
    WHERE $whereSQL
    ORDER BY $orderSQL
    LIMIT ? OFFSET ?
");
$stmt->execute($params_pag);
$eventos = $stmt->fetchAll();

// Categorias para os filtros
$categorias = $db->query("SELECT * FROM categorias_evento ORDER BY nome")->fetchAll();

$pageTitle = 'Eventos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1>Todos os <span style="color:var(--clr-accent)">eventos</span></h1>
    <p>Encontra e inscreve-te nos próximos eventos</p>
</div>

<div class="container" style="padding-top:32px; padding-bottom:80px">

    <!-- Formulário de filtros -->
    <form class="filter-bar" method="GET">
        <input type="text" name="q" value="<?= e($pesquisa) ?>" placeholder="Pesquisar...">

        <select name="categoria" onchange="this.form.submit()">
            <option value="">Todas as categorias</option>
            <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $categoria == $cat['id'] ? 'selected' : '' ?>>
                <?= $cat['icone'] ?> <?= e($cat['nome']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <select name="ordem" onchange="this.form.submit()">
            <option value="data_asc"  <?= $ordem === 'data_asc'  ? 'selected' : '' ?>>Data (mais proximo)</option>
            <option value="data_desc" <?= $ordem === 'data_desc' ? 'selected' : '' ?>>Data (mais recente)</option>
            <option value="vagas"     <?= $ordem === 'vagas'     ? 'selected' : '' ?>>Mais vagas</option>
        </select>

        <button type="submit" class="btn btn--primary">Filtrar</button>

        <?php if ($pesquisa || $categoria): ?>
        <a href="/pages/eventos.php" class="btn btn--ghost">Limpar</a>
        <?php endif; ?>
    </form>

    <!-- Filtros rápidos por categoria -->
    <div class="categories-strip">
        <a href="/pages/eventos.php<?= $pesquisa ? '?q=' . urlencode($pesquisa) : '' ?>"
           class="cat-pill <?= !$categoria ? 'active' : '' ?>">Todos</a>
        <?php foreach ($categorias as $cat): ?>
        <a href="/pages/eventos.php?categoria=<?= $cat['id'] ?><?= $pesquisa ? '&q=' . urlencode($pesquisa) : '' ?>"
           class="cat-pill <?= $categoria == $cat['id'] ? 'active' : '' ?>">
            <?= $cat['icone'] ?> <?= e($cat['nome']) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <p style="color:var(--clr-muted); font-size:.88rem; margin-bottom:24px">
        <?= $total ?> evento<?= $total != 1 ? 's' : '' ?> encontrado<?= $total != 1 ? 's' : '' ?>
    </p>

    <!-- Grelha de eventos -->
    <?php if (empty($eventos)): ?>
    <div class="empty-state">
        <h3>Nenhum evento encontrado</h3>
        <p>Tenta alterar os filtros ou <a href="/pages/eventos.php" style="color:var(--clr-accent)">ver todos os eventos</a>.</p>
    </div>
    <?php else: ?>
    <div class="grid-events">
        <?php foreach ($eventos as $ev):
            $vagas_disp  = max(0, $ev['vagas_disp']);
            $inscritos   = $ev['vagas'] - $vagas_disp;
            $percentagem = $ev['vagas'] > 0 ? round(($inscritos / $ev['vagas']) * 100) : 100;
            if ($vagas_disp <= 0) {
                $vagas_class = 'full';
                $vagas_txt   = 'Esgotado';
            } elseif ($percentagem >= 80) {
                $vagas_class = 'few';
                $vagas_txt   = "Ultimas $vagas_disp vagas";
            } else {
                $vagas_class = 'ok';
                $vagas_txt   = "$vagas_disp vagas";
            }
        ?>
        <a href="/pages/evento.php?id=<?= $ev['id'] ?>" class="card event-card fade-in">
            <?php if ($ev['imagem']): ?>
            <img class="event-card__img" src="/assets/uploads/events/<?= e($ev['imagem']) ?>" alt="<?= e($ev['titulo']) ?>">
            <?php else: ?>
            <div class="event-card__img-placeholder"><?= $ev['cat_icone'] ?></div>
            <?php endif; ?>
            <div class="event-card__body">
                <span class="event-card__cat" style="background:<?= e($ev['cat_cor']) ?>22;color:<?= e($ev['cat_cor']) ?>">
                    <?= $ev['cat_icone'] ?> <?= e($ev['cat_nome']) ?>
                </span>
                <h3 class="event-card__title"><?= e($ev['titulo']) ?></h3>
                <div class="event-card__meta">
                    <div class="event-card__meta-row"><?= formatDate($ev['data_inicio']) ?></div>
                    <div class="event-card__meta-row"><?= e($ev['local']) ?></div>
                    <div class="event-card__meta-row"><?= e($ev['org_nome']) ?></div>
                </div>
                <div class="event-card__footer">
                    <span class="vagas-badge vagas-badge--<?= $vagas_class ?>"><?= $vagas_txt ?></span>
                    <span class="btn btn--primary btn--sm">Ver mais</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Paginação -->
    <?php if ($total_paginas > 1): ?>
    <div class="pagination">
        <?php
        // Construir URL base sem a página atual
        $url_params = $_GET;
        ?>
        <a href="?<?= http_build_query(array_merge($url_params, ['page' => $pagina - 1])) ?>"
           class="page-btn <?= $pagina <= 1 ? 'disabled' : '' ?>">&lsaquo;</a>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
        <a href="?<?= http_build_query(array_merge($url_params, ['page' => $i])) ?>"
           class="page-btn <?= $i == $pagina ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <a href="?<?= http_build_query(array_merge($url_params, ['page' => $pagina + 1])) ?>"
           class="page-btn <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">&rsaquo;</a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
