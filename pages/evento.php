<?php
// pages/evento.php — Detalhe de um evento
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /pages/eventos.php');
    exit;
}

$db   = getDB();
$stmt = $db->prepare("
    SELECT e.*, c.nome AS cat_nome, c.icone AS cat_icone, c.cor AS cat_cor, u.nome AS org_nome
    FROM eventos e
    JOIN categorias_evento c ON e.categoria_id = c.id
    JOIN utilizadores u ON e.organizador_id = u.id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$ev = $stmt->fetch();

if (!$ev) {
    header('Location: /errors/404.php');
    exit;
}

$inscritos  = countInscritos($id);
$vagas_disp = max(0, $ev['vagas'] - $inscritos);
$percentagem = $ev['vagas'] > 0 ? round(($inscritos / $ev['vagas']) * 100) : 0;
$inscricao  = isLoggedIn() ? isInscrito(getCurrentUserId(), $id) : null;

$pageTitle = $ev['titulo'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="event-detail">
    <?php if ($ev['imagem']): ?>
    <img class="event-detail__img" src="/assets/uploads/events/<?= e($ev['imagem']) ?>" alt="<?= e($ev['titulo']) ?>">
    <?php else: ?>
    <div class="event-detail__img-placeholder"><?= $ev['cat_icone'] ?></div>
    <?php endif; ?>

    <div class="event-detail__header fade-in">
        <div class="event-detail__cat">
            <span class="event-card__cat" style="background:<?= e($ev['cat_cor']) ?>22;color:<?= e($ev['cat_cor']) ?>">
                <?= $ev['cat_icone'] ?> <?= e($ev['cat_nome']) ?>
            </span>
            <span class="badge badge--<?= e($ev['estado']) ?>" style="margin-left:8px"><?= ucfirst($ev['estado']) ?></span>
        </div>
        <h1 class="event-detail__title"><?= e($ev['titulo']) ?></h1>

        <div class="event-detail__meta-grid">
            <div class="meta-item">
                <div class="meta-item__icon">📅</div>
                <div class="meta-item__label">Data e hora</div>
                <div class="meta-item__value"><?= formatDate($ev['data_inicio']) ?></div>
                <?php if ($ev['data_fim']): ?>
                <div style="font-size:.8rem;color:var(--clr-muted)">até <?= formatDate($ev['data_fim']) ?></div>
                <?php endif; ?>
            </div>
            <div class="meta-item">
                <div class="meta-item__icon">📍</div>
                <div class="meta-item__label">Local</div>
                <div class="meta-item__value"><?= e($ev['local']) ?></div>
            </div>
            <div class="meta-item">
                <div class="meta-item__icon">💺</div>
                <div class="meta-item__label">Vagas</div>
                <div class="meta-item__value"><?= $vagas_disp ?> disponíveis de <?= $ev['vagas'] ?></div>
            </div>
            <div class="meta-item">
                <div class="meta-item__icon">👤</div>
                <div class="meta-item__label">Organizador</div>
                <div class="meta-item__value"><?= e($ev['org_nome']) ?></div>
            </div>
        </div>

        <div style="background:var(--clr-bg2);border:1px solid var(--clr-border);border-radius:16px;padding:24px;margin-bottom:24px">
            <h3 style="margin-bottom:12px">Sobre o evento</h3>
            <p style="color:var(--clr-muted);line-height:1.8"><?= nl2br(e($ev['descricao'])) ?></p>
        </div>

        <!-- Barra de ocupação -->
        <div class="vagas-bar">
            <div class="vagas-bar__label">
                <span><?= $inscritos ?> inscritos</span>
                <span><?= $percentagem ?>% ocupado</span>
            </div>
            <div class="vagas-bar__track">
                <?php
                if ($vagas_disp <= 0) $cor = 'full';
                elseif ($percentagem >= 80) $cor = 'few';
                else $cor = 'ok';
                ?>
                <div class="vagas-bar__fill vagas-bar__fill--<?= $cor ?>" style="width:<?= $percentagem ?>%"></div>
            </div>
        </div>

        <!-- Caixa de inscrição -->
        <div class="inscricao-box fade-in-2">

            <?php if ($ev['estado'] !== 'ativo'): ?>
                <div style="text-align:center;padding:16px;color:var(--clr-muted)">
                    🚫 Este evento está <?= e($ev['estado']) ?> e não aceita inscrições.
                </div>

            <?php elseif (!isLoggedIn()): ?>
                <h3>Inscreve-te neste evento</h3>
                <p style="color:var(--clr-muted);margin-bottom:16px">Cria uma conta ou entra para te inscreveres.</p>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="/pages/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn--primary">Entrar</a>
                    <a href="/pages/registo.php" class="btn btn--ghost">Criar conta</a>
                </div>

            <?php elseif ($inscricao && $inscricao['estado'] === 'confirmada'): ?>
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                    <span style="font-size:2rem">✅</span>
                    <div>
                        <strong>Estás inscrito(a)!</strong>
                        <p style="font-size:.85rem;color:var(--clr-muted)">
                            Inscrição confirmada em <?= formatDate($inscricao['criado_em']) ?>.
                        </p>
                    </div>
                </div>
                <form method="POST" action="/pages/inscricao.php"
                      onsubmit="return confirm('Tens a certeza que queres cancelar a inscrição?')">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="evento_id" value="<?= $ev['id'] ?>">
                    <input type="hidden" name="action" value="cancelar">
                    <button type="submit" class="btn btn--danger btn--sm">Cancelar inscrição</button>
                </form>

            <?php elseif ($inscricao && $inscricao['estado'] === 'cancelada'): ?>
                <p style="color:var(--clr-muted);margin-bottom:16px">Tinhas cancelado a inscrição neste evento.</p>
                <?php if ($vagas_disp > 0): ?>
                <form method="POST" action="/pages/inscricao.php">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="evento_id" value="<?= $ev['id'] ?>">
                    <input type="hidden" name="action" value="inscrever">
                    <button type="submit" class="btn btn--primary">🎟️ Inscrever novamente</button>
                </form>
                <?php else: ?>
                <span class="vagas-badge vagas-badge--full">Esgotado</span>
                <?php endif; ?>

            <?php elseif ($vagas_disp > 0): ?>
                <h3>Inscreve-te neste evento</h3>
                <p style="color:var(--clr-muted);margin-bottom:16px">
                    Ainda há <?= $vagas_disp ?> vaga<?= $vagas_disp != 1 ? 's' : '' ?> disponível<?= $vagas_disp != 1 ? 'eis' : '' ?>.
                </p>
                <form method="POST" action="/pages/inscricao.php">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="evento_id" value="<?= $ev['id'] ?>">
                    <input type="hidden" name="action" value="inscrever">
                    <button type="submit" class="btn btn--primary btn--lg">🎟️ Inscrever-me gratuitamente</button>
                </form>

            <?php else: ?>
                <div style="text-align:center;padding:16px">
                    <span class="vagas-badge vagas-badge--full" style="font-size:.95rem;padding:8px 20px">🎫 Esgotado</span>
                    <p style="margin-top:12px;color:var(--clr-muted)">Não há vagas disponíveis para este evento.</p>
                </div>
            <?php endif; ?>

        </div>

        <!-- Ações do organizador -->
        <?php if (isLoggedIn() && (isAdmin() || getCurrentUserId() == $ev['organizador_id'])): ?>
        <div style="margin-top:24px;display:flex;gap:10px;flex-wrap:wrap;padding:20px;background:var(--clr-bg3);border-radius:12px;">
            <strong style="width:100%;font-size:.82rem;color:var(--clr-muted);text-transform:uppercase;letter-spacing:.05em">Ações do organizador</strong>
            <a href="/pages/editar_evento.php?id=<?= $ev['id'] ?>" class="btn btn--ghost btn--sm">✏️ Editar</a>
            <a href="/pages/presencas.php?id=<?= $ev['id'] ?>" class="btn btn--ghost btn--sm">📋 Lista de presença</a>
            <a href="/pages/exportar_csv.php?id=<?= $ev['id'] ?>" class="btn btn--ghost btn--sm">⬇️ Exportar CSV</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
