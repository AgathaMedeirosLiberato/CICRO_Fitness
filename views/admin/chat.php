<?php
require_once __DIR__ . '/../../controllers/auth_admin.php';

if (empty($_SESSION['csrf_chat'])) {
    $_SESSION['csrf_chat'] = bin2hex(random_bytes(16));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Atendimento | CICRO</title>
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/notificacoes-admin.css">
  <link rel="stylesheet" href="../../css/chat.css">
</head>
<body class="pagina-chat">

  <header class="site-header">
    <a class="site-header__logo" href="inicio.php">CICRO</a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <li><a href="inicio.php">Painel</a></li>
        <li><a href="../../controllers/logout.php" class="site-header__cta">Sair</a></li>
      </ul>
    </nav>
  </header>

  <main class="atendimento" id="atendimento"
        data-api="../../controllers/gerenciar_mensagens.php"
        data-csrf="<?php echo htmlspecialchars($_SESSION['csrf_chat'], ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Coluna da esquerda: conversas -->
    <aside class="atendimento__lista">
      <div class="atendimento__titulo">Atendimento</div>
      <div class="atendimento__abas" role="tablist">
        <button type="button" class="is-active" data-status="Aberta" role="tab" aria-selected="true">
          Abertas <span class="aba-contagem" id="contagemAbertas" hidden>0</span>
        </button>
        <button type="button" data-status="Encerrada" role="tab" aria-selected="false">Encerradas</button>
      </div>
      <div class="atendimento__itens" id="listaConversas"></div>
    </aside>

    <!-- Coluna da direita: conversa selecionada -->
    <section class="atendimento__conversa">
      <div class="atendimento__sem-conversa" id="semConversa">
        Selecione uma conversa ao lado para responder.
      </div>

      <div class="conversa-area" id="areaConversa" hidden>
        <div class="conversa-topo">
          <button type="button" class="btn-voltar-lista" id="btnVoltarLista">← Conversas</button>
          <div class="conversa-topo__info">
            <strong id="clienteNome"></strong>
            <small id="conversaInfo"></small>
          </div>
          <button type="button" class="btn-encerrar" id="btnEncerrar">Encerrar conversa</button>
        </div>

        <div class="conversa-mensagens" id="adminMensagens" aria-live="polite"></div>

        <p class="chat-erro" id="adminErro" role="alert" hidden></p>

        <form class="conversa-form" id="adminForm" autocomplete="off">
          <input type="text" id="adminInput" placeholder="Digite a resposta..." maxlength="1000" aria-label="Resposta" required>
          <button type="submit" id="adminEnviar">Responder</button>
        </form>
        <p class="conversa-encerrada" id="avisoEncerrada" hidden>Esta conversa foi encerrada.</p>
      </div>
    </section>
  </main>

  <script src="../../js/chat-admin.js"></script>
  <script>window.CICRO_NOTIF = {pedidos: false};</script>
  <script src="../../js/notificacoes-admin.js"></script>
  <script src="../../js/confirmar-saida.js"></script>
</body>
</html>
