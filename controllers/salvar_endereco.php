<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/usuarios.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/user/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/user/perfil.php');
    exit;
}

function voltar_perfil(string $msg, bool $ok = false, int $editar = 0): void
{
    $_SESSION['flash_perfil'] = ['secao' => 'endereco', 'ok' => $ok, 'msg' => $msg];
    $destino = '../views/user/perfil.php';
    if ($editar > 0 && !$ok) {
        $destino .= '?editar=' . $editar . '#endereco-form';
    }
    header('Location: ' . $destino);
    exit;
}

$id_usuario = (int)$_SESSION['usuario_id'];

if (($_SESSION['usuario_tipo'] ?? 'cliente') !== 'cliente') {
    voltar_perfil('Apenas contas de cliente possuem endereço de entrega.');
}

$cep         = trim($_POST['cep'] ?? '');
$apelido     = trim($_POST['apelido'] ?? '') ?: 'Casa';
$rua         = trim($_POST['rua'] ?? '');
$numero      = trim($_POST['numero'] ?? '');
$complemento = trim($_POST['complemento'] ?? '');
$bairro      = trim($_POST['bairro'] ?? '');
$cidade      = trim($_POST['cidade'] ?? '');
$estado      = strtoupper(trim($_POST['estado'] ?? ''));
$principal   = isset($_POST['principal']) ? 1 : 0;
$id_edicao   = (int)($_POST['id'] ?? 0);

$cep_digitos = preg_replace('/\D/', '', $cep);
if (strlen($cep_digitos) !== 8) {
    voltar_perfil('CEP inválido. Informe 8 números.', false, $id_edicao);
}
$cep = substr($cep_digitos, 0, 5) . '-' . substr($cep_digitos, 5);

if ($rua === '' || $numero === '' || $bairro === '' || $cidade === '' || strlen($estado) !== 2) {
    voltar_perfil('Preencha todos os campos do endereço (rua, número, bairro, cidade e UF).', false, $id_edicao);
}

try {
    $pdo->beginTransaction();

    $st_dup = $pdo->prepare("SELECT apelido FROM endereco WHERE fk_cliente = ? AND cep = ? AND id <> ? LIMIT 1");
    $st_dup->execute([$id_usuario, $cep, $id_edicao]);
    $dup = $st_dup->fetch();
    if ($dup) {
        $pdo->rollBack();
        voltar_perfil('Você já tem um endereço com o CEP ' . $cep . ' ("' . $dup['apelido'] . '"). Edite esse endereço em vez de cadastrar outro.', false, $id_edicao);
    }

    garantir_perfil_filho($pdo, $id_usuario, 'cliente');

    if ($id_edicao > 0) {
        $stmt = $pdo->prepare("SELECT principal FROM endereco WHERE id = ? AND fk_cliente = ? FOR UPDATE");
        $stmt->execute([$id_edicao, $id_usuario]);
        $atual = $stmt->fetch();
        if (!$atual) {
            $pdo->rollBack();
            voltar_perfil('Endereço não encontrado.');
        }

        if ((int)$atual['principal'] === 1) {
            $principal = 1;
        }
        if ($principal === 1) {
            $pdo->prepare("UPDATE endereco SET principal = 0 WHERE fk_cliente = ?")->execute([$id_usuario]);
        }

        $pdo->prepare(
            "UPDATE endereco
                SET apelido = ?, cep = ?, rua = ?, numero = ?, complemento = ?,
                    bairro = ?, cidade = ?, estado = ?, principal = ?
              WHERE id = ? AND fk_cliente = ?"
        )->execute([
            $apelido, $cep, $rua, $numero,
            $complemento !== '' ? $complemento : null,
            $bairro, $cidade, $estado, $principal,
            $id_edicao, $id_usuario,
        ]);

        $pdo->commit();
        voltar_perfil('Endereço atualizado com sucesso!', true);
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM endereco WHERE fk_cliente = ?");
    $stmt->execute([$id_usuario]);
    if ((int)$stmt->fetchColumn() === 0) {
        $principal = 1;
    }

    if ($principal === 1) {
        $pdo->prepare("UPDATE endereco SET principal = 0 WHERE fk_cliente = ?")->execute([$id_usuario]);
    }

    $pdo->prepare(
        "INSERT INTO endereco (fk_cliente, apelido, cep, rua, numero, complemento, bairro, cidade, estado, principal)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        $id_usuario, $apelido, $cep, $rua, $numero,
        $complemento !== '' ? $complemento : null,
        $bairro, $cidade, $estado, $principal,
    ]);

    $pdo->commit();
    voltar_perfil('Endereço salvo com sucesso!', true);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erro ao salvar endereço: ' . $e->getMessage());
    if ($e->getCode() === '23000') {
        voltar_perfil('Você já tem um endereço com este CEP.', false, $id_edicao);
    }
    voltar_perfil('Não foi possível salvar o endereço. Tente novamente.');
}
