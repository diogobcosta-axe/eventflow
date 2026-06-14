<?php
// pages/login.php — Autenticação de utilizadores (formulário de login)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Se o utilizador já está autenticado, não faz sentido mostrar o login → redireciona
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

// Variáveis para o formulário (inicialmente vazias)
$erro  = '';   // Mensagem de erro a mostrar ao utilizador
$email = '';   // Guarda o email para repreencher o campo após erro

// Verifica se o formulário foi submetido (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Valida o token CSRF (proteção contra ataques de falsificação de pedidos)
    validateCsrf();

    // Lê e limpa os dados enviados pelo formulário
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Verifica se os campos foram preenchidos
    if (empty($email) || empty($password)) {
        $erro = 'Preenche todos os campos.';
    } else {
        $db   = getDB();

        // Prepara e executa a query de forma segura (prepared statement = sem SQL Injection)
        // Procura um utilizador com este email que esteja ativo (ativo = 1)
        $stmt = $db->prepare("SELECT * FROM utilizadores WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verifica se o utilizador existe E se a password corresponde ao hash guardado
        // password_verify() compara a password introduzida com o hash bcrypt na BD
        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user); // Guarda os dados do utilizador na sessão

            // Redireciona para o URL anterior (se o utilizador foi redirecionado para o login)
            $redirect = $_GET['redirect'] ?? '/index.php';
            redirectWith($redirect, 'success', 'Bem-vindo de volta, ' . $user['nome'] . '!');
        } else {
            // Mensagem genérica: não diz se o email ou a password está errada (por segurança)
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
            <h1>Bem-vindo de volta</h1>
            <p class="subtitle">Entra na tua conta EventFlow</p>
        </div>

        <!-- Mostra o erro de login se existir -->
        <?php if ($erro): ?>
        <div class="flash flash--error" style="border-radius:8px; margin-bottom:20px;">
            <span><?= e($erro) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST">
            <!-- Token CSRF obrigatório em todos os formulários POST -->
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
                Entrar
            </button>
        </form>

        <p style="text-align:center; margin-top:24px; color:var(--clr-muted); font-size:.88rem;">
            Nao tens conta?
            <a href="/pages/registo.php" style="color:var(--clr-accent); font-weight:600">Regista-te gratuitamente</a>
        </p>

        <!-- Contas de demonstração (útil para avaliação) -->
        <div style="margin-top:24px; padding:16px; background:var(--clr-bg3); border-radius:8px; font-size:.8rem; color:var(--clr-muted);">
            <strong>Contas demo:</strong><br>
            Admin: admin@eventflow.pt / admin123<br>
            Organizador: org@eventflow.pt / org123<br>
            Participante: user@eventflow.pt / user123
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
