<?php
// includes/footer.php — Rodapé HTML (incluído no final de todas as páginas)
?>
<footer class="footer">
    <div class="footer-inner">

        <!-- Coluna da marca/descrição -->
        <div class="footer-brand">
            <div class="footer-logo">EventFlow</div>
            <p>Descobre, cria e participa nos melhores eventos em Portugal.</p>
        </div>

        <!-- Coluna de links para eventos por categoria -->
        <div class="footer-links">
            <h4>Eventos</h4>
            <a href="/pages/eventos.php">Descobrir</a>
            <a href="/pages/eventos.php?categoria=1">Musica</a>
            <a href="/pages/eventos.php?categoria=2">Tecnologia</a>
            <a href="/pages/eventos.php?categoria=6">Conferencias</a>
        </div>

        <!-- Coluna de links de conta (muda consoante o estado de autenticação) -->
        <div class="footer-links">
            <h4>Conta</h4>
            <?php if (isLoggedIn()): ?>
            <a href="/pages/perfil.php">Perfil</a>
            <a href="/pages/minhas_inscricoes.php">Inscricoes</a>
            <a href="/pages/logout.php">Sair</a>
            <?php else: ?>
            <a href="/pages/login.php">Entrar</a>
            <a href="/pages/registo.php">Registar</a>
            <?php endif; ?>
        </div>

        <!-- Coluna de links para organizadores -->
        <div class="footer-links">
            <h4>Organizar</h4>
            <?php if (isOrganizador()): ?>
            <a href="/pages/criar_evento.php">Criar evento</a>
            <a href="/pages/meus_eventos.php">Os meus eventos</a>
            <?php else: ?>
            <a href="/pages/registo.php">Torna-te organizador</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Rodapé inferior com copyright -->
    <div class="footer-bottom">
        <!-- date('Y') devolve o ano atual automaticamente -->
        <p>&copy; <?= date('Y') ?> EventFlow &mdash; Feito em Portugal</p>
    </div>
</footer>

<!-- Carrega o JavaScript principal (menu mobile, flash auto-dismiss, upload preview, AJAX presencas) -->
<script src="/assets/js/main.js"></script>
</body>
</html>
