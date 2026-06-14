<?php
// pages/meus_eventos.php — Lista de eventos do organizador (ou todos os eventos para o admin)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireOrganizador();

$db  = getDB();
$uid = getCurrentUserId();

// O admin vê todos os eventos; o organizador só vê os seus
if (isAdmin()) {
    $stmt = $db->query("
        SELECT e.*, c.nome AS cat_nome, c.icone AS cat_icone, u.nome AS org_nome,
               (SELECT COUNT(*) FROM inscricoes i WHERE i.evento_id = e.id AND i.estado = 'confirmada') AS inscritos
        FROM eventos e
        JOIN categorias_evento c ON e.categoria_id = c.id
        JOIN utilizadores u ON e.organizador_id = u.id
        ORDER BY e.criado_em DESC
    ");
} else {
    $stmt = $db->prepare("
        SELECT e.*, c.nome AS cat_nome, c.icone AS cat_icone, u.nome AS org_nome,
               (SELECT COUNT(*) FROM inscricoes i WHERE i.evento_id = e.id AND i.estado = 'confirmada') AS inscritos
        FROM eventos e
        JOIN categorias_evento c ON e.categoria_id = c.id
        JOIN utilizadores u ON e.organizador_id = u.id
        WHERE e.organizador_id = ?
        ORDER BY e.criado_em DESC
    ");
    $stmt->execute([$uid]);
}
$eventos = $stmt->fetchAll();

$pageTitle = 'Os meus eventos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding:48px 24px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:16px">
        <div>
            <h1>Os meus eventos</h1>
            <p style="color:var(--clr-muted)"><?= count($eventos) ?> evento<?= count($eventos) != 1 ? 's' : '' ?> no total</p>
        </div>
        <a href="/pages/criar_evento.php" class="btn btn--primary">+ Criar evento</a>
    </div>

    <?php if (empty($eventos)): ?>
    <div class="empty-state">
        <h3>Ainda nao criaste eventos</h3>
        <p>Cria o teu primeiro evento e comeca a receber inscricoes!</p>
        <a href="/pages/criar_evento.php" class="btn btn--primary" style="margin-top:16px">Criar evento</a>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Data</th>
                    <th>Inscritos</th>
                    <th>Vagas livres</th>
                    <th>Estado</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventos as $ev):
                    $livres = $ev['vagas'] - $ev['inscritos'];
                    if ($livres <= 0) {
                        $vagas_class = 'full';
                    } elseif ($livres <= $ev['vagas'] * 0.2) {
                        $vagas_class = 'few';
                    } else {
                        $vagas_class = 'ok';
                    }
                ?>
                <tr>
                    <td>
                        <strong><?= e($ev['titulo']) ?></strong>
                        <br><small style="color:var(--clr-muted)"><?= e($ev['cat_icone']) ?> <?= e($ev['cat_nome']) ?></small>
                    </td>
                    <td><?= formatDate($ev['data_inicio'], false) ?></td>
                    <td>
                        <strong><?= $ev['inscritos'] ?></strong>
                        <span style="color:var(--clr-muted);font-size:.82rem"> / <?= $ev['vagas'] ?></span>
                    </td>
                    <td>
                        <span class="vagas-badge vagas-badge--<?= $vagas_class ?>">
                            <?= max(0, $livres) ?>
                        </span>
                    </td>
                    <td><span class="badge badge--<?= e($ev['estado']) ?>"><?= ucfirst($ev['estado']) ?></span></td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <a href="/pages/evento.php?id=<?= $ev['id'] ?>"        class="btn btn--ghost btn--sm">Ver</a>
                            <a href="/pages/editar_evento.php?id=<?= $ev['id'] ?>" class="btn btn--ghost btn--sm">Editar</a>
                            <a href="/pages/presencas.php?id=<?= $ev['id'] ?>"     class="btn btn--ghost btn--sm">Presencas</a>
                            <a href="/pages/exportar_csv.php?id=<?= $ev['id'] ?>"  class="btn btn--ghost btn--sm">CSV</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
