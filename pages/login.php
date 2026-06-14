<?php
// pages/login.php — Autenticação de utilizadores (formulário de login)
require_once __DIR__ . '/../config/database.php'; // Ligação à BD e constantes
require_once __DIR__ . '/../includes/auth.php';    // Funções de sessão e autenticação
require_once __DIR__ . '/../includes/helpers.php'; // Funções auxiliares (e, flash, redirect...)

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
    $email    = trim($_POST['email'] ?? '');    // trim() remove espaços em branco no início/fim
    $password = $_POST['password'] ?? '';       // A password não é trimmed (pode ter espaços propositais)

    // Verifica se os campos foram preenchidos
    if (empty($email) || empty($password)) {
        $erro = 'Preenche todos os campos.';
    } else {
        $db   = getDB(); // Obtém a ligação à base de dados

        // Prepara e executa a query de forma segura (prepared statement = sem SQL Injection)
        // Procura um utilizador com este email que esteja ativo (ativo = 1)
        $stmt = $db->prepare("SELECT * FROM utilizadores WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(); // Devolve a linha do utilizador ou false se não encontrar

        // Verifica se o utilizador existe E se a password corresponde ao hash guardado
        // password_verify() compara a password introduzida com o hash bcrypt na BD
        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user); // Guarda os dados do utilizador na sessão

            // Redireciona para o URL anterior (se o utilizador foi redirecionado para o login)
            // ou para a página inicial por defeito
            $redirect = $_GET['redirect'] ?? '/index.php';
            redirectWith($redirect, 'success', 'Bem-vindo de volta, ' . $user['nome'] . '!');
        } else {
            // Mensagem genérica: não diz se o email ou a password está errada (por segurança)
            $erro = 'Email ou password incorretos.';
        }
    }
}

// Define o título da página (usado no <title> do header.php)
$pageTitle = 'Entrar';
require_once __DIR__ . '/../includes/header.php'; // Inclui o cabeçalho HTML
?>

<div class="container" style="padding:60px 24px">
    <div class="form-box fade-in">

        <!-- Ícone e título da página -->
        <div style="text-align:center; margin-bottom:32px;">
            <div style="font-size:2.5rem; margin-bottom:12px;">🎟️</div>
            <h1>Bem-vindo de volta</h1>
            <p class="subtitle">Entra na tua conta EventFlow</p>
        </div>

        <!-- Mostra o erro de login se existir -->
        <?php if ($erro): ?>
        <div class="flash flash--error" style="border-radius:8px; margin-bottom:20px;">
            <span><?= e($erro) ?></span>
        </div>
        <?php endif; ?>

        <!-- Formulário de login -->
        <form method="POST">
            <!-- Token CSRF escondido: enviado com o formulário para validação no servidor -->
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <!-- Campo de email -->
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="o-teu@email.pt"
                       value="<?= e($email) ?>"  <!-- Repreenche com o email se houve erro -->
                       required>
            </div>

            <!-- Campo de password (type="password" esconde os caracteres) -->
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="A tua password" required>
            </div>

            <!-- Botão de submissão do formulário -->
            <button type="submit" class="btn btn--primary btn--block btn--lg" style="margin-top:8px">
                Entrar →
            </button>
        </form>

        <!-- Link para o registo -->
        <p style="text-align:center; margin-top:24px; color:var(--clr-muted); font-size:.88rem;">
            Não tens conta?
            <a href="/pages/registo.php" style="color:var(--clr-accent); font-weight:600">Regista-te grátis</a>
        </p>

        <!-- Caixa de contas de demonstração (útil para avaliação) -->
        <div style="margin-top:24px; padding:16px; background:var(--clr-bg3); border-radius:8px; font-size:.8rem; color:var(--clr-muted);">
            <strong>Contas demo:</strong><br>
            Admin: admin@eventflow.pt / admin123<br>
            Organizador: org@eventflow.pt / org123<br>
            Participante: user@eventflow.pt / user123
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; // Inclui o rodapé HTML ?>
