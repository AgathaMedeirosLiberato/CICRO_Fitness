<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/usuarios.php';

function voltar_login(string $msg, string $destino = '../views/user/login.php'): void
{
    echo "<script>alert(" . json_encode($msg) . "); window.location.href = " . json_encode($destino) . ";</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/user/login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($email === '' || $senha === '') {
    voltar_login('Preencha todos os campos!');
}

if (mb_strlen($senha) < 6) {
    voltar_login('A senha tem no mínimo 6 caracteres.');
}

try {
    $stmt = $pdo->prepare(
        "SELECT id, nome, senha_hash, tipo_usuario, email_verificado FROM usuario
         WHERE email = ? AND ativo = 1 LIMIT 1"
    );
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
        voltar_login('E-mail ou senha incorretos!');
    }

    if ($usuario['tipo_usuario'] === 'cliente' && !(int)$usuario['email_verificado']) {
        $_SESSION['verificacao_email'] = $email;
        voltar_login('Confirme o seu e-mail para entrar. Enviamos um link quando você se cadastrou; se não achou, peça outro na próxima tela.', '../views/user/verifique_seu_email.php');
    }

    garantir_perfil_filho($pdo, (int)$usuario['id'], $usuario['tipo_usuario']);

    session_regenerate_id(true);
    $_SESSION['usuario_id']   = (int)$usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];
    $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];

    if ($usuario['tipo_usuario'] === 'cliente') {
        header('Location: ../views/user/index.php');
    } else {
        header('Location: ../views/admin/inicio.php');
    }
    exit;
} catch (PDOException $e) {
    error_log('Erro no login: ' . $e->getMessage());
    voltar_login('Erro ao entrar. Tente novamente.');
}
