<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$__tipo = $_SESSION['usuario_tipo'] ?? null;
if (!isset($_SESSION['usuario_id']) || !in_array($__tipo, ['admin', 'vendedor'], true)) {
    header('Location: ../user/login.php');
    exit;
}
unset($__tipo);

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_validar(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        die('Requisição inválida (token expirado). Volte e tente de novo.');
    }
}

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
