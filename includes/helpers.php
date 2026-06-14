<?php
// includes/helpers.php — Funções auxiliares usadas em todas as páginas


// Escapa uma string para ser exibida em HTML de forma segura (previne ataques XSS)
// XSS = Cross-Site Scripting: atacante injeta código HTML/JS malicioso no site
// Uso: em vez de echo $nome, usar echo e($nome)
function e(string $str): string {
    // ENT_QUOTES converte tanto ' como " | UTF-8 define o conjunto de caracteres
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}


// Formata uma data da base de dados para formato legível em português
// Exemplo: "2026-07-15 09:00" → "15 Jul 2026 · 09:00"
// $comHora = false → mostra só a data sem a hora
function formatDate(string $date, bool $comHora = true): string {
    // Nomes dos meses abreviados em português
    $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];

    // Converte a string de data para timestamp Unix (número de segundos desde 1970)
    $ts = strtotime($date);

    // Monta o texto: dia + nome do mês + ano
    // date('n', $ts) devolve o mês como número (1-12); subtraímos 1 para índice do array
    $texto = date('d', $ts) . ' ' . $meses[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);

    // Acrescenta a hora se $comHora for true
    if ($comHora) {
        $texto .= ' · ' . date('H:i', $ts);
    }
    return $texto;
}


// Conta quantas inscrições confirmadas existem num evento
function countInscritos(int $evento_id): int {
    $db   = getDB(); // Obtém a ligação à base de dados
    $stmt = $db->prepare("SELECT COUNT(*) FROM inscricoes WHERE evento_id = ? AND estado = 'confirmada'");
    $stmt->execute([$evento_id]); // Passa o ID como parâmetro (previne SQL Injection)
    return (int)$stmt->fetchColumn(); // fetchColumn() devolve apenas o primeiro valor da linha
}


// Verifica se um utilizador está inscrito num evento
// Devolve a linha da inscrição (array) ou null se não estiver inscrito
function isInscrito(int $user_id, int $evento_id): ?array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM inscricoes WHERE utilizador_id = ? AND evento_id = ?");
    $stmt->execute([$user_id, $evento_id]);
    $resultado = $stmt->fetch(); // fetch() devolve a linha ou false se não existir
    return $resultado ?: null;   // Converte false para null (mais claro de verificar)
}


// Calcula o número de vagas disponíveis num evento
// Vagas disponíveis = total de vagas - número de inscrições confirmadas
function vagasDisponiveis(array $evento): int {
    // max(0, ...) garante que nunca devolve um número negativo
    return max(0, $evento['vagas'] - countInscritos($evento['id']));
}


// ============================================================
// PROTEÇÃO CSRF (Cross-Site Request Forgery)
// CSRF é um ataque onde um site malicioso faz pedidos em nome do utilizador.
// A proteção consiste em gerar um token secreto, guardá-lo na sessão,
// incluí-lo no formulário e verificá-lo quando o formulário é submetido.
// ============================================================

// Gera um token CSRF e guarda-o na sessão (ou devolve o existente)
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes(32) gera 32 bytes aleatórios criptograficamente seguros
        // bin2hex() converte os bytes para uma string hexadecimal (64 caracteres)
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Valida o token CSRF enviado pelo formulário
// Se o token for inválido, para a execução com erro 403
function validateCsrf(): void {
    $token = $_POST['csrf_token'] ?? ''; // Token enviado pelo formulário

    // hash_equals() faz a comparação de strings de forma segura contra "timing attacks"
    // (ao contrário de ===, não revela informação pelo tempo que demora a comparar)
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403); // Resposta HTTP: Forbidden
        die('Token CSRF inválido. Volta atrás e tenta novamente.');
    }
}


// ============================================================
// FLASH MESSAGES — mensagens temporárias após redirecionamento
// Padrão PRG (Post-Redirect-Get): após um POST bem-sucedido,
// redireciona para um GET, evitando resubmissão do formulário.
// A mensagem é guardada na sessão e apagada após ser lida.
// ============================================================

// Guarda uma mensagem temporária na sessão
// $tipo = 'success' ou 'error' (usado para estilizar a mensagem)
function setFlash(string $tipo, string $mensagem): void {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

// Lê e apaga a mensagem temporária da sessão
// Devolve o array ['tipo', 'mensagem'] ou null se não houver mensagem
function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null; // Lê a mensagem
    unset($_SESSION['flash']);           // Apaga da sessão (só deve aparecer uma vez)
    return $flash;
}

// Guarda uma flash message e redireciona para outro URL
// Combina setFlash() + header('Location: ...')
function redirectWith(string $url, string $tipo, string $mensagem): void {
    setFlash($tipo, $mensagem); // Guarda a mensagem na sessão
    header("Location: $url");   // Envia o cabeçalho HTTP de redirecionamento
    exit;                        // Para a execução (obrigatório após header Location)
}
