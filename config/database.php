<?php
// config/database.php — Ligação à base de dados SQLite e inicialização das tabelas

// Caminho para o ficheiro SQLite (fica dentro da pasta database/)
define('DB_PATH', __DIR__ . '/../database/eventflow.db');

// Pasta onde as imagens dos eventos são guardadas após upload
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/events/');

// Tamanho máximo para upload de imagens: 5MB em bytes (5 * 1024 * 1024)
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);

// Lista dos tipos MIME de imagem permitidos no upload
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);


// Retorna a ligação à base de dados (padrão Singleton: cria apenas uma ligação por pedido)
function getDB(): PDO {
    // Variável estática: mantém o seu valor entre chamadas à função
    static $pdo = null;

    // Se já existe uma ligação, devolve-a diretamente (evita criar uma nova)
    if ($pdo !== null) return $pdo;

    // Obtém o diretório onde o ficheiro da BD vai ser guardado
    $dir = dirname(DB_PATH);

    // Cria a pasta "database/" se ainda não existir (755 = permissões de leitura/escrita para o dono)
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    // Cria a ligação PDO ao ficheiro SQLite
    $pdo = new PDO('sqlite:' . DB_PATH);

    // Configura o PDO para lançar exceções em caso de erro (facilita depuração)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Configura o PDO para devolver resultados como arrays associativos (ex: $row['nome'])
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Ativa as chaves estrangeiras no SQLite (por defeito estão desativadas)
    $pdo->exec('PRAGMA foreign_keys = ON;');

    // Verifica se as tabelas já existem na base de dados
    // (sqlite_master é uma tabela interna do SQLite com informação sobre a estrutura)
    $existe = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='utilizadores'")->fetchColumn();

    // Se a tabela 'utilizadores' não existe, significa que a BD é nova → inicializar
    if (!$existe) {
        _criarTabelas($pdo);    // Cria as tabelas
        _inserirDadosDemo($pdo); // Insere dados de exemplo
    }

    // Devolve a ligação para ser usada nas páginas
    return $pdo;
}


// Cria todas as tabelas da base de dados (chamada apenas na primeira execução)
function _criarTabelas(PDO $pdo): void {

    // Tabela de categorias dos eventos (ex: Música, Tecnologia, Desporto...)
    $pdo->exec("CREATE TABLE IF NOT EXISTS categorias_evento (
        id        INTEGER PRIMARY KEY AUTOINCREMENT, -- Identificador único (gerado automaticamente)
        nome      TEXT NOT NULL UNIQUE,              -- Nome da categoria (não pode repetir)
        icone     TEXT NOT NULL DEFAULT '🎉',        -- Emoji representativo da categoria
        cor       TEXT NOT NULL DEFAULT '#6366f1',   -- Cor HEX para estilizar a categoria
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP -- Data/hora de criação (automática)
    )");

    // Tabela de utilizadores (admins, organizadores e participantes)
    $pdo->exec("CREATE TABLE IF NOT EXISTS utilizadores (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        nome          TEXT NOT NULL,                   -- Nome completo do utilizador
        email         TEXT NOT NULL UNIQUE,            -- Email único (usado para login)
        password_hash TEXT NOT NULL,                   -- Password cifrada com bcrypt (nunca em texto simples)
        papel         TEXT NOT NULL DEFAULT 'participante'
                          CHECK(papel IN ('admin','organizador','participante')), -- Só permite estes 3 valores
        ativo         INTEGER NOT NULL DEFAULT 1,      -- 1 = conta ativa, 0 = conta desativada
        criado_em     DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabela de eventos criados pelos organizadores
    $pdo->exec("CREATE TABLE IF NOT EXISTS eventos (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo         TEXT NOT NULL,
        descricao      TEXT NOT NULL,
        local          TEXT NOT NULL,
        data_inicio    DATETIME NOT NULL,
        data_fim       DATETIME,                        -- Opcional (pode ser null)
        vagas          INTEGER NOT NULL DEFAULT 100,    -- Número máximo de inscrições
        imagem         TEXT DEFAULT NULL,               -- Nome do ficheiro da imagem (ou null)
        organizador_id INTEGER NOT NULL,                -- FK: quem criou o evento
        categoria_id   INTEGER NOT NULL,                -- FK: categoria do evento
        estado         TEXT NOT NULL DEFAULT 'ativo'
                           CHECK(estado IN ('ativo','cancelado','encerrado')),
        criado_em      DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (organizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE, -- Apagar eventos se o user for apagado
        FOREIGN KEY (categoria_id)   REFERENCES categorias_evento(id)
    )");

    // Tabela de inscrições (liga utilizadores a eventos)
    $pdo->exec("CREATE TABLE IF NOT EXISTS inscricoes (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        utilizador_id INTEGER NOT NULL,                 -- FK: quem se inscreveu
        evento_id     INTEGER NOT NULL,                 -- FK: em que evento
        estado        TEXT NOT NULL DEFAULT 'confirmada'
                          CHECK(estado IN ('confirmada','cancelada','presenca')),
        criado_em     DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(utilizador_id, evento_id),               -- Um utilizador só se pode inscrever uma vez por evento
        FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
        FOREIGN KEY (evento_id)     REFERENCES eventos(id) ON DELETE CASCADE
    )");
}


// Insere dados de demonstração para testar a aplicação
function _inserirDadosDemo(PDO $pdo): void {

    // Array com as categorias iniciais: [nome, icone, cor]
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

    // Prepara o statement uma vez e executa-o para cada categoria
    // INSERT OR IGNORE ignora duplicados (não dá erro se a categoria já existir)
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO categorias_evento (nome, icone, cor) VALUES (?, ?, ?)");
    foreach ($categorias as $cat) {
        $stmt->execute($cat); // Passa o array diretamente como parâmetros
    }

    // Criar conta de administrador (password cifrada com bcrypt)
    $pdo->prepare("INSERT OR IGNORE INTO utilizadores (nome, email, password_hash, papel) VALUES (?, ?, ?, ?)")
        ->execute(['Administrador',    'admin@eventflow.pt', password_hash('admin123', PASSWORD_BCRYPT), 'admin']);

    // Criar conta de organizador de exemplo
    $pdo->prepare("INSERT OR IGNORE INTO utilizadores (nome, email, password_hash, papel) VALUES (?, ?, ?, ?)")
        ->execute(['João Organizador', 'org@eventflow.pt',   password_hash('org123',   PASSWORD_BCRYPT), 'organizador']);

    // Criar conta de participante de exemplo
    $pdo->prepare("INSERT OR IGNORE INTO utilizadores (nome, email, password_hash, papel) VALUES (?, ?, ?, ?)")
        ->execute(['Maria Silva',      'user@eventflow.pt',  password_hash('user123',  PASSWORD_BCRYPT), 'participante']);

    // Obter os IDs do organizador e das categorias para criar eventos de demonstração
    $org  = $pdo->query("SELECT id FROM utilizadores WHERE email='org@eventflow.pt'")->fetch();
    $tec  = $pdo->query("SELECT id FROM categorias_evento WHERE nome='Tecnologia'")->fetch();
    $mus  = $pdo->query("SELECT id FROM categorias_evento WHERE nome='Música'")->fetch();
    $conf = $pdo->query("SELECT id FROM categorias_evento WHERE nome='Conferência'")->fetch();

    // Só cria eventos se o organizador foi encontrado
    if ($org) {
        // Prepara o INSERT de eventos (reutilizado para cada evento demo)
        $ins = $pdo->prepare("INSERT OR IGNORE INTO eventos
            (organizador_id, categoria_id, titulo, descricao, local, data_inicio, data_fim, vagas)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        // Evento 1: Summit de PHP
        $ins->execute([$org['id'], $tec['id'],
            'PHP & Web Dev Summit 2026',
            'O maior encontro de programadores PHP em Portugal. Workshops, talks e networking!',
            'Porto, Casa da Música', '2026-07-15 09:00', '2026-07-15 18:00', 200]);

        // Evento 2: Festival de música
        $ins->execute([$org['id'], $mus['id'],
            'Festival Sunset Porto',
            'Um festival de música ao pôr do sol com os melhores DJs nacionais e internacionais.',
            'Porto, Jardins do Palácio de Cristal', '2026-08-10 17:00', '2026-08-10 23:59', 500]);

        // Evento 3: Conferência de IA
        $ins->execute([$org['id'], $conf['id'],
            'TechTalks: IA & Futuro',
            'Conferência sobre Inteligência Artificial e o futuro do trabalho. Oradores de topo.',
            'Lisboa, Centro de Congressos', '2026-09-05 10:00', '2026-09-05 17:00', 150]);

        // Evento 4: Hackathon
        $ins->execute([$org['id'], $tec['id'],
            'Hackathon 48h Lisboa',
            '48 horas para criar, inovar e ganhar prémios. Sozinho ou em equipa, venha hackear!',
            'Lisboa, Hub Criativo Beato', '2026-10-01 08:00', '2026-10-03 08:00', 80]);
    }
}
