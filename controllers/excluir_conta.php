<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/conta_funcoes.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/user/login.php');
    exit;
}
function voltar_perfil(string $msg): void
{
    $_SESSION['flash_perfil'] = ['secao' => 'conta', 'ok' => false, 'msg' => $msg];
    header('Location: ../views/user/perfil.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/user/perfil.php');
    exit;
}
if (!hash_equals($_SESSION['csrf_cliente'] ?? '', (string)($_POST['csrf'] ?? ''))) {
    voltar_perfil('Sessão expirada. Tente de novo.');
}

$id = (int)$_SESSION['usuario_id'];
try {
    $st = $pdo->prepare("SELECT senha_hash, tipo_usuario, google_id FROM usuario WHERE id = ? AND ativo = 1");
    $st->execute([$id]);
    $u = $st->fetch();
    if (!$u || $u['tipo_usuario'] !== 'cliente') {
        voltar_perfil('Esta conta não pode ser excluída por aqui.');
    }
    if (strtoupper(trim((string)($_POST['confirmacao'] ?? ''))) !== 'EXCLUIR') {
        voltar_perfil('Para excluir, digite EXCLUIR na confirmação.');
    }
    if (empty($u['google_id']) && !password_verify((string)($_POST['senha'] ?? ''), $u['senha_hash'])) {
        voltar_perfil('Senha incorreta. A conta não foi excluída.');
    }
    if (pedidos_em_andamento($pdo, $id) > 0) {
        voltar_perfil('Você tem pedidos em andamento (pendentes, pagos ou a caminho). Conclua ou cancele em "Meus pedidos" antes de excluir a conta.');
    }

    excluir_conta_cliente($pdo, $id);
} catch (Throwable $e) {
    error_log('excluir_conta: ' . $e->getMessage());
    voltar_perfil('Não foi possível excluir a conta agora. Tente novamente em instantes.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: ../views/user/login.php?conta_excluida=1');
exit;
