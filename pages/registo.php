<?php
// pages/registo.php — Criação de nova conta de utilizador
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Se o utilizador já está autenticado, não precisa de se registar
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

// Variáveis para repreencher o formulário em caso de erro
$erros = [];
$nome  = '';
$email = '';
$papel = 'participante';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $papel    = $_POST['papel'] ?? 'participante';
    $password = $_POST['password'] ?? '';
    $confirma = $_POST['password_confirm'] ?? '';

    // Validações no servidor
    if (strlen($nome) < 2)                              $erros['nome']             = 'Nome deve ter pelo menos 2 caracteres.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $erros['email']            = 'Email inválido.';
    if (strlen($password) < 6)                          $erros['password']         = 'Password deve ter pelo menos 6 caracteres.';
    if ($password !== $confirma)                        $erros['password_confirm'] = 'As passwords nao coincidem.';
    if (!in_array($papel, ['participante','organizador'])) $erros['papel']         = 'Tipo de conta invalido.';

    if (empty($erros)) {
        $db = getDB();

        // Verifica se o email já está registado na base de dados
        $stmt = $db->prepare("SELECT id FROM utilizadores WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erros['email'] = 'Este email ja esta registado.';
        } else {
            // Cifra a password com bcrypt antes de guardar na BD
            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Insere o novo utilizador na base de dados
            $db->prepare("INSERT INTO utilizadores (nome, email, password_hash, papel) VALUES (?, ?, ?, ?)")
               ->execute([$nome, $email, $hash, $papel]);

            // Faz login automático com a nova conta
            $novo_user = $db->prepare("SELECT * FROM utilizadores WHERE id = ?");
            $novo_user->execute([$db->lastInsertId()]);
            loginUser($novo_user->fetch());

            redirectWith('/index.php', 'success', 'Conta criada com sucesso! Bem-vindo(a), ' . $nome . '!');
        }
    }
}

$pageTitle = 'Criar conta';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding:60px 24px">
    <div class="form-box fade-in" style="max-width:520px">

        <div style="text-align:center; margin-bottom:32px;">
            <h1>Criar conta</h1>
            <p class="subtitle">Junta-te ao EventFlow gratuitamente</p>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="form-group">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome" class="form-control"
                       placeholder="O teu nome" value="<?= e($nome) ?>" required minlength="2">
                <?php if (!empty($erros['nome'])): ?>
                <span class="form-error"><?= e($erros['nome']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="o-teu@email.pt" value="<?= e($email) ?>" required>
                <?php if (!empty($erros['email'])): ?>
                <span class="form-error"><?= e($erros['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Minimo 6 caracteres" required minlength="6">
                <?php if (!empty($erros['password'])): ?>
                <span class="form-error"><?= e($erros['password']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirmar password</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                       placeholder="Repete a password" required>
                <?php if (!empty($erros['password_confirm'])): ?>
                <span class="form-error"><?= e($erros['password_confirm']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="papel">Tipo de conta</label>
                <select id="papel" name="papel" class="form-control">
                    <option value="participante" <?= $papel === 'participante' ? 'selected' : '' ?>>
                        Participante — inscreve-te em eventos
                    </option>
                    <option value="organizador" <?= $papel === 'organizador' ? 'selected' : '' ?>>
                        Organizador — cria e gere eventos
                    </option>
                </select>
                <?php if (!empty($erros['papel'])): ?>
                <span class="form-error"><?= e($erros['papel']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn--primary btn--block btn--lg">
                Criar conta
            </button>
        </form>

        <p style="text-align:center; margin-top:24px; color:var(--clr-muted); font-size:.88rem;">
            Ja tens conta?
            <a href="/pages/login.php" style="color:var(--clr-accent); font-weight:600">Entrar</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
