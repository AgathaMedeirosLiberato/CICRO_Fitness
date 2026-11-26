<?php
session_start();
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../models/pagamento_funcoes.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$id_cliente = (int)$_SESSION['usuario_id'];
$id_pedido  = (int)($_GET['id'] ?? 0);

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

expirar_pedidos_pendentes($pdo);
$s = $pdo->prepare("SELECT id, status, valor_total, forma_pagamento, data_criacao FROM pedido WHERE id = ? AND fk_cliente = ?");
$s->execute([$id_pedido, $id_cliente]);
$pedido = $s->fetch();
if (!$pedido) {
    header('Location: catalogo.php');
    exit;
}
if ($pedido['status'] !== 'Pendente') {
    header('Location: pedido_confirmado.php?id=' . $id_pedido);
    exit;
}

$valor = (float)$pedido['valor_total'];
$prazo = strtotime($pedido['data_criacao']) + PEDIDO_PRAZO_PAGAMENTO_HORAS * 3600;

if (($pedido['forma_pagamento'] ?? '') !== 'pix') {
    $pdo->prepare("UPDATE pedido SET forma_pagamento = 'pix' WHERE id = ? AND status = 'Pendente'")->execute([$id_pedido]);
}

if (empty($_SESSION['csrf_pagamento'])) { $_SESSION['csrf_pagamento'] = bin2hex(random_bytes(16)); }

$aviso = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && pix_demonstracao()) {
    if (!hash_equals($_SESSION['csrf_pagamento'], (string)($_POST['csrf'] ?? ''))) {
        $aviso = 'Sessão expirada. Tente de novo.';
    } else {
        try {
            $pdo->beginTransaction();
            alterar_status_pedido($pdo, $id_pedido, 'Pago');
            $pdo->commit();
            header('Location: pedido_confirmado.php?id=' . $id_pedido);
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('Simular pagamento: ' . $e->getMessage());
            $aviso = 'Não foi possível simular o pagamento.';
        }
    }
}

$pix = null;
$erro_pix = '';
try {
    $pg = pagamento_do_pedido($pdo, $id_pedido);
    if (!$pg) {
        throw new RuntimeException('O Pix ainda não foi configurado pela loja.');
    }
    $codigo = $pg['codigo'] ?: pix_codigo($id_pedido, $valor);
    $pix = ['codigo' => $codigo, 'qr' => pix_qr($codigo), 'txid' => $pg['txid']];
} catch (RuntimeException $e) {
    $erro_pix = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Pagamento do pedido de <?= h($_SESSION['usuario_nome'] ?? 'cliente') ?></title>
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
    <div class="checkout__topo">
      <span class="checkout__kicker">CICRO / PAGAMENTO</span>
      <h1>Pedido de <?= h($_SESSION['usuario_nome'] ?? 'cliente') ?> recebido. Falta pagar!</h1>
      <p class="pg-sub">Total a pagar: <strong><?= brl($valor) ?></strong>. Pague até <strong><?= h(date('d/m/Y \à\s H:i', $prazo)) ?></strong>; depois disso o pedido é cancelado e as peças voltam ao estoque. Seu pedido só é separado depois da confirmação do pagamento.</p>
    </div>

    <div class="checkout__layout">
      <div class="checkout__passos">

        <section class="ck-card">
          <h2>Pague com Pix</h2>
          <?php if (pix_demonstracao()): ?>
            <p class="ck-aviso" style="background:#fff4d6;border:1px solid #f1dc9b;color:#6b4a00;border-radius:12px;padding:10px 14px;margin-bottom:12px"><strong>Pagamento de demonstração.</strong> Nenhum valor é cobrado de verdade: não pague este Pix no app do banco. Use o botão <strong>Simular pagamento</strong> abaixo.</p>
          <?php endif; ?>
          <?php if ($aviso): ?><p class="ck-aviso ck-aviso--erro"><?= h($aviso) ?></p><?php endif; ?>
          <?php if (!$pix): ?>
            <p class="ck-aviso ck-aviso--erro"><?= h($erro_pix) ?> Fale com a loja pelo <a href="suporte.php">Suporte</a> para concluir o pagamento.</p>
          <?php else: ?>
            <div class="pg-pix">
              <div class="pg-qr"><img src="<?= h($pix['qr']) ?>" alt="QR Code do Pix no valor de <?= h(brl($valor)) ?>"></div>
              <div class="pg-pix__texto">
                <ol class="pg-passos">
                  <li>Abra o app do seu banco e escolha <strong>Pix</strong>.</li>
                  <li>Leia o QR Code ou use <strong>Pix copia e cola</strong>.</li>
                  <li>Confira o valor de <strong><?= brl($valor) ?></strong> e confirme.</li>
                </ol>
                <label class="pg-label" for="pix-codigo">Pix copia e cola</label>
                <textarea id="pix-codigo" class="pg-codigo" readonly rows="4"><?= h($pix['codigo']) ?></textarea>
                <button type="button" class="ck-link-btn pg-copiar" data-alvo="pix-codigo">Copiar código</button>
              </div>
            </div>
            <?php if (pix_demonstracao()): ?>
              <form method="post" style="margin-top:14px">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_pagamento']) ?>">
                <button type="submit" class="ck-confirmar" style="max-width:320px">Simular pagamento</button>
              </form>
            <?php endif; ?>
            <p class="ck-nota">Identificador do seu Pix: <strong><?= h($pix['txid']) ?></strong>. Na loja de verdade, a equipe confere o recebimento e muda o status do pedido para <strong>Pago</strong>; acompanhe em "Meus pedidos". Se demorar, envie o comprovante pelo <a href="suporte.php">Suporte</a>.</p>
          <?php endif; ?>
        </section>

        <section class="ck-card">
          <h2>Outras formas de pagamento</h2>
          <p class="ck-nota" style="margin:0"><strong>Cartão de crédito</strong> e <strong>boleto</strong> estarão disponíveis em breve.</p>
        </section>

      </div>

      <aside class="ck-resumo">
        <span class="checkout__kicker">RESUMO</span>
        <h3>Pedido de <?= h($_SESSION['usuario_nome'] ?? 'cliente') ?></h3>
        <div class="ck-linha"><span>Forma</span><span>Pix</span></div>
        <div class="ck-linha"><span>Status</span><span>Aguardando pagamento</span></div>
        <div class="ck-total"><span>Total</span><strong><?= brl($valor) ?></strong></div>
        <a href="pedido_confirmado.php?id=<?= $id_pedido ?>" class="ck-secundario">Pagar depois e ver meu pedido</a>
      </aside>
    </div>
  </main>

  <footer class="ck-rodape"><p>&copy; 2026 CICRO - Todos os direitos reservados</p></footer>

  <script>
    document.querySelectorAll('.pg-copiar').forEach(function (b) {
      b.addEventListener('click', function () {
        var campo = document.getElementById(b.dataset.alvo); campo.select();
        var ok = function () { var t = b.textContent; b.textContent = 'Copiado ✓'; setTimeout(function () { b.textContent = t; }, 1800); };
        var antigo = function () { try { document.execCommand('copy'); ok(); } catch (e) {} };
        if (navigator.clipboard) { navigator.clipboard.writeText(campo.value).then(ok, antigo); } else { antigo(); }
      });
    });
  </script>
</body>
</html>
