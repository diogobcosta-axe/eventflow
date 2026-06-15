<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
http_response_code(404);
$pageTitle = 'Página não encontrada';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="error-page">
    <div>
        <div class="error-page__code">404</div>
        <h2 class="error-page__msg">Página não encontrada</h2>
        <p class="error-page__sub">
            A página que procuras não existe ou foi removida.<br>
            Verifica se o endereço está correto ou volta à página inicial.
        </p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px">
            <button onclick="history.back()" class="btn btn--ghost">← Voltar atrás</button>
            <a href="/index.php" class="btn btn--primary">Ir para o início</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
