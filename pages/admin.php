<?php
// pages/admin.php — Painel de administração (apenas para utilizadores com papel 'admin')
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAdmin();

$db = getDB();

// Recolhe estatísticas globais da aplicação
$stats = [
    'utilizadores' => $db->query("SELECT COUNT(*) FROM utilizadores")->fetchColumn(),
    'eventos'      => $db->query("SELECT COUNT(*) FROM eventos")->fetchColumn(),
    'inscricoes'   => $db->query("SELECT COUNT(*) FROM inscricoes WHERE estado = 'confirmada'")->fetchColumn(),
    'presencas'    => $db->query("SELECT COUNT(*) FROM inscricoes WHERE estado = 'presenca'")->fetchColumn(),
];

// Obtém os últimos 20 utilizadores
$users = $db->query("SELECT * FROM utilizadores ORDER BY criado_em DESC LIMIT 20")->fetchAll();

// Obtém os últimos 20 eventos com nome do organizador e contagem de inscritos
$eventos = $db->query("
    SELECT e.*, u.nome AS org_nome,
           (SELECT COUNT(*) FROM inscricoes i WHERE i.evento_id = e.id AND i.estado = 'confirmada') AS inscritos
    FROM eventos e
    JOIN utilizadores u ON e.organizador_id = u.id
    ORDER BY e.criado_em DESC LIMIT 20
")->fetchAll();

// Processa ações POST (ativar/desativar utilizador, alterar papel)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();
    $action = $_POST['action'] ?? '';

    // Ativar ou desativar conta de utilizador
    if ($action === 'toggle_user') {
        $uid   = (int)$_POST['user_id'];
        $ativo = (int)$_POST['ativo'];
        // Inverte o estado: ativo (1) passa a inativo (0) e vice-versa
        $db->prepare("UPDATE utilizadores SET ativo = ? WHERE id = ?")
           ->execute([$ativo ? 0 : 1, $uid]);
        redirectWith('/pages/admin.php', 'success', 'Utilizador atualizado.');
    }

    // Alterar papel de um utilizador
    if ($action === 'change_role') {
        $uid   = (int)$_POST['user_id'];
        $papel = $_POST['papel'];
        if (in_array($papel, ['admin', 'organizador', 'participante'])) {
            $db->prepare("UPDATE utilizadores SET papel = ? WHERE id = ?")
               ->execute([$papel, $uid]);
            redirectWith('/pages/admin.php', 'success', 'Papel alterado.');
        }
    }
}

$pageTitle = 'Administracao';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding:48px 24px">
    <h1 style="margin-bottom:8px">Painel de Administracao</h1>
    <p style="color:var(--clr-muted);margin-bottom:40px">Gestao global do EventFlow</p>

    <!-- Estatísticas gerais -->
    <div class="stats-strip" style="margin-bottom:48px">
        <div class="stat-item"><div class="stat-item__num"><?= $stats['utilizadores'] ?></div><div class="stat-item__lbl">Utilizadores</div></div>
        <div class="stat-item"><div class="stat-item__num"><?= $stats['eventos'] ?></div><div class="stat-item__lbl">Eventos</div></div>
        <div class="stat-item"><div class="stat-item__num"><?= $stats['inscricoes'] ?></div><div class="stat-item__lbl">Inscricoes</div></div>
        <div class="stat-item"><div class="stat-item__num"><?= $stats['presencas'] ?></div><div class="stat-item__lbl">Presencas</div></div>
    </div>

    <!-- Tabela de utilizadores -->
    <h2 style="margin-bottom:16px">Utilizadores</h2>
    <div class="table-wrap" style="margin-bottom:48px">
        <table>
            <thead>
                <tr><th>Nome</th><th>Email</th><th>Papel</th><th>Estado</th><th>Desde</th><th>Acoes</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><strong><?= e($u['nome']) ?></strong></td>
                    <td><?= e($u['email']) ?></td>
                    <td>
                        <!-- Select que muda o papel ao selecionar -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="change_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <!-- disabled impede o admin de alterar o seu próprio papel -->
                            <select name="papel" class="form-control" style="padding:4px 8px;font-size:.82rem"
                                    onchange="this.form.submit()"
                                    <?= $u['id'] == getCurrentUserId() ? 'disabled' : '' ?>>
                                <option value="admin"        <?= $u['papel'] === 'admin'        ? 'selected' : '' ?>>Admin</option>
                                <option value="organizador"  <?= $u['papel'] === 'organizador'  ? 'selected' : '' ?>>Organizador</option>
                                <option value="participante" <?= $u['papel'] === 'participante' ? 'selected' : '' ?>>Participante</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <span class="badge <?= $u['ativo'] ? 'badge--confirmada' : 'badge--cancelada' ?>">
                            <?= $u['ativo'] ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </td>
                    <td><?= formatDate($u['criado_em'], false) ?></td>
                    <td>
                        <?php if ($u['id'] !== getCurrentUserId()): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="action" value="toggle_user">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="ativo" value="<?= $u['ativo'] ?>">
                            <button type="submit" class="btn btn--sm <?= $u['ativo'] ? 'btn--danger' : 'btn--success' ?>">
                                <?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tabela de eventos -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h2>Eventos</h2>
        <a href="/pages/criar_evento.php" class="btn btn--primary btn--sm">+ Criar</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Titulo</th><th>Organizador</th><th>Data</th><th>Inscritos</th><th>Estado</th><th>Acoes</th></tr>
            </thead>
            <tbody>
                <?php foreach ($eventos as $ev): ?>
                <tr>
                    <td><strong><?= e($ev['titulo']) ?></strong></td>
                    <td><?= e($ev['org_nome']) ?></td>
                    <td><?= formatDate($ev['data_inicio'], false) ?></td>
                    <td><?= $ev['inscritos'] ?> / <?= $ev['vagas'] ?></td>
                    <td><span class="badge badge--<?= e($ev['estado']) ?>"><?= ucfirst($ev['estado']) ?></span></td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="/pages/evento.php?id=<?= $ev['id'] ?>"        class="btn btn--ghost btn--sm">Ver</a>
                            <a href="/pages/editar_evento.php?id=<?= $ev['id'] ?>" class="btn btn--ghost btn--sm">Editar</a>
                            <a href="/pages/exportar_csv.php?id=<?= $ev['id'] ?>"  class="btn btn--ghost btn--sm">CSV</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
