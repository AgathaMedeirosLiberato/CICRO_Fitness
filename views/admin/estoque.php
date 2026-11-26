<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../controllers/auth_admin.php';
require_once __DIR__ . '/../../models/estoque_funcoes.php';
$MOTIVOS = [
    'entrada' => ['reposição', 'devolução', 'ajuste'],
    'saida'   => ['perda/avaria', 'ajuste', 'outro'],
];

function img_url(?string $url): string
{
    return $url ? '../../' . ltrim($url, './') : '../../images/uploads/CICRO.png';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $id_variacao = (int)($_POST['variacao'] ?? 0);
    $tipo        = $_POST['tipo'] ?? '';
    $qtd         = (int)($_POST['quantidade'] ?? 0);
    $motivo      = $_POST['motivo'] ?? '';

    $qs      = $_SERVER['QUERY_STRING'] ?? '';
    $destino = 'estoque.php' . ($qs !== '' ? '?' . $qs : '');

    try {
        if (!isset($MOTIVOS[$tipo]) || !in_array($motivo, $MOTIVOS[$tipo], true)) {
            throw new RuntimeException('Escolha o tipo e o motivo da movimentação.');
        }
        $novo = movimentar_estoque($pdo, $id_variacao, $tipo, $qtd, $motivo);
        $_SESSION['flash_admin'] = ['ok' => true, 'msg' => ($tipo === 'entrada' ? 'Entrada' : 'Saída') . " registrada. Novo saldo: $novo."];
    } catch (RuntimeException | InvalidArgumentException $e) {
        $_SESSION['flash_admin'] = ['ok' => false, 'msg' => $e->getMessage()];
    } catch (PDOException $e) {
        error_log('Erro no estoque: ' . $e->getMessage());
        $_SESSION['flash_admin'] = ['ok' => false, 'msg' => 'Não foi possível registrar a movimentação.'];
    }
    header('Location: ' . $destino);
    exit;
}

$busca    = trim($_GET['busca'] ?? '');
$situacao = $_GET['situacao'] ?? 'todos';
$aba      = ($_GET['aba'] ?? '') === 'historico' ? 'historico' : 'saldo';

$resumo = $pdo->query(
    "SELECT COALESCE(SUM(v.quantidade_estoque), 0)                                         AS pecas,
            COALESCE(SUM(v.quantidade_estoque * (p.preco_base + v.preco_adicional)), 0)    AS valor,
            COALESCE(SUM(v.quantidade_estoque = 0), 0)                                     AS zeradas,
            COALESCE(SUM(v.quantidade_estoque BETWEEN 1 AND " . (int)ESTOQUE_BAIXO . "), 0) AS baixas
     FROM variacao_produto v
     JOIN produto p ON p.id = v.fk_produto
     WHERE p.status = 1"
)->fetch();

$sql = "SELECT v.id, v.sku, v.tamanho, v.cor, v.quantidade_estoque AS estoque,
               (p.preco_base + v.preco_adicional) AS preco,
               p.id AS produto_id, p.nome, p.status,
               (SELECT url_imagem FROM imagem_produto
                 WHERE fk_produto = p.id ORDER BY capa DESC, ordem ASC, id ASC LIMIT 1) AS imagem
        FROM variacao_produto v
        JOIN produto p ON p.id = v.fk_produto
        WHERE 1 = 1";
$params = [];
if ($busca !== '') {
    $sql .= " AND (p.nome LIKE ? OR v.sku LIKE ?)";
    $termo = '%' . addcslashes($busca, '%_\\') . '%';
    array_push($params, $termo, $termo);
}
if ($situacao === 'baixo')  { $sql .= " AND v.quantidade_estoque BETWEEN 1 AND " . (int)ESTOQUE_BAIXO; }
if ($situacao === 'zerado') { $sql .= " AND v.quantidade_estoque = 0"; }
$sql .= " ORDER BY p.nome ASC, FIELD(v.tamanho, 'PP','P','M','G','GG','XG'), v.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$variacoes = $stmt->fetchAll();

$historico = $pdo->query(
    "SELECT m.id, m.tipo, m.quantidade, m.motivo, m.fk_pedido, m.data_movimento,
            v.tamanho, p.nome, uc.nome AS cliente_nome
     FROM movimentacao_estoque m
     JOIN variacao_produto v ON v.id = m.fk_variacao
     JOIN produto p ON p.id = v.fk_produto
     LEFT JOIN pedido ped ON ped.id = m.fk_pedido
     LEFT JOIN usuario uc ON uc.id = ped.fk_cliente
     ORDER BY m.id DESC
     LIMIT 50"
)->fetchAll();

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);

function situacao_badge(int $q): array
{
    if ($q === 0)              { return ['est-zero',  'Esgotado']; }
    if ($q <= ESTOQUE_BAIXO)   { return ['est-baixo', 'Estoque baixo']; }
    return ['est-ok', 'Disponível'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Estoque</title>
  <link rel="stylesheet" href="../../css/style10.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/notificacoes-admin.css">
</head>
<body>

  <header class="site-header">
    <a class="site-header__logo" href="inicio.php">CICRO</a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <li><a href="inicio.php">Painel</a></li>
        <li><a href="../../controllers/logout.php" class="site-header__cta">Sair</a></li>
      </ul>
    </nav>
  </header>

  <main class="dashboard-container">
    <div class="admin-wrapper wide-wrapper">

      <div class="profile-header">
        <div class="profile-header-info">
          <h1>Estoque</h1>
          <p>Saldo por tamanho, entradas e saídas. Vendas baixam o estoque automaticamente.</p>
        </div>
      </div>

      <?php if ($flash): ?>
        <div class="aviso <?= $flash['ok'] ? 'aviso-ok' : 'aviso-erro' ?>" role="status"><?= h($flash['msg']) ?></div>
      <?php endif; ?>

      <!-- Resumo -->
      <section class="est-resumo" aria-label="Resumo do estoque">
        <div class="est-card">
          <span class="est-card__valor"><?= (int)$resumo['pecas'] ?></span>
          <span class="est-card__rotulo">peças em estoque</span>
        </div>
        <div class="est-card">
          <span class="est-card__valor">R$ <?= number_format((float)$resumo['valor'], 2, ',', '.') ?></span>
          <span class="est-card__rotulo">valor em estoque (preço de venda)</span>
        </div>
        <a class="est-card est-card--alerta" href="?situacao=baixo">
          <span class="est-card__valor"><?= (int)$resumo['baixas'] ?></span>
          <span class="est-card__rotulo">com estoque baixo (até <?= (int)ESTOQUE_BAIXO ?>)</span>
        </a>
        <a class="est-card est-card--erro" href="?situacao=zerado">
          <span class="est-card__valor"><?= (int)$resumo['zeradas'] ?></span>
          <span class="est-card__rotulo">esgotadas</span>
        </a>
      </section>

      <!-- Abas -->
      <div class="est-abas" role="tablist">
        <a href="estoque.php" class="<?= $aba === 'saldo' ? 'ativa' : '' ?>" role="tab">Saldo atual</a>
        <a href="?aba=historico" class="<?= $aba === 'historico' ? 'ativa' : '' ?>" role="tab">Histórico de movimentações</a>
      </div>

      <?php if ($aba === 'saldo'): ?>

      <form method="GET" class="est-filtros">
        <input type="text" name="busca" value="<?= h($busca) ?>" placeholder="Buscar por produto ou SKU..." aria-label="Buscar">
        <select name="situacao" aria-label="Situação">
          <option value="todos"  <?= $situacao === 'todos'  ? 'selected' : '' ?>>Todas</option>
          <option value="baixo"  <?= $situacao === 'baixo'  ? 'selected' : '' ?>>Estoque baixo</option>
          <option value="zerado" <?= $situacao === 'zerado' ? 'selected' : '' ?>>Esgotadas</option>
        </select>
        <button type="submit" class="est-btn est-btn--escuro">Filtrar</button>
      </form>

      <section class="form-card table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Peça</th>
                <th>Tamanho</th>
                <th>Preço</th>
                <th>Estoque</th>
                <th>Registrar movimentação</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!$variacoes): ?>
              <tr><td colspan="5" class="empty-table"><p>Nenhuma peça encontrada.</p></td></tr>
            <?php endif; ?>

            <?php foreach ($variacoes as $v): ?>
              <?php [$classe, $texto] = situacao_badge((int)$v['estoque']); ?>
              <tr class="<?= $v['status'] ? '' : 'est-inativo' ?>">
                <td>
                  <div class="user-cell">
                    <img class="est-mini" src="<?= h(img_url($v['imagem'])) ?>" alt=""
                         onerror="this.onerror=null; this.src='../../images/uploads/CICRO.png';">
                    <div class="user-info">
                      <span class="user-name"><?= h($v['nome']) ?><?= $v['status'] ? '' : ' (desativado)' ?></span>
                      <span class="user-id">SKU: <?= h($v['sku']) ?></span>
                    </div>
                  </div>
                </td>
                <td><strong><?= h($v['tamanho']) ?></strong><?= $v['cor'] !== 'Padrão' ? ' · ' . h($v['cor']) : '' ?></td>
                <td>R$ <?= number_format((float)$v['preco'], 2, ',', '.') ?></td>
                <td>
                  <span class="est-qtd"><?= (int)$v['estoque'] ?></span>
                  <span class="est-badge <?= $classe ?>"><?= $texto ?></span>
                </td>
                <td>
                  <form method="POST" class="est-mov">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="variacao" value="<?= (int)$v['id'] ?>">
                    <select name="tipo" class="est-tipo" aria-label="Tipo" required>
                      <option value="entrada">Entrada (+)</option>
                      <option value="saida">Saída (−)</option>
                    </select>
                    <input type="number" name="quantidade" min="1" max="9999" value="1" aria-label="Quantidade" required>
                    <select name="motivo" class="est-motivo" aria-label="Motivo" required>
                      <option value="reposição" data-tipo="entrada">Reposição</option>
                      <option value="devolução" data-tipo="entrada">Devolução</option>
                      <option value="ajuste"    data-tipo="entrada">Ajuste</option>
                      <option value="perda/avaria" data-tipo="saida">Perda / avaria</option>
                      <option value="ajuste"    data-tipo="saida">Ajuste</option>
                      <option value="outro"     data-tipo="saida">Outro</option>
                    </select>
                    <button type="submit" class="est-btn est-btn--escuro">Salvar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <?php else: ?>

      <section class="form-card table-card">
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr><th>Data</th><th>Peça</th><th>Tam.</th><th>Movimento</th><th>Motivo</th><th>Pedido</th></tr>
            </thead>
            <tbody>
            <?php if (!$historico): ?>
              <tr><td colspan="6" class="empty-table"><p>Ainda não há movimentações.</p></td></tr>
            <?php endif; ?>
            <?php foreach ($historico as $m): ?>
              <tr>
                <td class="date-cell"><?= h(date('d/m/Y H:i', strtotime($m['data_movimento']))) ?></td>
                <td><?= h($m['nome']) ?></td>
                <td><?= h($m['tamanho']) ?></td>
                <td>
                  <span class="est-mov-<?= $m['tipo'] === 'entrada' ? 'entrada' : 'saida' ?>">
                    <?= $m['tipo'] === 'entrada' ? '+' : '−' ?><?= (int)$m['quantidade'] ?>
                  </span>
                </td>
                <td><?= h($m['motivo'] ?? '—') ?></td>
                <td><?= $m['fk_pedido'] ? 'Pedido de ' . h($m['cliente_nome'] ?? 'cliente') : '—' ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <?php endif; ?>

    </div>
  </main>
  <script>
    document.querySelectorAll('.est-mov').forEach(function (form) {
      var tipo = form.querySelector('.est-tipo');
      var motivo = form.querySelector('.est-motivo');
      function filtrar() {
        var primeiro = null;
        Array.from(motivo.options).forEach(function (op) {
          var ok = op.dataset.tipo === tipo.value;
          op.hidden = !ok;
          op.disabled = !ok;
          if (ok && !primeiro) primeiro = op;
        });
        if (motivo.selectedOptions[0] && motivo.selectedOptions[0].disabled && primeiro) primeiro.selected = true;
      }
      tipo.addEventListener('change', filtrar);
      filtrar();
    });
  </script>
  <script>window.CICRO_NOTIF = {pedidos: false};</script>
  <script src="../../js/notificacoes-admin.js"></script>
  <script src="../../js/confirmar-saida.js"></script>
</body>
</html>