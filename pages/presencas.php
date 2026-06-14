<?php
// pages/presencas.php — Lista de presenças de um evento (gerida pelo organizador)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Só organizadores e admins podem gerir presenças
requireOrganizador();

// Lê o ID do evento da URL (ex: /presencas.php?id=3)
$id = (int)($_GET['id'] ?? 0);
$db = getDB();

// Procura o evento na base de dados
$evStmt = $db->prepare("SELECT * FROM eventos WHERE id = ?");
$evStmt->execute([$id]);
$ev = $evStmt->fetch();

// Se o evento não existir, mostra 404
if (!$ev) { header('Location: /errors/404.php'); exit; }

// Verifica que o organizador é o dono do evento (ou é admin)
if (!isAdmin() && $ev['organizador_id'] !== getCurrentUserId()) {
    header('Location: /errors/403.php'); exit;
}

// Obtém todos os inscritos confirmados e os que já têm presença marcada
$stmt = $db->prepare("
    SELECT i.*, u.nome, u.email
    FROM inscricoes i
    JOIN utilizadores u ON i.utilizador_id = u.id
    WHERE i.evento_id = ? AND i.estado IN ('confirmada', 'presenca')
    ORDER BY u.nome ASC  -- Ordena alfabeticamente pelo nome do participante
");
$stmt->execute([$id]);
$inscritos = $stmt->fetchAll();

// Conta quantos inscritos já têm presença marcada
// array_filter() filtra o array mantendo só os que têm estado = 'presenca'
$presentes = count(array_filter($inscritos, fn($i) => $i['estado'] === 'presenca'));

// Define o título da página com o nome do evento
$pageTitle = 'Presenças — ' . $ev['titulo'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding:48px 24px">

    <!-- Campo escondido com o token CSRF para ser usado pelo JavaScript nas chamadas AJAX -->
    <input type="hidden" id="csrf" value="<?= csrfToken() ?>">

    <!-- Cabeçalho da página com estatísticas rápidas -->
    <div style="display:flex;align-items:start;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:16px">
        <div>
            <a href="/pages/evento.php?id=<?= $id ?>" style="color:var(--clr-muted);font-size:.85rem">← Voltar ao evento</a>
            <h1 style="margin-top:8px">📋 Lista de presença</h1>
            <p style="color:var(--clr-muted)"><?= e($ev['titulo']) ?></p>
        </div>
        <!-- Contador: presentes / total de inscritos -->
        <div style="text-align:right">
            <div style="font-size:2rem;font-weight:800;color:var(--clr-accent)"><?= $presentes ?>/<?= count($inscritos) ?></div>
            <div style="font-size:.82rem;color:var(--clr-muted)">presentes</div>
            <a href="/pages/exportar_csv.php?id=<?= $id ?>" class="btn btn--ghost btn--sm" style="margin-top:8px">⬇️ Exportar CSV</a>
        </div>
    </div>

    <!-- Se não há inscritos, mostra estado vazio -->
    <?php if (empty($inscritos)): ?>
    <div class="empty-state">
        <div class="empty-state__icon">👥</div>
        <h3>Sem inscrições confirmadas</h3>
    </div>
    <?php else: ?>

    <!-- Campo de pesquisa para filtrar inscritos por nome (filtrado com JavaScript) -->
    <div style="margin-bottom:16px">
        <input type="text" id="searchPresencas" class="form-control" style="max-width:300px"
               placeholder="🔍 Filtrar por nome...">
    </div>

    <!-- Tabela de inscritos -->
    <div class="table-wrap">
        <table id="tabelaPresencas">
            <thead>
                <tr>
                    <th>#</th>         <!-- Número de ordem -->
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Inscrito em</th>
                    <th>Estado</th>
                    <th>Presença</th>  <!-- Botão AJAX para marcar/desmarcar -->
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inscritos as $i => $ins): ?>
                <!-- data-nome é usado pelo JavaScript para filtrar a tabela -->
                <tr data-nome="<?= strtolower(e($ins['nome'])) ?>">
                    <td><?= $i + 1 ?></td>  <!-- Número de ordem (começa em 1) -->
                    <td><strong><?= e($ins['nome']) ?></strong></td>
                    <td><?= e($ins['email']) ?></td>
                    <td><?= formatDate($ins['criado_em']) ?></td>
                    <td>
                        <!-- Badge com o estado atual da inscrição -->
                        <span class="badge badge--<?= e($ins['estado']) ?>"><?= ucfirst($ins['estado']) ?></span>
                    </td>
                    <td>
                        <!-- Botão AJAX: chama togglePresenca() em main.js -->
                        <!-- Muda de estilo consoante o estado atual da inscrição -->
                        <button class="btn btn--sm <?= $ins['estado'] === 'presenca' ? 'btn--success' : 'btn--ghost' ?>"
                                onclick="togglePresenca(<?= $ins['id'] ?>, this)">
                            <?= $ins['estado'] === 'presenca' ? '✓ Presente' : 'Marcar' ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- JavaScript inline para filtrar a tabela por nome em tempo real -->
<script>
// Ouve eventos de digitação no campo de pesquisa
document.getElementById('searchPresencas')?.addEventListener('input', function() {
    const q = this.value.toLowerCase(); // Texto em minúsculas para comparação
    // Para cada linha da tabela, mostra ou esconde consoante o nome contém o texto
    document.querySelectorAll('#tabelaPresencas tbody tr').forEach(function(tr) {
        tr.style.display = tr.dataset.nome.includes(q) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
