<?php
/**
 * EventFlow - Lista de presenças (organizador)
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
    SELECT i.*, u.nome, u.email
    FROM inscricoes i
    JOIN utilizadores u ON i.utilizador_id=u.id
    WHERE i.evento_id=? AND i.estado IN ('confirmada','presenca')
    ORDER BY u.nome ASC
");
$stmt->execute([$id]);
$inscritos = $stmt->fetchAll();

$presentes = count(array_filter($inscritos, fn($i) => $i['estado']==='presenca'));

$pageTitle = 'Presenças — ' . e($ev['titulo']);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding:48px 24px">
    <input type="hidden" id="csrf" value="<?= csrfToken() ?>">

    <div style="display:flex;align-items:start;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:16px">
        <div>
            <a href="/pages/evento.php?id=<?= $id ?>" style="color:var(--clr-muted);font-size:.85rem">← Voltar ao evento</a>
            <h1 style="margin-top:8px">📋 Lista de presença</h1>
            <p style="color:var(--clr-muted)"><?= e($ev['titulo']) ?></p>
        </div>
        <div style="text-align:right">
            <div style="font-size:2rem;font-weight:800;color:var(--clr-accent)"><?= $presentes ?>/<?= count($inscritos) ?></div>
            <div style="font-size:.82rem;color:var(--clr-muted)">presentes</div>
            <a href="/pages/exportar_csv.php?id=<?= $id ?>" class="btn btn--ghost btn--sm" style="margin-top:8px">⬇️ Exportar CSV</a>
        </div>
    </div>

    <?php if (empty($inscritos)): ?>
    <div class="empty-state">
        <div class="empty-state__icon">👥</div>
        <h3>Sem inscrições confirmadas</h3>
    </div>
    <?php else: ?>
    <div style="margin-bottom:16px">
        <input type="text" id="searchPresencas" class="form-control" style="max-width:300px"
               placeholder="🔍 Filtrar por nome...">
    </div>
    <div class="table-wrap">
        <table id="tabelaPresencas">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Inscrito em</th>
                    <th>Estado</th>
                    <th>Presença</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inscritos as $i => $ins): ?>
                <tr data-nome="<?= strtolower(e($ins['nome'])) ?>">
                    <td><?= $i+1 ?></td>
                    <td><strong><?= e($ins['nome']) ?></strong></td>
                    <td><?= e($ins['email']) ?></td>
                    <td><?= formatDate($ins['criado_em']) ?></td>
                    <td><span class="badge badge--<?= e($ins['estado']) ?>"><?= ucfirst($ins['estado']) ?></span></td>
                    <td>
                        <button class="btn btn--sm <?= $ins['estado']==='presenca'?'btn--success':'btn--ghost' ?>"
                                onclick="togglePresenca(<?= $ins['id'] ?>, this)">
                            <?= $ins['estado']==='presenca' ? '✓ Presente' : 'Marcar' ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('searchPresencas')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tabelaPresencas tbody tr').forEach(tr => {
        tr.style.display = tr.dataset.nome.includes(q) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
