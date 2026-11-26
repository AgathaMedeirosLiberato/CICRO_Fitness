<?php
session_start();
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../models/recuperacao_senha.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf_redef'])) { $_SESSION['csrf_redef'] = bin2hex(random_bytes(16)); }

$codigo = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$u = usuario_do_codigo_recuperacao($pdo, $codigo);
$erro = ''; $ok = false;

if ($u && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova = (string)($_POST['senha'] ?? ''); $conf = (string)($_POST['confirmar_senha'] ?? '');
    if (!hash_equals($_SESSION['csrf_redef'], (string)($_POST['csrf'] ?? ''))) {
        $erro = 'Sessão expirada. Recarregue a página e tente de novo.';
    } elseif (mb_strlen($nova) < 6) {
        $erro = 'A senha precisa ter pelo menos 6 caracteres.';
    } elseif ($nova !== $conf) {
        $erro = 'As senhas não coincidem.';
    } else {
        $pdo->prepare("UPDATE usuario SET senha_hash = ?, token_recuperacao = NULL, recuperacao_expira = NULL, email_verificado = 1 WHERE id = ?")
            ->execute([password_hash($nova, PASSWORD_DEFAULT), (int)$u['id']]);
        $ok = true;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Nova senha</title>
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/meus-pedidos.css">
</head>
<body class="mp-body">
  <header class="site-header"><a class="site-header__logo" href="index.php">CICRO</a>
    <nav class="site-header__nav"><ul><li><a href="catalogo.php">Catálogo</a></li><li><a href="login.php" class="site-header__cta">Entrar</a></li></ul></nav></header>
  <main class="mp" style="max-width:560px">
    <div class="mp-pedido" style="padding:28px">
      <?php if ($ok): ?>
        <h1 style="font-size:1.6rem;color:#111;margin-bottom:8px">Senha alterada!</h1>
        <p style="color:#555;margin-bottom:18px">Agora é só entrar com a nova senha.</p>
        <a class="mp-btn mp-btn--escuro" href="login.php">Ir para o login</a>
      <?php elseif (!$u): ?>
        <h1 style="font-size:1.6rem;color:#111;margin-bottom:8px">Link inválido ou expirado</h1>
        <p style="color:#555;margin-bottom:18px">Peça um novo link para redefinir a senha.</p>
        <a class="mp-btn mp-btn--escuro" href="esqueci_senha.php">Pedir novo link</a>
      <?php else: ?>
        <span class="mp__kicker">NOVA SENHA</span>
        <h1 style="font-size:1.6rem;margin:4px 0 10px;color:#111">Escolha uma nova senha</h1>
        <p style="color:#555">Conta: <strong><?= h($u['email']) ?></strong></p>
        <?php if ($erro): ?><p class="mp-aviso" style="margin-top:14px" role="alert"><?= h($erro) ?></p><?php endif; ?>
        <form method="post" style="margin-top:18px;display:grid;gap:10px">
          <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_redef']) ?>">
          <input type="hidden" name="token" value="<?= h($codigo) ?>">
          <input type="password" name="senha" placeholder="Nova senha (mínimo 6 caracteres)" minlength="6" required autofocus style="padding:12px;border:1px solid #ccc;border-radius:10px">
          <input type="password" name="confirmar_senha" placeholder="Repita a nova senha" minlength="6" required style="padding:12px;border:1px solid #ccc;border-radius:10px">
          <button type="submit" class="mp-btn mp-btn--escuro" style="border:0;cursor:pointer">Salvar nova senha</button>
        </form>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
