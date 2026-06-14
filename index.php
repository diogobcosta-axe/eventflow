<?php
// index.php — Página inicial
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$db = getDB();

// Próximos eventos ativos (máximo 6)
$stmt = $db->query("
    SELECT e.*, c.nome AS cat_nome, c.icone AS cat_icone, c.cor AS cat_cor, u.nome AS org_nome,
           (SELECT COUNT(*) FROM inscricoes i WHERE i.evento_id = e.id AND i.estado = 'confirmada') AS inscritos
    FROM eventos e
    JOIN categorias_evento c ON e.categoria_id = c.id
    JOIN utilizadores u ON e.organizador_id = u.id
    WHERE e.estado = 'ativo' AND e.data_inicio >= datetime('now')
    ORDER BY e.data_inicio ASC
    LIMIT 6
");
$eventos = $stmt->fetchAll();

// Estatísticas gerais
$total_eventos      = $db->query("SELECT COUNT(*) FROM eventos WHERE estado = 'ativo'")->fetchColumn();
$total_inscricoes   = $db->query("SELECT COUNT(*) FROM inscricoes WHERE estado = 'confirmada'")->fetchColumn();
$total_utilizadores = $db->query("SELECT COUNT(*) FROM utilizadores WHERE ativo = 1")->fetchColumn();

// Categorias para os filtros rápidos
$categorias = $db->query("SELECT * FROM categorias_evento ORDER BY nome")->fetchAll();

$pageTitle = 'Inicio';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero">
    <div class="hero__content fade-in">
        <span class="hero__eyebrow">EventFlow Portugal</span>
        <h1 class="hero__title">Descobre os melhores <span>eventos</span> perto de ti</h1>
        <p class="hero__sub">Musica, tecnologia, cultura, desporto &mdash; encontra o teu proximo evento e inscreve-te em segundos.</p>
        <div class="hero__actions">
            <a href="/pages/eventos.php" class="btn btn--primary btn--lg">Explorar eventos</a>
            <?php if (!isLoggedIn()): ?>
            <a href="/pages/registo.php" class="btn btn--ghost btn--lg">Criar conta gratis</a>
            <?php endif; ?>
        </div>

        <form class="search-bar" action="/pages/eventos.php" method="GET">
            <input type="text" name="q" placeholder="Pesquisar eventos, locais ou categorias..." autocomplete="off">
            <button type="submit" class="btn btn--primary">Pesquisar</button>
        </form>
    </div>
</section>

<!-- Estatísticas -->
<div class="container">
    <div class="stats-strip fade-in-2">
        <div class="stat-item">
            <div class="stat-item__num"><?= $total_eventos ?></div>
            <div class="stat-item__lbl">Eventos ativos</div>
        </div>
        <div class="stat-item">
            <div class="stat-item__num"><?= $total_inscricoes ?></div>
            <div class="stat-item__lbl">Inscricoes feitas</div>
        </div>
        <div class="stat-item">
            <div class="stat-item__num"><?= $total_utilizadores ?></div>
            <div class="stat-item__lbl">Utilizadores</div>
        </div>
    </div>
</div>

<!-- Filtros por categoria -->
<div class="container">
    <div class="categories-strip fade-in-3">
        <a href="/pages/eventos.php" class="cat-pill active">Todos</a>
        <?php foreach ($categorias as $cat): ?>
        <a href="/pages/eventos.php?categoria=<?= $cat['id'] ?>" class="cat-pill">
            <?= $cat['icone'] ?> <?= e($cat['nome']) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Próximos eventos -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Proximos <span>eventos</span></h2>
            <a href="/pages/eventos.php" class="see-all">Ver todos &rarr;</a>
        </div>

        <?php if (empty($eventos)): ?>
        <div class="empty-state">
            <h3>Sem eventos agendados</h3>
            <p>Ainda nao ha eventos publicados. Volta mais tarde!</p>
            <?php if (isOrganizador()): ?>
            <a href="/pages/criar_evento.php" class="btn btn--primary" style="margin-top:16px">Criar o primeiro evento</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="grid-events">
            <?php foreach ($eventos as $ev):
                $vagas_disp  = $ev['vagas'] - $ev['inscritos'];
                $percentagem = $ev['vagas'] > 0 ? round(($ev['inscritos'] / $ev['vagas']) * 100) : 0;
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
        <?php endif; ?>
    </div>
</section>

<!-- Call-to-action para organizadores -->
<?php if (!isOrganizador()): ?>
<section class="section" style="background: linear-gradient(135deg, rgba(255,92,40,.08) 0%, rgba(255,179,71,.05) 100%); border-top: 1px solid var(--clr-border); border-bottom: 1px solid var(--clr-border);">
    <div class="container" style="text-align:center; max-width:600px; margin:0 auto;">
        <h2 style="font-size:2rem; margin-bottom:12px;">Tens um evento para divulgar?</h2>
        <p style="color:var(--clr-muted); margin-bottom:28px;">Cria a tua conta como organizador e comeca a gerir eventos com o EventFlow &mdash; gratuito, simples e poderoso.</p>
        <a href="/pages/registo.php" class="btn btn--primary btn--lg">Comecar agora</a>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
