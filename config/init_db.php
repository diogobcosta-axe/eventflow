<?php
// init_db.php — A inicialização da base de dados é feita automaticamente em database.php
// Este ficheiro é mantido por compatibilidade mas não é necessário chamar diretamente.
require_once __DIR__ . '/database.php';
getDB(); // garante que as tabelas existem
