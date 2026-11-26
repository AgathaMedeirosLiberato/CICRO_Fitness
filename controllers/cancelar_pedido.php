<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/pedido_funcoes.php';

const VOLTAR = '../views/user/meus_pedidos.php';
function voltar(string $msg, bool $ok): void
{
    $_SESSION['flash_pedidos'] = ['ok' => $ok, 'msg' => $msg];
    header('Location: ' . VOLTAR);
    exit;
}

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] ?? '') !== 'cliente') {
    header('Location: ../views/user/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . VOLTAR);
    exit;
}
if (!hash_equals($_SESSION['csrf_cancelar'] ?? '', (string)($_POST['csrf'] ?? ''))) {
    voltar('Sessão expirada. Tente de novo.', false);
}

$id_pedido = (int)($_POST['pedido'] ?? 0);
try {
    cancelar_pedido_do_cliente($pdo, $id_pedido, (int)$_SESSION['usuario_id']);
    voltar('Pedido de ' . ($_SESSION['usuario_nome'] ?? 'cliente') . ' cancelado. As peças voltaram ao estoque.', true);
} catch (RuntimeException $e) {
    voltar($e->getMessage(), false);
} catch (Throwable $e) {
    error_log('Cancelar pedido: ' . $e->getMessage());
    voltar('Não foi possível cancelar o pedido.', false);
}
