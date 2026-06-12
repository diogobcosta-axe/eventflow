<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
http_response_code(403);
$pageTitle = 'Acesso negado';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="error-page">
    <div>
        <div class="error-page__code">403</div>
        <h2 class="error-page__msg">Acesso negado</h2>
        <p class="error-page__sub">Não tens permissões para aceder a esta página.</p>
        <a href="/index.php" class="btn btn--primary">← Voltar ao início</a>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
