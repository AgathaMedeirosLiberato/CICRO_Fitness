<?php
session_start();
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../models/pedido_funcoes.php';
require_once __DIR__ . '/../../config/config_pagamento.php';
require_once __DIR__ . '/../../models/avaliacao_funcoes.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
$id_cliente = (int)$_SESSION['usuario_id'];
if (!function_exists('h')) {
    function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

if (empty($_SESSION['csrf_cancelar'])) { $_SESSION['csrf_cancelar'] = bin2hex(random_bytes(16)); }
$flash = $_SESSION['flash_pedidos'] ?? null; unset($_SESSION['flash_pedidos']);
expirar_pedidos_pendentes($pdo);

$abas = [
    'todos'     => ['Todos',               null],
    'pagar'     => ['Aguardando pagamento', ['Pendente']],
    'preparo'   => ['Em preparo',           ['Pago', 'Em Preparação']],
    'caminho'   => ['A caminho',            ['Enviado']],
    'concluido' => ['Concluídos',           ['Entregue']],
    'cancelado' => ['Cancelados',           ['Cancelado']],
];
$aba = $_GET['aba'] ?? 'todos';
if (!isset($abas[$aba])) { $aba = 'todos'; }
$busca = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($busca) > 60) { $busca = mb_substr($busca, 0, 60); }

$st = $pdo->prepare("SELECT status, COUNT(*) AS n, SUM(valor_total) AS total FROM pedido WHERE fk_cliente = ? GROUP BY status");
$st->execute([$id_cliente]);
$por_status = [];
foreach ($st->fetchAll() as $l) { $por_status[$l['status']] = ['n' => (int)$l['n'], 'total' => (float)$l['total']]; }
$n_total = array_sum(array_column($por_status, 'n'));
$a_pagar_valor = $por_status['Pendente']['total'] ?? 0;
$gasto = 0;
foreach (['Pago', 'Em Preparação', 'Enviado', 'Entregue'] as $s) { $gasto += $por_status[$s]['total'] ?? 0; }

$onde = ["p.fk_cliente = ?"]; $args = [$id_cliente];
if ($abas[$aba][1] !== null) {
    $onde[] = "p.status IN (" . implode(',', array_fill(0, count($abas[$aba][1]), '?')) . ")";
    array_push($args, ...$abas[$aba][1]);
}
if ($busca !== '') {
    $num = ltrim($busca, '#');
    $onde[] = "(p.id = ? OR EXISTS (SELECT 1 FROM item_pedido ip WHERE ip.fk_pedido = p.id AND ip.nome_produto LIKE ?))";
    $args[] = ctype_digit($num) ? (int)$num : 0;
    $args[] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $busca) . '%';
}
$where = implode(' AND ', $onde);

$por_pagina = 8;
$c = $pdo->prepare("SELECT COUNT(*) FROM pedido p WHERE $where");
$c->execute($args);
$total_lista = (int)$c->fetchColumn();
$paginas = max(1, (int)ceil($total_lista / $por_pagina));
$pagina = min(max(1, (int)($_GET['p'] ?? 1)), $paginas);
$off = ($pagina - 1) * $por_pagina;

$q = $pdo->prepare("SELECT p.id, p.status, p.valor_total, p.data_criacao, pg.data_pagamento, p.forma_pagamento
                    FROM pedido p LEFT JOIN pagamento pg ON pg.fk_pedido = p.id WHERE $where ORDER BY p.id DESC LIMIT $por_pagina OFFSET $off");
$q->execute($args);
$pedidos = $q->fetchAll();

$itens = [];
if ($pedidos) {
    $ids = array_map(fn($p) => (int)$p['id'], $pedidos);
    $m = implode(',', array_fill(0, count($ids), '?'));
    $q = $pdo->prepare(
        "SELECT i.fk_pedido, i.nome_produto, i.tamanho, i.cor, i.quantidade, i.preco_unitario,
                (SELECT url_imagem FROM imagem_produto WHERE fk_produto = vp.fk_produto ORDER BY capa DESC, ordem ASC, id ASC LIMIT 1) AS url_imagem
         FROM item_pedido i LEFT JOIN variacao_produto vp ON vp.id = i.fk_variacao
         WHERE i.fk_pedido IN ($m) ORDER BY i.id ASC");
    $q->execute($ids);
    foreach ($q->fetchAll() as $l) { $itens[(int)$l['fk_pedido']][] = $l; }
}
$imagem_padrao = '../../images/uploads/CICRO.png';

$falta_avaliar = [];
foreach ($pedidos as $p) {
    if ($p['status'] === 'Entregue') {
        $lista = produtos_para_avaliar($pdo, (int)$p['id'], $id_cliente);
        $falta_avaliar[(int)$p['id']] = ['total' => count($lista), 'faltam' => count(array_filter($lista, fn($x) => empty($x['nota'])))];
    }
}

$rotulo = ['Pendente' => 'Aguardando pagamento', 'Pago' => 'Pago · aguardando preparo', 'Em Preparação' => 'Em preparação', 'Enviado' => 'A caminho',
           'Entregue' => 'Entregue', 'Cancelado' => 'Cancelado'];
$agora = time();
function url_aba(string $aba, string $busca = '', int $p = 1): string {
    $q = array_filter(['aba' => $aba === 'todos' ? '' : $aba, 'q' => $busca, 'p' => $p > 1 ? $p : ''], fn($v) => $v !== '');
    return 'meus_pedidos.php' . ($q ? '?' . http_build_query($q) : '');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Meus pedidos</title>
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/admin-vendas.css">
  <link rel="stylesheet" href="../../css/meus-pedidos.css">
</head>
<body class="mp-body">
  <header class="site-header">
    <a class="site-header__logo" href="index.php">CICRO</a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <li><a href="index.php">Início</a></li>
        <li><a href="catalogo.php">Catálogo</a></li>
        <li><a href="suporte.php">Suporte</a></li>
        <li><a href="meus_pedidos.php" class="is-active">Meus pedidos</a></li>
        <li><a href="perfil.php">Meu Perfil</a></li>
        <li><a href="carrinho.php" class="site-header__cta">Carrinho</a></li>
      </ul>
    </nav>
  </header>

  <main class="mp">
    <div class="mp__topo">
      <span class="mp__kicker">MINHA CONTA</span>
      <h1>Meus pedidos</h1>
    </div>

    <div class="mp-resumo">
      <div><b><?= $n_total ?></b><span>pedidos feitos</span></div>
      <div class="<?= ($por_status['Pendente']['n'] ?? 0) ? 'mp-resumo--alerta' : '' ?>"><b><?= (int)($por_status['Pendente']['n'] ?? 0) ?></b><span>aguardando pagamento<?= $a_pagar_valor > 0 ? ' · ' . brl($a_pagar_valor) : '' ?></span></div>
      <div><b><?= (int)(($por_status['Pago']['n'] ?? 0) + ($por_status['Em Preparação']['n'] ?? 0) + ($por_status['Enviado']['n'] ?? 0)) ?></b><span>em preparo ou a caminho</span></div>
      <div><b><?= brl($gasto) ?></b><span>já comprado (pagos)</span></div>
    </div>

    <nav class="mp-abas" aria-label="Situação do pedido">
      <?php foreach ($abas as $chave => [$nome, $sts]):
          $n = $sts === null ? $n_total : array_sum(array_map(fn($s) => $por_status[$s]['n'] ?? 0, $sts)); ?>
        <a href="<?= h(url_aba($chave, $busca)) ?>" class="<?= $chave === $aba ? 'ativa' : '' ?>"><?= h($nome) ?><?php if ($n > 0): ?> <em><?= $n ?></em><?php endif; ?></a>
      <?php endforeach; ?>
    </nav>

    <?php if ($flash): ?><div class="mp-aviso" style="margin:0 0 12px;padding:10px 14px;border-radius:8px;background:<?= $flash['ok'] ? '#e8f5e9' : '#fdecea' ?>"><?= h($flash['msg']) ?></div><?php endif; ?>
    <form class="mp-busca" method="get" action="meus_pedidos.php">
      <?php if ($aba !== 'todos'): ?><input type="hidden" name="aba" value="<?= h($aba) ?>"><?php endif; ?>
      <input type="search" name="q" value="<?= h($busca) ?>" maxlength="60" placeholder="Busque pelo nome do produto">
      <button type="submit">Buscar</button>
      <?php if ($busca !== ''): ?><a href="<?= h(url_aba($aba)) ?>">Limpar</a><?php endif; ?>
    </form>

    <?php if (!$pedidos): ?>
      <div class="mp-vazio">
        <h2><?= $n_total === 0 ? 'Você ainda não fez nenhum pedido' : 'Nenhum pedido encontrado aqui' ?></h2>
        <p><?= $n_total === 0 ? 'Quando você comprar, o acompanhamento aparece nesta página.' : 'Tente outra aba ou limpe a busca.' ?></p>
        <a class="mp-btn mp-btn--escuro" href="catalogo.php">Ver catálogo</a>
      </div>
    <?php endif; ?>

    <?php foreach ($pedidos as $p):
        $id = (int)$p['id']; $linhas = $itens[$id] ?? [];
        $pecas = array_sum(array_map(fn($l) => (int)$l['quantidade'], $linhas));
        $prazo = strtotime($p['data_criacao']) + PEDIDO_PRAZO_PAGAMENTO_HORAS * 3600;
        $falta = $prazo - $agora; ?>
      <article class="mp-pedido">
        <header class="mp-pedido__cab">
          <div>
            <strong>Pedido de <?= h($_SESSION['usuario_nome'] ?? 'cliente') ?></strong>
            <span>feito em <?= h(date('d/m/Y H:i', strtotime($p['data_criacao']))) ?></span>
          </div>
          <span class="mp-selo <?= h(classe_status($p['status'])) ?>"><?= h($rotulo[$p['status']] ?? $p['status']) ?></span>
        </header>

        <ul class="mp-itens">
          <?php foreach ($linhas as $l): ?>
            <li>
              <img src="<?= h(!empty($l['url_imagem']) ? '../../' . ltrim($l['url_imagem'], './') : $imagem_padrao) ?>" alt="" loading="lazy">
              <div>
                <strong><?= h($l['nome_produto']) ?></strong>
                <span>Tamanho <?= h($l['tamanho']) ?><?= !in_array($l['cor'], ['', 'Padrão'], true) ? ' · ' . h($l['cor']) : '' ?> · x<?= (int)$l['quantidade'] ?></span>
              </div>
              <b><?= brl($l['preco_unitario'] * $l['quantidade']) ?></b>
            </li>
          <?php endforeach; ?>
        </ul>

        <footer class="mp-pedido__rodape">
          <div class="mp-info">
            <?php if ($p['status'] === 'Pendente'): ?>
              <?php if ($falta > 0): ?>
                <span class="mp-aviso">Pague até <?= h(date('d/m H:i', $prazo)) ?> (faltam <?= $falta >= 3600 ? floor($falta / 3600) . ' h ' . floor(($falta % 3600) / 60) . ' min' : max(1, (int)floor($falta / 60)) . ' min' ?>) ou o pedido será cancelado.</span>
              <?php else: ?>
                <span class="mp-aviso">O prazo para pagar terminou; o pedido será cancelado em instantes.</span>
              <?php endif; ?>
            <?php elseif ($p['status'] === 'Cancelado'): ?>
              <span>Pedido cancelado. As peças voltaram ao estoque.</span>
            <?php elseif ($p['data_pagamento']): ?>
              <span>Pagamento confirmado em <?= h(date('d/m/Y H:i', strtotime($p['data_pagamento']))) ?>.</span>
            <?php endif; ?>
            <span><?= $pecas ?> <?= $pecas === 1 ? 'item' : 'itens' ?> · <?= h(FORMAS_PAGAMENTO[$p['forma_pagamento'] ?? ''] ?? 'Pix') ?></span>
          </div>
          <div class="mp-total">
            <span>Total do pedido</span>
            <strong><?= brl($p['valor_total']) ?></strong>
          </div>
          <div class="mp-acoes">
            <?php if ($p['status'] === 'Pendente' && $falta > 0): ?>
              <a class="mp-btn mp-btn--escuro" href="pagamento.php?id=<?= $id ?>">Pagar agora</a>
            <?php elseif ($p['status'] === 'Entregue'): $av = $falta_avaliar[$id] ?? ['total' => 0, 'faltam' => 0]; ?>
              <?php if ($av['total'] > 0): ?>
                <a class="mp-btn mp-btn--escuro" href="avaliar.php?pedido=<?= $id ?>"><?= $av['faltam'] > 0 ? 'Avaliar' : 'Ver minha avaliação' ?></a>
              <?php endif; ?>
              <a class="mp-btn" href="catalogo.php">Comprar de novo</a>
            <?php elseif ($p['status'] === 'Cancelado'): ?>
              <a class="mp-btn mp-btn--escuro" href="catalogo.php">Comprar de novo</a>
            <?php endif; ?>
            <?php if (in_array($p['status'], ['Pendente', 'Pago'], true)): ?>
              <form method="post" action="../../controllers/cancelar_pedido.php" style="display:inline"
                    onsubmit="return confirm(<?= h(json_encode('Cancelar este pedido? As peças voltam ao estoque e isso não pode ser desfeito.' . ($p['status'] === 'Pago' ? ' Como ele já foi pago, o estorno do valor será combinado com a loja.' : ''), JSON_UNESCAPED_UNICODE)) ?>);">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf_cancelar']) ?>">
                <input type="hidden" name="pedido" value="<?= $id ?>">
                <button type="submit" class="mp-btn">Cancelar pedido</button>
              </form>
            <?php endif; ?>
            <a class="mp-btn" href="pedido_confirmado.php?id=<?= $id ?>">Ver detalhes</a>
          </div>
        </footer>
      </article>
    <?php endforeach; ?>

    <?php if ($paginas > 1): ?>
      <nav class="mp-paginas" aria-label="Páginas">
        <?php for ($i = 1; $i <= $paginas; $i++): ?>
          <a href="<?= h(url_aba($aba, $busca, $i)) ?>" class="<?= $i === $pagina ? 'ativa' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>
  </main>

  <footer class="mp-rodape"><p>&copy; 2026 CICRO - Todos os direitos reservados</p></footer>
</body>
</html>
