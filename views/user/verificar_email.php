<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../models/email_funcoes.php';

$codigo = (string)($_GET['token'] ?? '');
$ok = false; $msg = 'Link inválido ou expirado. Peça um novo link na tela de confirmação.';
if (preg_match('/^[a-f0-9]{64}$/', $codigo)) {
    $st = $pdo->prepare("SELECT id, email_verificado, token_expira FROM usuario WHERE token_verificacao = ? LIMIT 1");
    $st->execute([hash('sha256', $codigo)]);
    $u = $st->fetch();
    if ($u && $u['token_expira'] && strtotime($u['token_expira']) >= time()) {
        $pdo->prepare("UPDATE usuario SET email_verificado = 1, token_verificacao = NULL, token_expira = NULL WHERE id = ?")->execute([(int)$u['id']]);
        $ok = true; $msg = 'E-mail confirmado! Agora é só fazer o login.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Confirmação de e-mail</title>
  <link rel="stylesheet" href="../../css/header.css"><link rel="stylesheet" href="../../css/meus-pedidos.css">
</head>
<body class="mp-body">
  <header class="site-header"><a class="site-header__logo" href="index.php">CICRO</a></header>
  <main class="mp" style="max-width:560px">
    <div class="mp-pedido" style="padding:28px;text-align:center">
      <h1 style="font-size:1.6rem;color:#111;margin-bottom:8px"><?= $ok ? 'Tudo certo!' : 'Não deu certo' ?></h1>
      <p style="color:#555;margin-bottom:18px"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
      <a class="mp-btn mp-btn--escuro" href="<?= $ok ? 'login.php' : 'verifique_seu_email.php' ?>"><?= $ok ? 'Ir para o login' : 'Pedir novo link' ?></a>
    </div>
  </main>
</body>
</html>
