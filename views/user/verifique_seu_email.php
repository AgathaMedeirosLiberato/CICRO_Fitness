<?php
session_start();
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../models/email_funcoes.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf_verif'])) { $_SESSION['csrf_verif'] = bin2hex(random_bytes(16)); }

$email = (string)($_SESSION['verificacao_email'] ?? '');
$aviso = (string)($_SESSION['verificacao_aviso'] ?? ''); unset($_SESSION['verificacao_aviso']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_post = trim($_POST['email'] ?? '');
    if (!hash_equals($_SESSION['csrf_verif'], (string)($_POST['csrf'] ?? ''))) {
        $aviso = 'Sessão expirada. Tente de novo.';
    } elseif (!filter_var($email_post, FILTER_VALIDATE_EMAIL)) {
        $aviso = 'Informe um e-mail válido.';
    } else {
        $email = $email_post;
        $_SESSION['verificacao_email'] = $email;
        $st = $pdo->prepare("SELECT id, nome, verificacao_enviada_em FROM usuario WHERE email = ? AND tipo_usuario = 'cliente' AND email_verificado = 0 AND ativo = 1 LIMIT 1");
        $st->execute([$email]);
        $u = $st->fetch();
        $recente = $u && $u['verificacao_enviada_em'] && (time() - strtotime($u['verificacao_enviada_em'])) < VERIFICACAO_REENVIO_SEGUNDOS;
        if ($recente) {
            $aviso = 'Aguarde um minuto antes de pedir outro link.';
        } else {
            if ($u) {
                $env = enviar_verificacao_email($pdo, (int)$u['id'], $u['nome'], $email);
                if (EMAIL_MODO === 'arquivo' && EMAIL_MOSTRAR_LINK_NA_TELA) { $_SESSION['verificacao_link_teste'] = $env['link']; }
                if (!$env['enviado']) { $aviso = 'Não foi possível enviar o e-mail agora. Tente de novo em alguns minutos ou fale com a loja.'; }
            }
            if ($aviso === '') { $aviso = 'Se existe uma conta aguardando confirmação para este e-mail, enviamos um novo link.'; }
        }
    }
}
$link_teste = (EMAIL_MODO === 'arquivo' && EMAIL_MOSTRAR_LINK_NA_TELA) ? ($_SESSION['verificacao_link_teste'] ?? '') : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Confirme o seu e-mail</title>
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/meus-pedidos.css">
</head>
<body class="mp-body">
  <header class="site-header"><a class="site-header__logo" href="index.php">CICRO</a>
    <nav class="site-header__nav"><ul><li><a href="catalogo.php">Catálogo</a></li><li><a href="login.php" class="site-header__cta">Entrar</a></li></ul></nav></header>
  <main class="mp" style="max-width:560px">
    <div class="mp-pedido" style="padding:28px">
      <span class="mp__kicker">QUASE LÁ</span>
      <h1 style="font-size:1.6rem;margin:4px 0 10px;color:#111">Confirme o seu e-mail</h1>
      <p style="color:#555;line-height:1.5">
        <?php if ($email !== ''): ?>Enviamos um link de confirmação para <strong><?= h($email) ?></strong>.<?php else: ?>Enviamos um link de confirmação para o e-mail do cadastro.<?php endif; ?>
        Clique nele para ativar a conta e depois faça o login. Olhe também a caixa de spam. O link vale <?= (int)VERIFICACAO_VALIDADE_HORAS ?> horas.
      </p>
      <?php if ($aviso): ?><p class="mp-aviso" style="margin-top:14px" role="status"><?= h($aviso) ?></p><?php endif; ?>
      <?php if ($link_teste): ?>
        <div style="margin-top:16px;padding:12px 14px;border:1px dashed #999;border-radius:12px;font-size:.85rem;word-break:break-all">
          <strong>Modo de teste</strong> (sem servidor de e-mail): <a href="<?= h($link_teste) ?>">abrir o link de confirmação</a>
        </div>
      <?php endif; ?>
      <form method="post" class="mp-busca" style="margin-top:20px">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_verif']) ?>">
        <input type="email" name="email" value="<?= h($email) ?>" placeholder="Seu e-mail" maxlength="150" required>
        <button type="submit">Reenviar link</button>
      </form>
      <p style="margin-top:14px"><a href="login.php" style="color:#111;font-weight:700">Voltar ao login</a></p>
    </div>
  </main>
</body>
</html>
