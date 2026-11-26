<?php

require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/usuarios.php';
require_once __DIR__ . '/../models/cpf_funcoes.php';
require_once __DIR__ . '/../models/email_funcoes.php';
session_start();

function voltar_com_alerta(string $msg, string $destino = '../views/user/login.php'): void
{
    echo "<script>alert(" . json_encode($msg) . "); window.location.href = " . json_encode($destino) . ";</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/user/login.php');
    exit;
}

$nome            = trim($_POST['nome'] ?? '');
$email           = strtolower(trim($_POST['email'] ?? ''));
$senha           = $_POST['senha'] ?? '';
$confirmar_senha = $_POST['confirmar_senha'] ?? '';
$cpf_digitado    = trim($_POST['cpf'] ?? '');
$aceitou_termos  = isset($_POST['aceite_termos']);

if ($nome === '' || $email === '' || $senha === '') {
    voltar_com_alerta('Por favor, preencha todos os campos obrigatórios.');
}
if (mb_strlen($nome) > 150) {
    voltar_com_alerta('O nome pode ter no máximo 150 caracteres.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    voltar_com_alerta('Informe um e-mail válido.');
}
$cpf = cpf_normalizar($cpf_digitado);
if ($cpf === null || !cpf_validar_externo($cpf)) {
    voltar_com_alerta('CPF inválido. Confira os números e tente de novo.');
}
if (mb_strlen($senha) < 6) {
    voltar_com_alerta('A senha precisa ter pelo menos 6 caracteres.');
}
if ($senha !== $confirmar_senha) {
    voltar_com_alerta('As senhas não coincidem!');
}
if (!$aceitou_termos) {
    voltar_com_alerta('Para criar a conta é preciso aceitar os Termos de Uso e a Política de Privacidade.');
}

try {
    limpar_cadastros_nao_confirmados($pdo, $email, $cpf);
    if (cpf_em_uso($pdo, $cpf)) {
        voltar_com_alerta('Este CPF já está cadastrado. Se a conta é sua, faça login ou fale com o Suporte.');
    }
    $id_novo = criar_usuario($pdo, $nome, $email, $senha, 'cliente', $cpf, true);
    $env = enviar_verificacao_email($pdo, $id_novo, $nome, $email);
    $_SESSION['verificacao_email'] = $email;
    if (!$env['enviado']) { $_SESSION['verificacao_aviso'] = 'Sua conta foi criada, mas não conseguimos enviar o e-mail agora. Toque em "Reenviar link" em instantes ou fale com a loja.'; }
    if (EMAIL_MODO === 'arquivo' && EMAIL_MOSTRAR_LINK_NA_TELA) { $_SESSION['verificacao_link_teste'] = $env['link']; }
    header('Location: ../views/user/verifique_seu_email.php');
    exit;
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $e_cpf = (bool)preg_match("/key '(?:\\w+\\.)?cpf(?:_hash)?'/i", $e->getMessage());
        voltar_com_alerta($e_cpf ? 'Este CPF já está cadastrado. Se a conta é sua, faça login ou fale com o Suporte.' : 'Este e-mail já está cadastrado! Faça login ou use "Esqueci minha senha".');
    }
    error_log('Erro ao cadastrar: ' . $e->getMessage());
    voltar_com_alerta('Erro ao cadastrar usuário. Tente novamente.');
} catch (RuntimeException $e) {
    error_log('Erro ao cadastrar: ' . $e->getMessage());
    voltar_com_alerta('Não foi possível concluir o cadastro. Avise a loja.');
}
