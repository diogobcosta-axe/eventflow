<?php
// includes/helpers.php — Funções auxiliares usadas em todas as páginas

// Protege o output contra XSS
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Formata uma data de BD (ex: "2026-07-15 09:00") para formato legível
function formatDate(string $date, bool $comHora = true): string {
    $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    $ts    = strtotime($date);
    $texto = date('d', $ts) . ' ' . $meses[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
    if ($comHora) {
        $texto .= ' · ' . date('H:i', $ts);
    }
    return $texto;
}

// Conta inscrições confirmadas num evento
function countInscritos(int $evento_id): int {
    $db   = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM inscricoes WHERE evento_id = ? AND estado = 'confirmada'");
    $stmt->execute([$evento_id]);
    return (int)$stmt->fetchColumn();
}

// Verifica se um utilizador está inscrito num evento (devolve a linha ou null)
function isInscrito(int $user_id, int $evento_id): ?array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM inscricoes WHERE utilizador_id = ? AND evento_id = ?");
    $stmt->execute([$user_id, $evento_id]);
    $resultado = $stmt->fetch();
    return $resultado ?: null;
}

// Devolve o número de vagas disponíveis num evento
function vagasDisponiveis(array $evento): int {
    return max(0, $evento['vagas'] - countInscritos($evento['id']));
}

// --- CSRF (proteção contra ataques Cross-Site Request Forgery) ---

// Gera e guarda um token único na sessão
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Valida o token enviado pelo formulário
function validateCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Token CSRF inválido. Volta atrás e tenta novamente.');
    }
}

// --- Flash messages (mensagens de sucesso/erro após redirect) ---

function setFlash(string $tipo, string $mensagem): void {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// Guarda uma flash message e redireciona
function redirectWith(string $url, string $tipo, string $mensagem): void {
    setFlash($tipo, $mensagem);
    header("Location: $url");
    exit;
}
