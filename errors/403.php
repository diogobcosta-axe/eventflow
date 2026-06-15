<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
http_response_code(403);
$pageTitle = 'Acesso negado';
require_once __DIR__ . '/../includes/header.php';

// Verifica se o utilizador está autenticado para personalizar a mensagem
$autenticado = isLoggedIn();
?>
<div class="error-page">
    <div>
        <div class="error-page__code">403</div>
        <h2 class="error-page__msg">Acesso negado</h2>
        <p class="error-page__sub">
            Não tens permissões para aceder a esta página.<br>
            <?php if (!$autenticado): ?>
                Talvez precises de iniciar sessão primeiro.
            <?php else: ?>
                A tua conta não tem o perfil necessário para esta ação.
            <?php endif; ?>
        </p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px">
            <button onclick="history.back()" class="btn btn--ghost">← Voltar atrás</button>
            <?php if (!$autenticado): ?>
                <a href="/pages/login.php" class="btn btn--primary">Iniciar sessão</a>
            <?php else: ?>
                <a href="/index.php" class="btn btn--primary">Ir para o início</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
