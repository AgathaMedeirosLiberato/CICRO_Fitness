<?php
session_start();
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../models/recuperacao_senha.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf_recup'])) { $_SESSION['csrf_recup'] = bin2hex(random_bytes(16)); }

$aviso = ''; $enviado = false; $link_teste = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!hash_equals($_SESSION['csrf_recup'], (string)($_POST['csrf'] ?? ''))) {
        $aviso = 'Sessão expirada. Tente de novo.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $aviso = 'Informe um e-mail válido.';
    } else {
        $st = $pdo->prepare("SELECT id, nome, recuperacao_enviada_em FROM usuario WHERE email = ? AND ativo = 1 LIMIT 1");
        $st->execute([$email]);
        $u = $st->fetch();
        if ($u && $u['recuperacao_enviada_em'] && (time() - strtotime($u['recuperacao_enviada_em'])) < RECUPERACAO_REENVIO_SEGUNDOS) {
            $aviso = 'Aguarde um minuto antes de pedir outro link.';
        } else {
            if ($u) {
                $env = enviar_recuperacao_senha($pdo, (int)$u['id'], $u['nome'], $email);
                if (EMAIL_MODO === 'arquivo' && EMAIL_MOSTRAR_LINK_NA_TELA) { $link_teste = $env['link']; }
                if (!$env['enviado']) { $aviso = 'Não foi possível enviar o e-mail agora. Tente de novo em alguns minutos ou fale com a loja.'; }
            }
            if ($aviso === '') { $enviado = true; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Esqueci minha senha</title>
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/meus-pedidos.css">
</head>
<body class="mp-body">
  <header class="site-header"><a class="site-header__logo" href="index.php">CICRO</a>
    <nav class="site-header__nav"><ul><li><a href="catalogo.php">Catálogo</a></li><li><a href="login.php" class="site-header__cta">Entrar</a></li></ul></nav></header>
  <main class="mp" style="max-width:560px">
    <div class="mp-pedido" style="padding:28px">
      <span class="mp__kicker">SENHA</span>
      <h1 style="font-size:1.6rem;margin:4px 0 10px;color:#111">Esqueceu a senha?</h1>
      <?php if ($enviado): ?>
        <p style="color:#555;line-height:1.5">Se existe uma conta com esse e-mail, enviamos um link para criar uma nova senha. Ele vale <?= (int)RECUPERACAO_VALIDADE_MINUTOS ?> minutos. Olhe também a caixa de spam.</p>
        <?php if ($link_teste): ?>
          <div style="margin-top:16px;padding:12px 14px;border:1px dashed #999;border-radius:12px;font-size:.85rem;word-break:break-all">
            <strong>Modo de teste</strong> (sem servidor de e-mail): <a href="<?= h($link_teste) ?>">abrir o link de redefinição</a>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <p style="color:#555;line-height:1.5">Informe o e-mail da sua conta. Vamos enviar um link para você escolher uma nova senha.</p>
        <?php if ($aviso): ?><p class="mp-aviso" style="margin-top:14px" role="status"><?= h($aviso) ?></p><?php endif; ?>
        <form method="post" class="mp-busca" style="margin-top:20px">
          <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_recup']) ?>">
          <input type="email" name="email" placeholder="Seu e-mail" maxlength="150" required autofocus>
          <button type="submit">Enviar link</button>
        </form>
      <?php endif; ?>
      <p style="margin-top:14px"><a href="login.php" style="color:#111;font-weight:700">Voltar ao login</a></p>
    </div>
  </main>
</body>
</html>
