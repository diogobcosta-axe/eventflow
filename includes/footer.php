<?php /** EventFlow - Footer global */ ?>
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <div class="footer-logo">🎟️ EventFlow</div>
            <p>Descobre, cria e participa nos melhores eventos em Portugal.</p>
        </div>
        <div class="footer-links">
            <h4>Eventos</h4>
            <a href="/pages/eventos.php">Descobrir</a>
            <a href="/pages/eventos.php?categoria=1">Música</a>
            <a href="/pages/eventos.php?categoria=2">Tecnologia</a>
            <a href="/pages/eventos.php?categoria=6">Conferências</a>
        </div>
        <div class="footer-links">
            <h4>Conta</h4>
            <?php if (isLoggedIn()): ?>
            <a href="/pages/perfil.php">Perfil</a>
            <a href="/pages/minhas_inscricoes.php">Inscrições</a>
            <a href="/pages/logout.php">Sair</a>
            <?php else: ?>
            <a href="/pages/login.php">Entrar</a>
            <a href="/pages/registo.php">Registar</a>
            <?php endif; ?>
        </div>
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
    <div class="footer-bottom">
        <p>© <?= date('Y') ?> EventFlow · Feito com ❤️ em Portugal</p>
    </div>
</footer>

<script src="/assets/js/main.js"></script>
</body>
</html>
