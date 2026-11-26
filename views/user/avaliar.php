<?php
session_start();
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../models/avaliacao_funcoes.php';

if (!isset($_SESSION['usuario_id'])) { header('Location: login.php'); exit; }
$id_cliente = (int)$_SESSION['usuario_id'];
$id_pedido  = (int)($_GET['pedido'] ?? $_POST['pedido'] ?? 0);
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf_avaliar'])) { $_SESSION['csrf_avaliar'] = bin2hex(random_bytes(16)); }

$msg = $_SESSION['flash_avaliar'] ?? ''; unset($_SESSION['flash_avaliar']);
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_avaliar'], (string)($_POST['csrf'] ?? ''))) {
        $erro = 'Sessão expirada. Tente de novo.';
    } else {
        try {
            salvar_avaliacao($pdo, $id_pedido, $id_cliente, (int)($_POST['produto'] ?? 0), (int)($_POST['nota'] ?? 0), (string)($_POST['comentario'] ?? ''));
            $_SESSION['flash_avaliar'] = 'Avaliação salva. Obrigado!';
            header('Location: avaliar.php?pedido=' . $id_pedido); exit;
        } catch (RuntimeException $e) { $erro = $e->getMessage(); }
        catch (Throwable $e) { error_log('avaliar: ' . $e->getMessage()); $erro = 'Não foi possível salvar. Tente novamente.'; }
    }
}
$produtos = produtos_para_avaliar($pdo, $id_pedido, $id_cliente);
$imagem_padrao = '../../images/uploads/CICRO.png';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Avaliar pedido de <?= h($_SESSION['usuario_nome'] ?? 'cliente') ?></title>
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/meus-pedidos.css">
  <link rel="stylesheet" href="../../css/avaliar.css">
</head>
<body class="mp-body">
  <header class="site-header"><a class="site-header__logo" href="index.php">CICRO</a>
    <nav class="site-header__nav"><ul>
      <li><a href="catalogo.php">Catálogo</a></li><li><a href="meus_pedidos.php" class="is-active">Meus pedidos</a></li>
      <li><a href="perfil.php">Meu Perfil</a></li><li><a href="carrinho.php" class="site-header__cta">Carrinho</a></li></ul></nav></header>
  <main class="mp av-pagina">
    <div class="mp__topo av-topo-pagina"><span class="mp__kicker">PEDIDO DE <?= h(mb_strtoupper($_SESSION['usuario_nome'] ?? 'cliente')) ?></span><h1>Avaliar produtos</h1>
      <p>Sua opinião ajuda outras pessoas a escolher e nos ajuda a melhorar.</p>
      <a class="av-voltar" href="meus_pedidos.php?aba=concluido">← Voltar para meus pedidos</a></div>
    <?php if ($msg): ?><p class="av-ok" role="status"><?= h($msg) ?></p><?php endif; ?>
    <?php if ($erro): ?><p class="av-erro" role="alert"><?= h($erro) ?></p><?php endif; ?>

    <?php if (!$produtos): ?>
      <div class="mp-vazio"><h2>Este pedido ainda não pode ser avaliado</h2><p>Você pode avaliar os produtos depois que o pedido for entregue.</p>
        <a class="mp-btn mp-btn--escuro" href="meus_pedidos.php">Meus pedidos</a></div>
    <?php endif; ?>

    <?php foreach ($produtos as $p): $nota = (int)($p['nota'] ?? 0); $pid = (int)$p['fk_produto']; ?>
      <form method="post" class="av-card">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_avaliar']) ?>">
        <input type="hidden" name="pedido" value="<?= (int)$id_pedido ?>">
        <input type="hidden" name="produto" value="<?= $pid ?>">
        <div class="av-topo">
          <img src="<?= h(!empty($p['url_imagem']) ? '../../' . ltrim($p['url_imagem'], './') : $imagem_padrao) ?>" alt="">
          <div>
            <?php if ($nota): ?><span class="av-selo">Avaliado</span><?php endif; ?>
            <strong><?= h($p['nome']) ?></strong>
            <span><?= $nota ? 'Você já avaliou. Pode alterar quando quiser.' : 'Conte como foi a sua experiência.' ?></span>
          </div>
        </div>
        <div class="av-corpo">
          <fieldset class="av-estrelas">
            <legend>Sua nota</legend>
            <div class="av-linha">
              <div class="av-stars">
                <?php for ($n = 5; $n >= 1; $n--): ?>
                  <input type="radio" id="n<?= $pid ?>_<?= $n ?>" name="nota" value="<?= $n ?>" <?= $nota === $n ? 'checked' : '' ?> required>
                  <label for="n<?= $pid ?>_<?= $n ?>" title="<?= $n ?> estrela<?= $n > 1 ? 's' : '' ?>">★</label>
                <?php endfor; ?>
              </div>
              <span class="av-nota-txt" aria-live="polite"></span>
            </div>
          </fieldset>
          <div class="av-campo">
            <textarea name="comentario" rows="4" maxlength="<?= AVALIACAO_COMENTARIO_MAX ?>" placeholder="Comentário (opcional): tamanho, caimento, qualidade..."><?= h($p['comentario'] ?? '') ?></textarea>
            <span class="av-contador" data-max="<?= AVALIACAO_COMENTARIO_MAX ?>"></span>
          </div>
          <button type="submit" class="mp-btn mp-btn--escuro"><?= $nota ? 'Atualizar avaliação' : 'Enviar avaliação' ?></button>
        </div>
      </form>
    <?php endforeach; ?>
  </main>
  <script>
    (function () {
      var rotulos = {1: 'Ruim', 2: 'Regular', 3: 'Bom', 4: 'Muito bom', 5: 'Excelente'};
      document.querySelectorAll('.av-card').forEach(function (card) {
        var txt = card.querySelector('.av-nota-txt');
        var ta = card.querySelector('textarea');
        var cont = card.querySelector('.av-contador');
        function nota() { var c = card.querySelector('input[name="nota"]:checked'); txt.textContent = c ? rotulos[c.value] : ''; }
        function conta() { cont.textContent = ta.value.length + '/' + cont.dataset.max; }
        card.querySelectorAll('input[name="nota"]').forEach(function (i) { i.addEventListener('change', nota); });
        ta.addEventListener('input', conta);
        nota(); conta();
      });
    })();
  </script>
</body>
</html>
