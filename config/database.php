<?php
// config/database.php — Ligação à base de dados SQLite e inicialização

define('DB_PATH',            __DIR__ . '/../database/eventflow.db');
define('UPLOAD_DIR',         __DIR__ . '/../assets/uploads/events/');
define('UPLOAD_MAX_SIZE',    5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// Retorna a ligação PDO (criada apenas uma vez por pedido)
function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    // Criar a pasta da BD se não existir
    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    // Inicializar tabelas e dados demo na primeira vez
    $existe = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='utilizadores'")->fetchColumn();
    if (!$existe) {
        _criarTabelas($pdo);
        _inserirDadosDemo($pdo);
    }

    return $pdo;
}

function _criarTabelas(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS categorias_evento (
        id        INTEGER PRIMARY KEY AUTOINCREMENT,
        nome      TEXT NOT NULL UNIQUE,
        icone     TEXT NOT NULL DEFAULT '🎉',
        cor       TEXT NOT NULL DEFAULT '#6366f1',
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS utilizadores (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        nome          TEXT NOT NULL,
        email         TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        papel         TEXT NOT NULL DEFAULT 'participante'
                          CHECK(papel IN ('admin','organizador','participante')),
        ativo         INTEGER NOT NULL DEFAULT 1,
        criado_em     DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS eventos (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo         TEXT NOT NULL,
        descricao      TEXT NOT NULL,
        local          TEXT NOT NULL,
        data_inicio    DATETIME NOT NULL,
        data_fim       DATETIME,
        vagas          INTEGER NOT NULL DEFAULT 100,
        imagem         TEXT DEFAULT NULL,
        organizador_id INTEGER NOT NULL,
        categoria_id   INTEGER NOT NULL,
        estado         TEXT NOT NULL DEFAULT 'ativo'
                           CHECK(estado IN ('ativo','cancelado','encerrado')),
        criado_em      DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
        FOREIGN KEY (categoria_id)   REFERENCES categorias_evento(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS inscricoes (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        utilizador_id INTEGER NOT NULL,
        evento_id     INTEGER NOT NULL,
        estado        TEXT NOT NULL DEFAULT 'confirmada'
                          CHECK(estado IN ('confirmada','cancelada','presenca')),
        criado_em     DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(utilizador_id, evento_id),
        FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
        FOREIGN KEY (evento_id)     REFERENCES eventos(id) ON DELETE CASCADE
    )");
}

function _inserirDadosDemo(PDO $pdo): void {
    // Categorias
    $categorias = [
        ['Música',             '🎵', '#7c3aed'],
        ['Tecnologia',         '💻', '#2563eb'],
        ['Cultura & Arte',     '🎨', '#db2777'],
        ['Desporto',           '⚽', '#16a34a'],
        ['Comida & Lifestyle', '🍽️', '#ea580c'],
        ['Conferência',        '🎤', '#0891b2'],
        ['Universitário',      '🎓', '#7c3aed'],
        ['Outros',             '🎉', '#64748b'],
    ];
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO categorias_evento (nome, icone, cor) VALUES (?, ?, ?)");
    foreach ($categorias as $cat) {
        $stmt->execute($cat);
    }

    // Utilizadores demo
    $pdo->prepare("INSERT OR IGNORE INTO utilizadores (nome, email, password_hash, papel) VALUES (?, ?, ?, ?)")
        ->execute(['Administrador',    'admin@eventflow.pt', password_hash('admin123', PASSWORD_BCRYPT), 'admin']);

    $pdo->prepare("INSERT OR IGNORE INTO utilizadores (nome, email, password_hash, papel) VALUES (?, ?, ?, ?)")
        ->execute(['João Organizador', 'org@eventflow.pt',   password_hash('org123',   PASSWORD_BCRYPT), 'organizador']);

    $pdo->prepare("INSERT OR IGNORE INTO utilizadores (nome, email, password_hash, papel) VALUES (?, ?, ?, ?)")
        ->execute(['Maria Silva',      'user@eventflow.pt',  password_hash('user123',  PASSWORD_BCRYPT), 'participante']);

    // Eventos demo
    $org      = $pdo->query("SELECT id FROM utilizadores WHERE email='org@eventflow.pt'")->fetch();
    $tec      = $pdo->query("SELECT id FROM categorias_evento WHERE nome='Tecnologia'")->fetch();
    $mus      = $pdo->query("SELECT id FROM categorias_evento WHERE nome='Música'")->fetch();
    $conf     = $pdo->query("SELECT id FROM categorias_evento WHERE nome='Conferência'")->fetch();

    if ($org) {
        $ins = $pdo->prepare("INSERT OR IGNORE INTO eventos
            (organizador_id, categoria_id, titulo, descricao, local, data_inicio, data_fim, vagas)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $ins->execute([$org['id'], $tec['id'],
            'PHP & Web Dev Summit 2026',
            'O maior encontro de programadores PHP em Portugal. Workshops, talks e networking!',
            'Porto, Casa da Música', '2026-07-15 09:00', '2026-07-15 18:00', 200]);

        $ins->execute([$org['id'], $mus['id'],
            'Festival Sunset Porto',
            'Um festival de música ao pôr do sol com os melhores DJs nacionais e internacionais.',
            'Porto, Jardins do Palácio de Cristal', '2026-08-10 17:00', '2026-08-10 23:59', 500]);

        $ins->execute([$org['id'], $conf['id'],
            'TechTalks: IA & Futuro',
            'Conferência sobre Inteligência Artificial e o futuro do trabalho. Oradores de topo.',
            'Lisboa, Centro de Congressos', '2026-09-05 10:00', '2026-09-05 17:00', 150]);

        $ins->execute([$org['id'], $tec['id'],
            'Hackathon 48h Lisboa',
            '48 horas para criar, inovar e ganhar prémios. Sozinho ou em equipa, venha hackear!',
            'Lisboa, Hub Criativo Beato', '2026-10-01 08:00', '2026-10-03 08:00', 80]);
    }
}
