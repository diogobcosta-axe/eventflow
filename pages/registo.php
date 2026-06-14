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
$erros = []; // Array associativo com erros por campo (ex: $erros['email'] = 'Email inválido')
$nome  = '';
$email = '';
$papel = 'participante'; // Valor padrão do tipo de conta

// Verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf(); // Valida o token CSRF antes de processar qualquer dado

    // Lê e limpa os dados do formulário
    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $papel    = $_POST['papel'] ?? 'participante';
    $password = $_POST['password'] ?? '';
    $confirma = $_POST['password_confirm'] ?? '';

    // --- Validações no servidor (mesmo que o HTML5 valide, o PHP valida novamente por segurança) ---

    // Nome deve ter pelo menos 2 caracteres
    if (strlen($nome) < 2) {
        $erros['nome'] = 'Nome deve ter pelo menos 2 caracteres.';
    }

    // Valida formato do email usando filtro PHP nativo
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'Email inválido.';
    }

    // Password deve ter pelo menos 6 caracteres
    if (strlen($password) < 6) {
        $erros['password'] = 'Password deve ter pelo menos 6 caracteres.';
    }

    // As duas passwords têm de ser iguais
    if ($password !== $confirma) {
        $erros['password_confirm'] = 'As passwords não coincidem.';
    }

    // O papel só pode ser 'participante' ou 'organizador' (admin não se pode registar)
    if (!in_array($papel, ['participante', 'organizador'])) {
        $erros['papel'] = 'Tipo de conta inválido.';
    }

    // Só avança se não houver erros de validação
    if (empty($erros)) {
        $db = getDB();

        // Verifica se o email já está registado na base de dados
        $stmt = $db->prepare("SELECT id FROM utilizadores WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erros['email'] = 'Este email já está registado.';
        } else {
            // Cifra a password com bcrypt (algoritmo seguro, nunca guardar em texto simples)
            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Insere o novo utilizador na base de dados
            $db->prepare("INSERT INTO utilizadores (nome, email, password_hash, papel) VALUES (?, ?, ?, ?)")
               ->execute([$nome, $email, $hash, $papel]);

            // Faz login automático com a nova conta
            // lastInsertId() devolve o ID do registo que acabou de ser inserido
            $novo_user = $db->prepare("SELECT * FROM utilizadores WHERE id = ?");
            $novo_user->execute([$db->lastInsertId()]);
            loginUser($novo_user->fetch()); // Guarda os dados na sessão

            // Redireciona para a página inicial com mensagem de boas-vindas
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
            <div style="font-size:2.5rem; margin-bottom:12px;">🎉</div>
            <h1>Criar conta</h1>
            <p class="subtitle">Junta-te ao EventFlow gratuitamente</p>
        </div>

        <form method="POST">
            <!-- Token CSRF obrigatório em todos os formulários POST -->
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <!-- Campo: Nome -->
            <div class="form-group">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome" class="form-control"
                       placeholder="O teu nome"
                       value="<?= e($nome) ?>"  <!-- Repreenche se houve erro -->
                       required minlength="2">   <!-- Validação HTML5 (primeira linha de defesa) -->
                <?php if (!empty($erros['nome'])): ?>
                <span class="form-error"><?= e($erros['nome']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Campo: Email -->
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="o-teu@email.pt" value="<?= e($email) ?>" required>
                <?php if (!empty($erros['email'])): ?>
                <span class="form-error"><?= e($erros['email']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Campo: Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Mínimo 6 caracteres" required minlength="6">
                <?php if (!empty($erros['password'])): ?>
                <span class="form-error"><?= e($erros['password']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Campo: Confirmação da password -->
            <div class="form-group">
                <label for="password_confirm">Confirmar password</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                       placeholder="Repete a password" required>
                <?php if (!empty($erros['password_confirm'])): ?>
                <span class="form-error"><?= e($erros['password_confirm']) ?></span>
                <?php endif; ?>
            </div>

            <!-- Campo: Tipo de conta (participante ou organizador) -->
            <div class="form-group">
                <label for="papel">Tipo de conta</label>
                <select id="papel" name="papel" class="form-control">
                    <!-- selected mantém a opção escolhida após um erro de validação -->
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
                Criar conta →
            </button>
        </form>

        <p style="text-align:center; margin-top:24px; color:var(--clr-muted); font-size:.88rem;">
            Já tens conta?
            <a href="/pages/login.php" style="color:var(--clr-accent); font-weight:600">Entrar</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
