<?php
session_start();

$tipo_sessao = $_SESSION['usuario_tipo'] ?? '';
$logado      = isset($_SESSION['usuario_id']);
$eh_cliente  = $logado && $tipo_sessao === 'cliente';
$eh_equipe   = $logado && in_array($tipo_sessao, ['admin', 'vendedor'], true);

$modo_chat = $eh_cliente ? 'cliente' : ($eh_equipe ? 'equipe' : 'visitante');

if ($eh_cliente && empty($_SESSION['csrf_chat'])) {
    $_SESSION['csrf_chat'] = bin2hex(random_bytes(16));
}
$csrf_chat = $eh_cliente ? $_SESSION['csrf_chat'] : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Suporte</title>
  <link rel="stylesheet" href="../../css/style2.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/chat.css">
</head>
<body>

  <header class="site-header">
    <a class="site-header__logo" href="index.php">CICRO</a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <li><a href="index.php">Início</a></li>
        <li><a href="catalogo.php">Catálogo</a></li>
        <li><a href="suporte.php" class="is-active">Suporte</a></li>
        <?php if ($logado): ?><li><a href="meus_pedidos.php">Meus pedidos</a></li><?php endif; ?>
        <li><?php if ($logado): ?><a href="perfil.php">Meu Perfil</a><?php else: ?><a href="login.php">Entrar</a><?php endif; ?></li>
        <li><a href="carrinho.php" class="site-header__cta">Carrinho</a></li>
      </ul>
    </nav>
  </header>

  <main>
    <div class="support-message">
      <img src="../../images/Support.png" alt="Ícone de Suporte">

      <ul class="support-list">
        <li>Está com dificuldades para comprar?</li>
        <li>Encontrou problemas no sistema?</li>
        <li>Ocorreu um erro na finalização da compra?</li>
      </ul>

      <p class="support-title">Entre em contato com nossa equipe:</p>
      <button type="button" class="support-contact" id="abrirChat">Enviar mensagem</button>
    </div>
  </main>

  <footer>
    <p>&copy; 2026 CICRO - Todos os direitos reservados</p>
  </footer>

  <!-- ================= Chat de suporte ================= -->
  <button type="button" class="chat-launcher" id="chatLauncher" aria-label="Abrir chat de suporte" aria-expanded="false" aria-controls="chatWidget">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.6 8.6 0 0 1-3.6-.8L3 21l1.9-5.4A8.4 8.4 0 1 1 21 11.5z"/>
    </svg>
    <span class="chat-badge" id="chatBadge" hidden>0</span>
  </button>

  <section class="chat-widget" id="chatWidget" role="dialog" aria-label="Suporte online CICRO" hidden
           data-api="../../controllers/gerenciar_mensagens.php"
           data-csrf="<?php echo htmlspecialchars($csrf_chat, ENT_QUOTES, 'UTF-8'); ?>"
           data-modo="<?php echo $modo_chat; ?>">
    <header class="chat-widget__topo">
      <div>
        <strong>Suporte CICRO</strong>
        <small id="chatStatus">Responderemos o quanto antes</small>
      </div>
      <div class="chat-widget__acoes">
        <button type="button" id="chatEncerrar" hidden>Encerrar</button>
        <button type="button" id="chatFechar" aria-label="Fechar chat">✕</button>
      </div>
    </header>

    <div class="chat-widget__corpo" id="chatMensagens" aria-live="polite"></div>

    <p class="chat-erro" id="chatErro" role="alert" hidden></p>

    <?php if ($modo_chat === 'cliente'): ?>
      <div class="chat-widget__aviso" id="chatEncerrada" hidden>
        <p>Esta conversa foi encerrada.</p>
        <button type="button" id="chatNova">Iniciar nova conversa</button>
      </div>

      <form class="chat-widget__form" id="chatForm" autocomplete="off">
        <input type="text" id="chatInput" placeholder="Digite sua mensagem..." maxlength="1000" aria-label="Mensagem" required>
        <button type="submit" id="chatEnviar">Enviar</button>
      </form>
    <?php elseif ($modo_chat === 'equipe'): ?>
      <div class="chat-widget__aviso">
        <p>Esta é uma conta da equipe. As conversas dos clientes são respondidas no painel.</p>
        <a href="../admin/chat.php">Abrir atendimento</a>
      </div>
    <?php else: ?>
      <div class="chat-widget__aviso">
        <p>Entre na sua conta para conversar com a nossa equipe.</p>
        <a href="login.php">Entrar</a>
      </div>
    <?php endif; ?>
  </section>

  <script src="../../js/chat-cliente.js"></script>
</body>
</html>
