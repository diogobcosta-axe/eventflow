<?php
// pages/login.php — Autenticação de utilizadores
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Já está autenticado → redireciona para a página inicial
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$erro  = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $erro = 'Preenche todos os campos.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM utilizadores WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            $redirect = $_GET['redirect'] ?? '/index.php';
            redirectWith($redirect, 'success', 'Bem-vindo de volta, ' . $user['nome'] . '!');
        } else {
            $erro = 'Email ou password incorretos.';
        }
    }
}

$pageTitle = 'Entrar';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding:60px 24px">
    <div class="form-box fade-in">
        <div style="text-align:center; margin-bottom:32px;">
            <div style="font-size:2.5rem; margin-bottom:12px;">🎟️</div>
            <h1>Bem-vindo de volta</h1>
            <p class="subtitle">Entra na tua conta EventFlow</p>
        </div>

        <?php if ($erro): ?>
        <div class="flash flash--error" style="border-radius:8px; margin-bottom:20px;">
            <span><?= e($erro) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="o-teu@email.pt" value="<?= e($email) ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="A tua password" required>
            </div>

            <button type="submit" class="btn btn--primary btn--block btn--lg" style="margin-top:8px">
                Entrar →
            </button>
        </form>

        <p style="text-align:center; margin-top:24px; color:var(--clr-muted); font-size:.88rem;">
            Não tens conta?
            <a href="/pages/registo.php" style="color:var(--clr-accent); font-weight:600">Regista-te grátis</a>
        </p>

        <div style="margin-top:24px; padding:16px; background:var(--clr-bg3); border-radius:8px; font-size:.8rem; color:var(--clr-muted);">
            <strong>Contas demo:</strong><br>
            Admin: admin@eventflow.pt / admin123<br>
            Organizador: org@eventflow.pt / org123<br>
            Participante: user@eventflow.pt / user123
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
