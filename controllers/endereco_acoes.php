<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

function voltar(string $msg, bool $ok): void
{
    $_SESSION['flash_perfil'] = ['secao' => 'endereco', 'ok' => $ok, 'msg' => $msg];
    header('Location: ../views/user/perfil.php');
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/user/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/user/perfil.php');
    exit;
}
if (!hash_equals($_SESSION['csrf_cliente'] ?? '', (string)($_POST['csrf'] ?? ''))) {
    voltar('Sessão expirada. Tente de novo.', false);
}
if (($_SESSION['usuario_tipo'] ?? '') !== 'cliente') {
    voltar('Apenas contas de cliente possuem endereço de entrega.', false);
}

$id_cliente  = (int)$_SESSION['usuario_id'];
$id_endereco = (int)($_POST['id'] ?? 0);
$acao        = $_POST['acao'] ?? '';

try {
    $pdo->beginTransaction();

    $s = $pdo->prepare("SELECT principal FROM endereco WHERE id = ? AND fk_cliente = ? FOR UPDATE");
    $s->execute([$id_endereco, $id_cliente]);
    $end = $s->fetch();
    if (!$end) {
        $pdo->rollBack();
        voltar('Endereço não encontrado.', false);
    }

    if ($acao === 'padrao') {
        $pdo->prepare("UPDATE endereco SET principal = 0 WHERE fk_cliente = ?")->execute([$id_cliente]);
        $pdo->prepare("UPDATE endereco SET principal = 1 WHERE id = ? AND fk_cliente = ?")->execute([$id_endereco, $id_cliente]);
        $pdo->commit();
        voltar('Endereço padrão atualizado.', true);
    }

    if ($acao === 'excluir') {
        $pdo->prepare("DELETE FROM endereco WHERE id = ? AND fk_cliente = ?")->execute([$id_endereco, $id_cliente]);

        if ((int)$end['principal'] === 1) {
            $pdo->prepare(
                "UPDATE endereco SET principal = 1
                 WHERE fk_cliente = ? ORDER BY id DESC LIMIT 1"
            )->execute([$id_cliente]);
        }
        $pdo->commit();
        voltar('Endereço excluído.', true);
    }

    $pdo->rollBack();
    voltar('Ação inválida.', false);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erro em endereco_acoes: ' . $e->getMessage());
    voltar('Não foi possível concluir a ação. Tente novamente.', false);
}
