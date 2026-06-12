<?php
// pages/minhas_inscricoes.php — Inscrições do utilizador autenticado
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireLogin();

$db  = getDB();
$uid = getCurrentUserId();

$stmt = $db->prepare("
    SELECT i.*, e.titulo, e.local, e.data_inicio, e.imagem,
           c.nome AS cat_nome, c.icone AS cat_icone, c.cor AS cat_cor
    FROM inscricoes i
    JOIN eventos e ON i.evento_id = e.id
    JOIN categorias_evento c ON e.categoria_id = c.id
    WHERE i.utilizador_id = ?
    ORDER BY i.criado_em DESC
");
$stmt->execute([$uid]);
$inscricoes = $stmt->fetchAll();

$pageTitle = 'As minhas inscrições';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding:48px 24px">
    <div style="margin-bottom:32px">
        <h1>As minhas inscrições</h1>
        <p style="color:var(--clr-muted)"><?= count($inscricoes) ?> inscrição<?= count($inscricoes) != 1 ? 'ões' : '' ?> no total</p>
    </div>

    <?php if (empty($inscricoes)): ?>
    <div class="empty-state">
        <div class="empty-state__icon">🎫</div>
        <h3>Ainda não tens inscrições</h3>
        <p>Explora os eventos disponíveis e inscreve-te!</p>
        <a href="/pages/eventos.php" class="btn btn--primary" style="margin-top:16px">Ver eventos</a>
    </div>
    <?php else: ?>
    <div class="grid-events">
        <?php foreach ($inscricoes as $ins):
            // Texto e badge do estado da inscrição
            if ($ins['estado'] === 'confirmada') {
                $estado_txt   = '✅ Confirmada';
                $estado_class = 'confirmada';
            } elseif ($ins['estado'] === 'cancelada') {
                $estado_txt   = '❌ Cancelada';
                $estado_class = 'cancelada';
            } elseif ($ins['estado'] === 'presenca') {
                $estado_txt   = '🎯 Presente';
                $estado_class = 'presenca';
            } else {
                $estado_txt   = $ins['estado'];
                $estado_class = '';
            }
        ?>
        <div class="card event-card">
            <?php if ($ins['imagem']): ?>
            <img class="event-card__img" src="/assets/uploads/events/<?= e($ins['imagem']) ?>" alt="">
            <?php else: ?>
            <div class="event-card__img-placeholder"><?= $ins['cat_icone'] ?></div>
            <?php endif; ?>
            <div class="event-card__body">
                <span class="event-card__cat" style="background:<?= e($ins['cat_cor']) ?>22;color:<?= e($ins['cat_cor']) ?>">
                    <?= $ins['cat_icone'] ?> <?= e($ins['cat_nome']) ?>
                </span>
                <h3 class="event-card__title"><?= e($ins['titulo']) ?></h3>
                <div class="event-card__meta">
                    <div class="event-card__meta-row">📅 <?= formatDate($ins['data_inicio']) ?></div>
                    <div class="event-card__meta-row">📍 <?= e($ins['local']) ?></div>
                    <div class="event-card__meta-row">🕐 Inscrito em <?= formatDate($ins['criado_em'], false) ?></div>
                </div>
                <div class="event-card__footer">
                    <span class="badge badge--<?= $estado_class ?>"><?= $estado_txt ?></span>
                    <a href="/pages/evento.php?id=<?= $ins['evento_id'] ?>" class="btn btn--ghost btn--sm">Ver evento</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
