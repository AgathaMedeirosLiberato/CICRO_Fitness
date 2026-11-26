<?php
session_start();
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../models/pedido_funcoes.php';
require_once __DIR__ . '/../../models/pagamento_funcoes.php';
require_once __DIR__ . '/../../models/carrinho_selecao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
if (($_SESSION['usuario_tipo'] ?? '') !== 'cliente') {
    header('Location: carrinho.php');
    exit;
}
$id_cliente = (int)$_SESSION['usuario_id'];

if (empty($_SESSION['csrf_cliente'])) {
    $_SESSION['csrf_cliente'] = bin2hex(random_bytes(16));
}

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    carrinho_definir_selecao((array)($_POST['sel'] ?? []));
    if (!carrinho_selecao()) {
        header('Location: carrinho.php');
        exit;
    }
}
$sel_atual = carrinho_selecao();
$filtro_sel = $sel_atual ? ' AND ic.id IN (' . carrinho_marcas($sel_atual) . ')' : '';
$s = $pdo->prepare(
    "SELECT ic.id AS item_id, ic.quantidade, vp.tamanho, vp.quantidade_estoque, p.nome, p.status, ip.cor,
            (p.preco_base + vp.preco_adicional) AS preco,
            COALESCE(ip.url_imagem,
                     (SELECT url_imagem FROM imagem_produto WHERE fk_produto = p.id ORDER BY ordem ASC, id ASC LIMIT 1)) AS url_imagem
     FROM item_carrinho ic
     JOIN carrinho c          ON c.id  = ic.fk_carrinho
     JOIN variacao_produto vp ON vp.id = ic.fk_variacao
     JOIN produto p           ON p.id  = vp.fk_produto
     LEFT JOIN imagem_produto ip ON ip.id = ic.fk_imagem
     WHERE c.fk_cliente = ?$filtro_sel
     ORDER BY ic.id DESC"
);
$s->execute(array_merge([$id_cliente], $sel_atual ?: []));
$itens = $s->fetchAll();

if (!$itens) {
    unset($_SESSION['carrinho_sel']);
    header('Location: carrinho.php');
    exit;
}
carrinho_definir_selecao(array_column($itens, 'item_id'));

$subtotal = 0.0;
$problemas = [];
foreach ($itens as $i) {
    $subtotal += (float)$i['preco'] * (int)$i['quantidade'];
    if (!(int)$i['status']) {
        $problemas[] = '"' . $i['nome'] . '" não está mais disponível.';
    } elseif ((int)$i['quantidade'] > (int)$i['quantidade_estoque']) {
        $problemas[] = '"' . $i['nome'] . '" (' . $i['tamanho'] . ') tem só ' . (int)$i['quantidade_estoque'] . ' unidade(s) em estoque.';
    }
}
$st = $pdo->prepare("SELECT cpf FROM cliente WHERE id_usuario = ?");
$st->execute([$id_cliente]);
$cpf_cliente = $st->fetchColumn() ?: null;

$total = $subtotal;

$s = $pdo->prepare(
    "SELECT id, apelido, cep, rua, numero, complemento, bairro, cidade, estado, principal
     FROM endereco WHERE fk_cliente = ? ORDER BY principal DESC, id DESC"
);
$s->execute([$id_cliente]);
$enderecos = $s->fetchAll();

$flash = $_SESSION['flash_carrinho'] ?? null;
unset($_SESSION['flash_carrinho']);

$imagem_padrao = '../../images/uploads/CICRO.png';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Finalizar compra</title>
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
        <li><a href="carrinho.php" class="is-active site-header__cta">Carrinho</a></li>
      </ul>
    </nav>
  </header>

  <main class="checkout">
    <div class="checkout__topo">
      <span class="checkout__kicker">CICRO / FINALIZAR COMPRA</span>
      <h1>Finalizar compra</h1>
      <a href="carrinho.php" class="checkout__voltar">← Voltar ao carrinho</a>
    </div>

    <?php if ($flash): ?><div class="ck-aviso ck-aviso--erro" role="alert"><?= h($flash) ?></div><?php endif; ?>
    <?php if (!$cpf_cliente): ?><div class="ck-aviso ck-aviso--erro" role="alert">Para comprar, precisamos do seu CPF. <a href="perfil.php">Informe o CPF no seu perfil</a> e volte para finalizar.</div><?php endif; ?>
    <?php foreach ($problemas as $p): ?><div class="ck-aviso ck-aviso--erro" role="alert"><?= h($p) ?> Ajuste o carrinho para continuar.</div><?php endforeach; ?>

    <form action="../../controllers/finalizar_pedido.php" method="POST" class="checkout__layout" id="form-checkout">
      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_cliente']) ?>">

      <div class="checkout__passos">

        <!-- 1) ENDEREÇO -->
        <section class="ck-card" aria-labelledby="t-endereco">
          <h2 id="t-endereco"><span class="ck-num">1</span> Endereço de entrega</h2>

          <?php if (!$enderecos): ?>
            <p class="ck-vazio">Você ainda não tem endereço cadastrado.</p>
            <a class="ck-link-btn" href="perfil.php#novo-endereco">+ Cadastrar endereço</a>
          <?php else: ?>
            <div class="ck-opcoes">
              <?php foreach ($enderecos as $n => $e): ?>
                <label class="ck-opcao">
                  <input type="radio" name="endereco_id" value="<?= (int)$e['id'] ?>" <?= $n === 0 ? 'checked' : '' ?> required>
                  <span class="ck-opcao__corpo">
                    <strong><?= h($e['apelido']) ?></strong>
                    <?php if ($e['principal']): ?><em class="ck-selo">Padrão</em><?php endif; ?>
                    <span><?= h($e['rua'] . ', nº ' . $e['numero'] . ($e['complemento'] ? ' - ' . $e['complemento'] : '')) ?></span>
                    <span><?= h($e['bairro'] . ' · ' . $e['cidade'] . '/' . $e['estado']) ?> · CEP <?= h($e['cep']) ?></span>
                  </span>
                  <a class="ck-editar" href="perfil.php?editar=<?= (int)$e['id'] ?>#endereco-form">Editar</a>
                </label>
              <?php endforeach; ?>
            </div>
            <a class="ck-link-btn" href="perfil.php#novo-endereco">+ Usar outro endereço (cadastrar novo)</a>
          <?php endif; ?>
        </section>

        <!-- 2) PAGAMENTO -->
        <section class="ck-card" aria-labelledby="t-pagamento">
          <h2 id="t-pagamento"><span class="ck-num">2</span> Forma de pagamento</h2>
          <?php $formas = formas_disponiveis(); ?>
          <div class="ck-opcoes">
            <?php $primeira = true; foreach ($formas as $valor => $rotulo): ?>
              <label class="ck-opcao">
                <input type="radio" name="forma_pagamento" value="<?= h($valor) ?>" <?= $primeira ? 'checked' : '' ?> required>
                <span class="ck-opcao__corpo"><strong><?= h($rotulo) ?></strong><span>Pague na próxima tela com o QR Code ou o Pix copia e cola.</span></span>
              </label>
            <?php $primeira = false; endforeach; ?>
            <?php foreach (FORMAS_FUTURAS as $f): ?>
              <label class="ck-opcao ck-opcao--off" title="Disponível em breve">
                <input type="radio" disabled>
                <span class="ck-opcao__corpo"><strong><?= h(FORMAS_PAGAMENTO[$f]) ?></strong></span>
                <span class="ck-breve">Em breve</span>
              </label>
            <?php endforeach; ?>
          </div>
          <?php if (!$formas): ?>
            <p class="ck-aviso ck-aviso--erro">O pagamento por Pix ainda não foi configurado pela loja. Tente novamente mais tarde.</p>
          <?php else: ?>
            <p class="ck-nota">Você conclui o pagamento na próxima tela, logo depois de confirmar o pedido.</p>
          <?php endif; ?>
        </section>

        <!-- 3) REVISÃO -->
        <section class="ck-card" aria-labelledby="t-itens">
          <h2 id="t-itens"><span class="ck-num">3</span> Revise seus itens</h2>
          <ul class="ck-itens">
            <?php foreach ($itens as $i): ?>
              <li>
                <img src="<?= h($i['url_imagem'] ? '../../' . ltrim($i['url_imagem'], './') : $imagem_padrao) ?>"
                     alt="" onerror="this.onerror=null; this.src='<?= h($imagem_padrao) ?>';">
                <div>
                  <strong><?= h($i['nome']) ?></strong>
                  <span>Tam. <?= h($i['tamanho']) ?><?= !empty($i['cor']) ? ' · ' . h($i['cor']) : '' ?> · <?= (int)$i['quantidade'] ?> × <?= brl($i['preco']) ?></span>
                </div>
                <b><?= brl($i['preco'] * $i['quantidade']) ?></b>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      </div>

      <aside class="ck-resumo">
        <span class="checkout__kicker">RESUMO</span>
        <h3>Seu pedido</h3>
        <div class="ck-linha"><span>Produtos</span><span><?= brl($subtotal) ?></span></div>
        <div class="ck-total"><span>Total</span><strong><?= brl($total) ?></strong></div>

        <button type="submit" class="ck-confirmar" <?= (!$enderecos || $problemas || !$formas || !$cpf_cliente) ? 'disabled' : '' ?>>
          Confirmar pedido →
        </button>
        <?php if (!$enderecos): ?><p class="ck-nota">Cadastre um endereço para poder confirmar.</p><?php endif; ?>
      </aside>
    </form>
  </main>

  <footer class="ck-rodape"><p>&copy; 2026 CICRO - Todos os direitos reservados</p></footer>

  <script>
    document.getElementById('form-checkout').addEventListener('submit', function () {
      var b = this.querySelector('.ck-confirmar');
      if (b) { b.disabled = true; b.textContent = 'Enviando…'; }
    });
  </script>
</body>
</html>
