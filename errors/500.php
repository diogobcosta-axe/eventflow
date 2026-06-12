<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
http_response_code(500);
$pageTitle = 'Erro interno';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="error-page">
    <div>
        <div class="error-page__code">500</div>
        <h2 class="error-page__msg">Erro interno do servidor</h2>
        <p class="error-page__sub">Algo correu mal. Tenta novamente mais tarde.</p>
        <a href="/index.php" class="btn btn--primary">← Voltar ao início</a>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
