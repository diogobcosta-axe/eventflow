<?php
// includes/header.php — Cabeçalho HTML e barra de navegação (incluído em todas as páginas)

// Inicia a sessão se ainda não foi iniciada (necessário para usar $_SESSION)
if (session_status() === PHP_SESSION_NONE) session_start();

// Lê e apaga a flash message da sessão (mensagem de sucesso/erro após redirect)
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <!-- Define o conjunto de caracteres como UTF-8 (suporta acentos e caracteres especiais) -->
    <meta charset="UTF-8">
    <!-- Torna a página responsiva em dispositivos móveis -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título da aba do browser: usa $pageTitle definido em cada página, ou "EventFlow" por defeito -->
    <title><?= e($pageTitle ?? 'EventFlow') ?> | EventFlow</title>
    <!-- Pré-ligação às fontes Google para carregar mais rápido -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Carrega as fontes Syne (títulos) e DM Sans (corpo de texto) -->
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- CSS principal da aplicação -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- Barra de navegação principal -->
<nav class="navbar">
    <div class="nav-inner">

        <!-- Logo/nome da aplicação — clique leva à página inicial -->
        <a href="/index.php" class="nav-logo">
            <span class="logo-text">EventFlow</span>
        </a>

        <!-- Links de navegação centrais (desktop) -->
        <div class="nav-links">
            <a href="/index.php" class="nav-link">Inicio</a>
            <a href="/pages/eventos.php" class="nav-link">Eventos</a>
            <!-- Só mostra "Criar Evento" para organizadores e admins -->
            <?php if (isOrganizador()): ?>
            <a href="/pages/criar_evento.php" class="nav-link nav-link--cta">+ Criar Evento</a>
            <?php endif; ?>
        </div>

        <!-- Área de autenticação (lado direito) -->
        <div class="nav-auth">
            <?php if (isLoggedIn()): ?>
                <!-- Mostra o papel do utilizador como badge colorido -->
                <span class="user-role user-role--<?= e($_SESSION['user_papel']) ?>"><?= e(ucfirst($_SESSION['user_papel'])) ?></span>

                <!-- Links apenas para organizadores e admins -->
                <?php if (isOrganizador()): ?>
                <a href="/pages/meus_eventos.php" class="btn btn--ghost btn--sm">Os meus eventos</a>
                <?php endif; ?>

                <!-- Link de administração apenas para admins -->
                <?php if (isAdmin()): ?>
                <a href="/pages/admin.php" class="btn btn--ghost btn--sm">Admin</a>
                <?php endif; ?>

                <!-- Links disponíveis para todos os utilizadores autenticados -->
                <a href="/pages/minhas_inscricoes.php" class="btn btn--ghost btn--sm">Inscricoes</a>
                <!-- Mostra o nome do utilizador como link para o perfil -->
                <a href="/pages/perfil.php" class="btn btn--ghost btn--sm"><?= e($_SESSION['user_nome']) ?></a>
                <a href="/pages/logout.php" class="btn btn--ghost btn--sm">Sair</a>
            <?php else: ?>
                <!-- Utilizador não autenticado: mostrar botões de login e registo -->
                <a href="/pages/login.php" class="btn btn--ghost">Entrar</a>
                <a href="/pages/registo.php" class="btn btn--primary">Registar</a>
            <?php endif; ?>
        </div>

        <!-- Botão hamburger para abrir o menu mobile -->
        <button class="nav-hamburger" onclick="toggleMobileMenu()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- Flash message: aparece após redirecionamentos (sucesso ou erro) -->
<?php if ($flash): ?>
<div class="flash flash--<?= e($flash['tipo']) ?>" id="flashMsg">
    <span><?= e($flash['mensagem']) ?></span>
    <!-- Botão para fechar manualmente a mensagem -->
    <button onclick="this.parentElement.remove()" class="flash__close">x</button>
</div>
<?php endif; ?>

<!-- Menu mobile: oculto por defeito, abre com o botão hamburger -->
<div class="mobile-menu" id="mobileMenu">
    <a href="/index.php">Inicio</a>
    <a href="/pages/eventos.php">Eventos</a>
    <?php if (isLoggedIn()): ?>
        <?php if (isOrganizador()): ?>
        <a href="/pages/criar_evento.php">+ Criar Evento</a>
        <a href="/pages/meus_eventos.php">Os meus eventos</a>
        <?php endif; ?>
        <?php if (isAdmin()): ?>
        <a href="/pages/admin.php">Administracao</a>
        <?php endif; ?>
        <a href="/pages/minhas_inscricoes.php">As minhas inscricoes</a>
        <a href="/pages/perfil.php">Perfil</a>
        <a href="/pages/logout.php">Sair</a>
    <?php else: ?>
        <a href="/pages/login.php">Entrar</a>
        <a href="/pages/registo.php">Registar</a>
    <?php endif; ?>
</div>
