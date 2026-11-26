<?php
session_start();
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../models/pedido_funcoes.php';
require_once __DIR__ . '/../../models/pagamento_funcoes.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id_pedido  = (int)($_GET['id'] ?? 0);
$id_cliente = (int)$_SESSION['usuario_id'];

expirar_pedidos_pendentes($pdo);

$s = $pdo->prepare("SELECT p.id, p.status, p.valor_total, p.data_criacao,
                           p.endereco_entrega, p.forma_pagamento, pg.data_pagamento, pg.status AS pag_status, pg.simulado AS pag_simulado
                    FROM pedido p LEFT JOIN pagamento pg ON pg.fk_pedido = p.id
                    WHERE p.id = ? AND p.fk_cliente = ?");
$s->execute([$id_pedido, $id_cliente]);
$pedido = $s->fetch();
if (!$pedido) {
    header('Location: catalogo.php');
    exit;
}

$s = $pdo->prepare("SELECT nome_produto, tamanho, cor, quantidade, preco_unitario FROM item_pedido WHERE fk_pedido = ?");
$s->execute([$id_pedido]);
$itens = $s->fetchAll();

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$status = $pedido['status'];
if ($status === 'Pendente') {
    $titulo = 'Pedido recebido, aguardando pagamento';
    $texto  = 'Assim que o pagamento for confirmado, separamos o seu pedido.';
    $classe = 'pg-banner--espera';
} elseif ($status === 'Cancelado') {
    $titulo = 'Pedido cancelado';
    $texto  = 'Este pedido foi cancelado (pelo cliente, pela loja ou por falta de pagamento no prazo). Se foi engano, faça um novo pedido pelo catálogo.';
    $classe = 'pg-banner--erro';
} else {
    $titulo = 'Pagamento confirmado!';
    $texto  = 'Obrigado pela compra. Acompanhe o andamento do pedido por aqui.';
    $classe = 'pg-banner--ok';
}
$etapas = ['Pendente' => 'Pedido feito', 'Pago' => 'Pago', 'Em Preparação' => 'Em preparação', 'Enviado' => 'Enviado', 'Entregue' => 'Entregue'];
$ordem  = array_keys($etapas);
$pos    = array_search($status, $ordem, true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Pedido de <?= h($_SESSION['usuario_nome'] ?? 'cliente') ?></title>
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/checkout.css">
</head>
<body>
  <header class="site-header">
    <a class="site-header__logo" href="index.php">CICRO</a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <li><a href="index.php">Início</a></li>
        <li><a href="catalogo.php">Catálogo</a></li>
        <li><a href="suporte.php">Suporte</a></li>
        <li><a href="meus_pedidos.php">Meus pedidos</a></li>
        <li><a href="perfil.php">Meu Perfil</a></li>
        <li><a href="carrinho.php" class="site-header__cta">Carrinho</a></li>
      </ul>
    </nav>
  </header>

  <main class="checkout">
    <div class="pg-banner <?= $classe ?>">
      <h1><?= h($titulo) ?></h1>
      <p><?= h($texto) ?></p>
      <?php if ($status === 'Pendente'): ?>
        <a class="ck-confirmar pg-banner__btn" href="pagamento.php?id=<?= (int)$pedido['id'] ?>">Pagar agora →</a>
      <?php endif; ?>
    </div>

    <?php if ($pos !== false): ?>
      <ol class="pg-trilha" aria-label="Andamento do pedido">
        <?php foreach ($etapas as $chave => $rotulo): $i = array_search($chave, $ordem, true); ?>
          <li class="<?= $i < $pos ? 'feito' : ($i === $pos ? 'atual' : '') ?>"><span><?= $i + 1 ?></span><?= h($rotulo) ?></li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>

    <div class="checkout__layout">
      <div class="checkout__passos">
        <section class="ck-card">
          <h2>Itens do pedido de <?= h($_SESSION['usuario_nome'] ?? 'cliente') ?></h2>
          <ul class="ck-itens">
            <?php foreach ($itens as $i): ?>
              <li>
                <div>
                  <strong><?= h($i['nome_produto']) ?></strong>
                  <span>Tam. <?= h($i['tamanho']) ?><?= !in_array($i['cor'], ['', 'Padrão'], true) ? ' · ' . h($i['cor']) : '' ?> · <?= (int)$i['quantidade'] ?> × <?= brl($i['preco_unitario']) ?></span>
                </div>
                <b><?= brl($i['preco_unitario'] * $i['quantidade']) ?></b>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>

        <section class="ck-card">
          <h2>Entrega e pagamento</h2>
          <p class="pg-info"><strong>Entrega em:</strong> <?= h($pedido['endereco_entrega'] ?: 'não registrada') ?></p>
          <p class="pg-info"><strong>Pagamento:</strong> <?= h(FORMAS_PAGAMENTO[$pedido['forma_pagamento'] ?? ''] ?? 'não informado') ?>
            · <?= h(ROTULO_PAGAMENTO[$pedido['pag_status'] ?? ''] ?? ($status === 'Cancelado' ? 'Cancelado' : ($status === 'Pendente' ? 'Aguardando pagamento' : 'Pago'))) ?><?= !empty($pedido['pag_simulado']) ? ' (demonstração)' : '' ?>
            <?php if ($pedido['data_pagamento']): ?> em <?= h(date('d/m/Y H:i', strtotime($pedido['data_pagamento']))) ?><?php endif; ?>
          </p>
          <p class="pg-info"><strong>Feito em:</strong> <?= h(date('d/m/Y H:i', strtotime($pedido['data_criacao']))) ?></p>
        </section>
      </div>

      <aside class="ck-resumo">
        <span class="checkout__kicker">RESUMO</span>
        <h3>Valores</h3>
        <div class="ck-total"><span>Total</span><strong><?= brl($pedido['valor_total']) ?></strong></div>
        <a href="catalogo.php" class="ck-secundario">Continuar comprando</a>
      </aside>
    </div>
  </main>

  <footer class="ck-rodape"><p>&copy; 2026 CICRO - Todos os direitos reservados</p></footer>
</body>
</html>
