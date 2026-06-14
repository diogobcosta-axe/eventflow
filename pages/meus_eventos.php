<?php
// pages/meus_eventos.php — Lista de eventos do organizador (ou todos os eventos para o admin)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Só organizadores e admins podem aceder a esta página
requireOrganizador();

$db  = getDB();
$uid = getCurrentUserId(); // ID do utilizador autenticado

// O admin vê todos os eventos de todos os organizadores
// O organizador só vê os seus próprios eventos
if (isAdmin()) {
    // Sem WHERE: devolve todos os eventos com nome do organizador
    $stmt = $db->query("
        SELECT e.*, c.nome AS cat_nome, c.icone AS cat_icone, u.nome AS org_nome,
               (SELECT COUNT(*) FROM inscricoes i WHERE i.evento_id = e.id AND i.estado = 'confirmada') AS inscritos
        FROM eventos e
        JOIN categorias_evento c ON e.categoria_id = c.id
        JOIN utilizadores u ON e.organizador_id = u.id
        ORDER BY e.criado_em DESC
    ");
} else {
    // Com WHERE: só os eventos deste organizador
    $stmt = $db->prepare("
        SELECT e.*, c.nome AS cat_nome, c.icone AS cat_icone, u.nome AS org_nome,
               (SELECT COUNT(*) FROM inscricoes i WHERE i.evento_id = e.id AND i.estado = 'confirmada') AS inscritos
        FROM eventos e
        JOIN categorias_evento c ON e.categoria_id = c.id
        JOIN utilizadores u ON e.organizador_id = u.id
        WHERE e.organizador_id = ?
        ORDER BY e.criado_em DESC
    ");
    $stmt->execute([$uid]); // Passa o ID do organizador como parâmetro seguro
}
$eventos = $stmt->fetchAll(); // Obtém todos os resultados como array de arrays

$pageTitle = 'Os meus eventos';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding:48px 24px">

    <!-- Cabeçalho com contador e botão de criar -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:16px">
        <div>
            <h1>Os meus eventos</h1>
            <!-- Conta os eventos encontrados (usa operador ternário para singular/plural) -->
            <p style="color:var(--clr-muted)"><?= count($eventos) ?> evento<?= count($eventos) != 1 ? 's' : '' ?> no total</p>
        </div>
        <a href="/pages/criar_evento.php" class="btn btn--primary">+ Criar evento</a>
    </div>

    <!-- Se não há eventos, mostra estado vazio -->
    <?php if (empty($eventos)): ?>
    <div class="empty-state">
        <div class="empty-state__icon">📅</div>
        <h3>Ainda não criaste eventos</h3>
        <p>Cria o teu primeiro evento e começa a receber inscrições!</p>
        <a href="/pages/criar_evento.php" class="btn btn--primary" style="margin-top:16px">Criar evento</a>
    </div>
    <?php else: ?>

    <!-- Tabela de eventos -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Data</th>
                    <th>Inscritos</th>
                    <th>Vagas livres</th>
                    <th>Estado</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventos as $ev):
                    // Calcula vagas livres: total - inscritos confirmados
                    $livres = $ev['vagas'] - $ev['inscritos'];

                    // Define a classe CSS do badge de vagas consoante a ocupação
                    if ($livres <= 0) {
                        $vagas_class = 'full';    // Vermelho: esgotado
                    } elseif ($livres <= $ev['vagas'] * 0.2) {
                        $vagas_class = 'few';     // Laranja: menos de 20% disponíveis
                    } else {
                        $vagas_class = 'ok';      // Verde: há vagas suficientes
                    }
                ?>
                <tr>
                    <td>
                        <strong><?= e($ev['titulo']) ?></strong>
                        <!-- Categoria do evento em texto pequeno -->
                        <br><small style="color:var(--clr-muted)"><?= $ev['cat_icone'] ?> <?= e($ev['cat_nome']) ?></small>
                    </td>
                    <td><?= formatDate($ev['data_inicio'], false) ?></td>
                    <td>
                        <strong><?= $ev['inscritos'] ?></strong>
                        <!-- Total de vagas em tom mais suave -->
                        <span style="color:var(--clr-muted);font-size:.82rem"> / <?= $ev['vagas'] ?></span>
                    </td>
                    <td>
                        <!-- Badge colorido com o número de vagas livres -->
                        <span class="vagas-badge vagas-badge--<?= $vagas_class ?>">
                            <?= max(0, $livres) ?>
                        </span>
                    </td>
                    <td><span class="badge badge--<?= e($ev['estado']) ?>"><?= ucfirst($ev['estado']) ?></span></td>
                    <td>
                        <!-- Botões de ação rápida -->
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <a href="/pages/evento.php?id=<?= $ev['id'] ?>"        class="btn btn--ghost btn--sm">👁️</a>
                            <a href="/pages/editar_evento.php?id=<?= $ev['id'] ?>" class="btn btn--ghost btn--sm">✏️</a>
                            <a href="/pages/presencas.php?id=<?= $ev['id'] ?>"     class="btn btn--ghost btn--sm">📋</a>
                            <a href="/pages/exportar_csv.php?id=<?= $ev['id'] ?>"  class="btn btn--ghost btn--sm">⬇️ CSV</a>
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
