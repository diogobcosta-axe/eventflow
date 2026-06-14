<?php
// pages/perfil.php — Editar informação pessoal e alterar password
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// É necessário estar autenticado
requireLogin();

$db  = getDB();
$uid = getCurrentUserId(); // ID do utilizador autenticado

// Obtém os dados atuais do utilizador (nome, email, papel, etc.)
$stmt = $db->prepare("SELECT * FROM utilizadores WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

// Obtém estatísticas de atividade do utilizador
$stmt_stats = $db->prepare("
    SELECT
        SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) AS confirmadas,
        SUM(CASE WHEN estado = 'presenca'   THEN 1 ELSE 0 END) AS presencas
    FROM inscricoes
    WHERE utilizador_id = ?
");
// SUM(CASE WHEN ...) conta apenas as linhas que satisfazem a condição
// Se não houver inscrições, devolve NULL — o (int) converte para 0
$stmt_stats->execute([$uid]);
$stats = $stmt_stats->fetch();

$erros = []; // Array de erros de validação por campo

// Processa as submissões de formulário (há dois formulários distintos nesta página)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf(); // Valida sempre o token CSRF

    // Lê qual dos formulários foi submetido
    $action = $_POST['action'] ?? '';

    // ===================== AÇÃO: Atualizar informação pessoal =====================
    if ($action === 'update_info') {
        $nome  = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // Validações
        if (strlen($nome) < 2) {
            $erros['nome'] = 'Nome muito curto.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'Email inválido.';
        }

        // Verifica se o email já está a ser usado por outro utilizador
        // (o AND id != ? exclui o próprio utilizador da verificação)
        $dup = $db->prepare("SELECT id FROM utilizadores WHERE email = ? AND id != ?");
        $dup->execute([$email, $uid]);
        if ($dup->fetch()) {
            $erros['email'] = 'Este email já está em uso.';
        }

        if (empty($erros)) {
            // Atualiza o nome e email na base de dados
            $db->prepare("UPDATE utilizadores SET nome = ?, email = ? WHERE id = ?")
               ->execute([$nome, $email, $uid]);

            // Atualiza também os dados na sessão (para a navbar refletir imediatamente)
            $_SESSION['user_nome']  = $nome;
            $_SESSION['user_email'] = $email;

            redirectWith('/pages/perfil.php', 'success', 'Perfil atualizado!');
        }
    }

    // ===================== AÇÃO: Alterar password =====================
    if ($action === 'update_password') {
        $atual    = $_POST['current_password'] ?? '';
        $nova     = $_POST['new_password'] ?? '';
        $confirma = $_POST['confirm_password'] ?? '';

        // Verifica se a password atual está correta (compara com o hash na BD)
        if (!password_verify($atual, $user['password_hash'])) {
            $erros['current_password'] = 'Password atual incorreta.';
        }
        if (strlen($nova) < 6) {
            $erros['new_password'] = 'Nova password deve ter pelo menos 6 caracteres.';
        }
        if ($nova !== $confirma) {
            $erros['confirm_password'] = 'As passwords não coincidem.';
        }

        if (empty($erros)) {
            // Cifra a nova password com bcrypt antes de guardar
            $hash = password_hash($nova, PASSWORD_BCRYPT);
            $db->prepare("UPDATE utilizadores SET password_hash = ? WHERE id = ?")
               ->execute([$hash, $uid]);

            redirectWith('/pages/perfil.php', 'success', 'Password alterada com sucesso!');
        }
    }
}

$pageTitle = 'Meu perfil';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding:48px 24px; max-width:700px">
    <h1 style="margin-bottom:32px">👤 O meu perfil</h1>

    <!-- Cartão de resumo do utilizador -->
    <div style="display:flex;align-items:center;gap:20px;background:var(--clr-bg2);border:1px solid var(--clr-border);border-radius:16px;padding:24px;margin-bottom:32px">

        <!-- Avatar gerado com a primeira letra do nome -->
        <div style="width:72px;height:72px;border-radius:50%;background:var(--clr-accent);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;color:#fff;flex-shrink:0">
            <?= strtoupper(substr($user['nome'], 0, 1)) ?>  <!-- Primeira letra do nome em maiúscula -->
        </div>

        <div>
            <h2 style="font-size:1.4rem"><?= e($user['nome']) ?></h2>
            <p style="color:var(--clr-muted)"><?= e($user['email']) ?></p>
            <!-- Badge do papel do utilizador -->
            <span class="user-role user-role--<?= e($user['papel']) ?>"><?= ucfirst($user['papel']) ?></span>
        </div>

        <!-- Estatísticas de atividade -->
        <div style="margin-left:auto;display:flex;gap:16px;text-align:center">
            <div>
                <!-- (int) converte NULL para 0 se não houver inscrições -->
                <div style="font-size:1.5rem;font-weight:800;color:var(--clr-accent)"><?= (int)$stats['confirmadas'] ?></div>
                <div style="font-size:.75rem;color:var(--clr-muted)">Inscrições</div>
            </div>
            <div>
                <div style="font-size:1.5rem;font-weight:800;color:var(--clr-accent)"><?= (int)$stats['presencas'] ?></div>
                <div style="font-size:.75rem;color:var(--clr-muted)">Presenças</div>
            </div>
        </div>
    </div>

    <!-- Formulário 1: Atualizar informação pessoal -->
    <div style="background:var(--clr-bg2);border:1px solid var(--clr-border);border-radius:16px;padding:28px;margin-bottom:24px">
        <h3 style="margin-bottom:20px">Informação pessoal</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <!-- Campo action indica qual formulário foi submetido -->
            <input type="hidden" name="action" value="update_info">

            <div class="form-group">
                <label>Nome</label>
                <input type="text" name="nome" class="form-control"
                       value="<?= e($user['nome']) ?>" required>
                <?php if (!empty($erros['nome'])): ?>
                <span class="form-error"><?= e($erros['nome']) ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control"
                       value="<?= e($user['email']) ?>" required>
                <?php if (!empty($erros['email'])): ?>
                <span class="form-error"><?= e($erros['email']) ?></span>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn--primary">Guardar</button>
        </form>
    </div>

    <!-- Formulário 2: Alterar password -->
    <div style="background:var(--clr-bg2);border:1px solid var(--clr-border);border-radius:16px;padding:28px">
        <h3 style="margin-bottom:20px">Alterar password</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="update_password">

            <div class="form-group">
                <label>Password atual</label>
                <input type="password" name="current_password" class="form-control" required>
                <?php if (!empty($erros['current_password'])): ?>
                <span class="form-error"><?= e($erros['current_password']) ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Nova password</label>
                <!-- minlength="6" faz validação HTML5 antes de enviar -->
                <input type="password" name="new_password" class="form-control" minlength="6" required>
                <?php if (!empty($erros['new_password'])): ?>
                <span class="form-error"><?= e($erros['new_password']) ?></span>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Confirmar nova password</label>
                <input type="password" name="confirm_password" class="form-control" required>
                <?php if (!empty($erros['confirm_password'])): ?>
                <span class="form-error"><?= e($erros['confirm_password']) ?></span>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn--primary">Alterar password</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
