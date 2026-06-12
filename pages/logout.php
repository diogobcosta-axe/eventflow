<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
logoutUser();
redirectWith('/index.php', 'success', 'Sessão terminada com sucesso.');
