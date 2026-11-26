<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../controllers/auth_admin.php';
require_once __DIR__ . '/../../models/pedido_funcoes.php';
require_once __DIR__ . '/../../models/estoque_funcoes.php';
require_once __DIR__ . '/../../models/pagamento_funcoes.php';
require_once __DIR__ . '/../../models/cpf_cripto.php';

expirar_pedidos_pendentes($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $destino = 'vendas.php' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
    try {
        if (!in_array((string)($_POST['status'] ?? ''), STATUS_ADMIN, true)) {
            throw new RuntimeException('O cancelamento é feito pelo cliente, em "Meus pedidos". Aqui só dá para confirmar pagamento, enviar e marcar como entregue.');
        }
        alterar_status_pedido($pdo, (int)($_POST['pedido'] ?? 0), (string)($_POST['status'] ?? ''));
        $_SESSION['flash_admin'] = ['ok' => true, 'msg' => 'Pedido de ' . nome_do_cliente_do_pedido($pdo, (int)$_POST['pedido']) . ' atualizado para "' . (string)$_POST['status'] . '".'];
    } catch (RuntimeException $e) {
        $_SESSION['flash_admin'] = ['ok' => false, 'msg' => $e->getMessage()];
    } catch (PDOException $e) {
        error_log('Erro ao mudar status do pedido: ' . $e->getMessage());
        $_SESSION['flash_admin'] = ['ok' => false, 'msg' => 'Não foi possível atualizar o pedido.'];
    }
    header('Location: ' . $destino);
    exit;
}

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);

function form_status(array $p): string
{
    $o = '';
    $lista = $p['status'] === 'Cancelado' ? STATUS_PEDIDO : STATUS_ADMIN;
    foreach ($lista as $s) {
        $o .= '<option value="' . h($s) . '"' . ($s === $p['status'] ? ' selected' : '') . '>' . h($s) . '</option>';
    }
    $dis = $p['status'] === 'Cancelado' ? ' disabled title="Pedido cancelado não pode ser alterado"' : '';
    $nome = (string)($p['nome'] ?? $p['cliente_nome'] ?? '');
    $html = '<div class="vd-acoes-status">'
          . '<form method="POST" class="vd-form-status">' . csrf_campo()
          . '<input type="hidden" name="pedido" value="' . (int)$p['id'] . '">'
          . '<select name="status" aria-label="Status do pedido de ' . h($nome) . '"' . $dis . '>' . $o . '</select>'
          . '<button type="submit" class="vd-btn vd-btn--pequeno"' . $dis . '>Salvar</button>'
          . '</form>';
    if ($p['status'] === 'Pago') {
        $html .= '<form method="POST" class="vd-form-status">' . csrf_campo()
               . '<input type="hidden" name="pedido" value="' . (int)$p['id'] . '">'
               . '<input type="hidden" name="status" value="Em Preparação">'
               . '<button type="submit" class="vd-btn vd-btn--pequeno">Em Preparação</button>'
               . '</form>';
    }
    return $html . '</div>';
}

$id_detalhe = (int)($_GET['id'] ?? 0);
if ($id_detalhe > 0) {
    $st = $pdo->prepare(
        "SELECT p.*, u.nome AS cliente_nome, u.email AS cliente_email, u.telefone AS cliente_telefone, c.cpf AS cliente_cpf,
                pg.status AS pag_status, pg.txid AS pag_txid, pg.valor AS pag_valor, pg.simulado AS pag_simulado, pg.data_pagamento AS data_pagamento
         FROM pedido p JOIN usuario u ON u.id = p.fk_cliente LEFT JOIN cliente c ON c.id_usuario = u.id
         LEFT JOIN pagamento pg ON pg.fk_pedido = p.id WHERE p.id = ?"
    );
    $st->execute([$id_detalhe]);
    $pedido = $st->fetch();
    if (!$pedido) {
        $_SESSION['flash_admin'] = ['ok' => false, 'msg' => 'Pedido não encontrado.'];
        header('Location: vendas.php');
        exit;
    }
    $itens = itens_dos_pedidos($pdo, [$id_detalhe])[$id_detalhe] ?? [];
}

if ($id_detalhe === 0) {
    $busca  = trim($_GET['busca'] ?? '');
    $status = $_GET['status'] ?? '';
    $de     = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['de']  ?? '') ? $_GET['de']  : '';
    $ate    = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['ate'] ?? '') ? $_GET['ate'] : '';
    $pagina = max(1, (int)($_GET['p'] ?? 1));
    $por_pagina = 30;

    $where = ['1 = 1'];
    $par   = [];
    if ($busca !== '') {
        $termo = '%' . addcslashes($busca, '%_\\') . '%';
        $where[] = '(u.nome LIKE ? OR u.email LIKE ? OR p.id = ?)';
        array_push($par, $termo, $termo, ctype_digit($busca) ? (int)$busca : 0);
    }
    if (in_array($status, STATUS_PEDIDO, true)) {
        $where[] = 'p.status = ?';
        $par[] = $status;
    }
    if ($de !== '')  { $where[] = 'p.data_criacao >= ?'; $par[] = $de . ' 00:00:00'; }
    if ($ate !== '') { $where[] = 'p.data_criacao < ?';  $par[] = date('Y-m-d', strtotime($ate . ' +1 day')) . ' 00:00:00'; }
    $sql_where = implode(' AND ', $where);

    $st = $pdo->prepare("SELECT COUNT(*) AS n,
                                COALESCE(SUM(CASE WHEN " . SQL_VENDA_VALIDA . " THEN p.valor_total END), 0) AS faturado,
                                COALESCE(SUM(CASE WHEN p.status = 'Pendente' THEN 1 ELSE 0 END), 0) AS pendentes
                         FROM pedido p JOIN usuario u ON u.id = p.fk_cliente WHERE $sql_where");
    $st->execute($par);
    $tot = $st->fetch();
    $paginas = max(1, (int)ceil($tot['n'] / $por_pagina));
    $pagina  = min($pagina, $paginas);

    $st = $pdo->prepare(
        "SELECT p.id, p.data_criacao, p.status, p.valor_total, p.forma_pagamento,
                u.id AS uid, u.nome, u.email,
                (SELECT COALESCE(SUM(quantidade), 0) FROM item_pedido WHERE fk_pedido = p.id) AS pecas
         FROM pedido p JOIN usuario u ON u.id = p.fk_cliente
         WHERE $sql_where
         ORDER BY p.id DESC
         LIMIT " . (int)$por_pagina . " OFFSET " . (int)(($pagina - 1) * $por_pagina)
    );
    $st->execute($par);
    $pedidos = $st->fetchAll();

    $qs_filtros = http_build_query(array_filter(['busca' => $busca, 'status' => $status, 'de' => $de, 'ate' => $ate]));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Vendas</title>
  <link rel="stylesheet" href="../../css/style10.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/notificacoes-admin.css">
  <link rel="stylesheet" href="../../css/admin-vendas.css">
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

      <?php if ($flash): ?>
        <div class="aviso vd-aviso-topo <?= $flash['ok'] ? 'aviso-ok' : 'aviso-erro' ?>" role="status"><?= h($flash['msg']) ?></div>
      <?php endif; ?>

<?php if ($id_detalhe > 0): ?>

      <div class="profile-header">
        <div class="profile-header-info">
          <h1>Pedido de <?= h($pedido['cliente_nome']) ?> <span class="vd-status <?= h(classe_status($pedido['status'])) ?>"><?= h($pedido['status']) ?></span></h1>
          <p>Feito em <?= h(date('d/m/Y \à\s H:i', strtotime($pedido['data_criacao']))) ?></p>
        </div>
        <div class="vd-barra-acoes no-print">
          <a href="vendas.php" class="vd-btn vd-btn--claro">← Voltar às vendas</a>
          <button type="button" class="vd-btn vd-btn--claro" onclick="window.print()">🖨 Imprimir / PDF</button>
        </div>
      </div>

      <section class="vd-detalhe">
        <div class="vd-bloco">
          <h3>Cliente</h3>
          <p><strong><a class="vd-link" href="compras-usuario.php?id=<?= (int)$pedido['fk_cliente'] ?>"><?= h($pedido['cliente_nome']) ?></a></strong></p>
          <p><?= h($pedido['cliente_email']) ?></p>
          <p><?= h($pedido['cliente_telefone'] ?: 'Telefone não informado') ?></p>
          <p>CPF: <?= h(cpf_exibir($pedido['cliente_cpf']) ?: 'não informado') ?></p>
        </div>
        <div class="vd-bloco">
          <h3>Entrega</h3>
          <p><?= h($pedido['endereco_entrega'] ?: 'Endereço não registrado (pedido anterior à escolha de endereço).') ?></p>
        </div>
        <div class="vd-bloco">
          <h3>Pagamento</h3>
          <p><strong><?= h(FORMAS_PAGAMENTO[$pedido['forma_pagamento'] ?? ''] ?? 'Não informado') ?></strong></p>
          <p><strong>Total: <?= brl($pedido['valor_total']) ?></strong></p>
        </div>
        <div class="vd-bloco no-print">
          <h3>Alterar status</h3>
          <?= form_status($pedido) ?>
          <p class="vd-muted" style="margin-top:8px">O cancelamento é feito pelo cliente (Meus pedidos) ou automaticamente após o prazo de pagamento.</p>
        </div>
      </section>

      <section class="vd-secao">
        <h2>Pagamento (Pix)</h2>
        <table class="vd-mini">
          <tbody>
            <tr><th>Situação</th><td><?= h(ROTULO_PAGAMENTO[$pedido['pag_status'] ?? ''] ?? 'Sem registro de pagamento') ?><?= !empty($pedido['pag_simulado']) ? ' <span class="vd-muted">(demonstração)</span>' : '' ?></td></tr>
            <tr><th>Forma</th><td><?= h(FORMAS_PAGAMENTO[$pedido['forma_pagamento'] ?? ''] ?? 'Não informado') ?></td></tr>
            <tr><th>Identificador no Pix (txid)</th><td><?= h($pedido['pag_txid'] ?: pix_txid((int)$pedido['id'])) ?> <span class="vd-muted">— aparece no comprovante do cliente</span></td></tr>
            <tr><th>Valor esperado</th><td><?= brl($pedido['pag_valor'] ?? $pedido['valor_total']) ?></td></tr>
            <tr><th>Pago em</th><td><?= $pedido['data_pagamento'] ? h(date('d/m/Y H:i', strtotime($pedido['data_pagamento']))) : '—' ?></td></tr>
          </tbody>
        </table>
        <?php if ($pedido['status'] === 'Pendente'): ?>
          <p class="vd-muted" style="margin-top:8px">Conferiu no extrato do banco um Pix de <strong><?= brl($pedido['valor_total']) ?></strong> com este txid? Troque o status para <strong>Pago</strong> acima.</p>
        <?php endif; ?>
      </section>

      <section class="form-card table-card">
        <div class="table-responsive">
          <table class="data-table vd-tabela">
            <thead><tr><th>Produto</th><th>Tamanho</th><th>Cor</th><th class="vd-num">Qtd.</th><th class="vd-num">Unitário</th><th class="vd-num">Subtotal</th></tr></thead>
            <tbody>
              <?php foreach ($itens as $i): ?>
                <tr>
                  <td><?= h($i['nome_produto']) ?></td><td><?= h($i['tamanho']) ?></td><td><?= h($i['cor']) ?></td>
                  <td class="vd-num"><?= (int)$i['quantidade'] ?></td>
                  <td class="vd-num"><?= brl($i['preco_unitario']) ?></td>
                  <td class="vd-num"><?= brl($i['preco_unitario'] * $i['quantidade']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

<?php else: ?>

      <div class="profile-header">
        <div class="profile-header-info">
          <h1>Vendas</h1>
          <p>Todos os pedidos da loja. Clique em "Pedido de ..." para ver os detalhes e no nome do cliente para ver todas as compras dele.</p>
        </div>
      </div>

      <section class="vd-kpis" aria-label="Resumo do filtro">
        <div class="vd-kpi"><span class="vd-kpi__valor"><?= (int)$tot['n'] ?></span><span class="vd-kpi__rotulo">pedidos encontrados</span></div>
        <div class="vd-kpi"><span class="vd-kpi__valor"><?= brl($tot['faturado']) ?></span><span class="vd-kpi__rotulo">faturado (sem cancelados)</span></div>
        <div class="vd-kpi"><span class="vd-kpi__valor"><?= (int)$tot['pendentes'] ?></span><span class="vd-kpi__rotulo">aguardando (Pendente)</span></div>
      </section>

      <form method="GET" class="vd-filtros">
        <label>Buscar <input type="text" name="busca" value="<?= h($busca) ?>" placeholder="Cliente ou e-mail"></label>
        <label>Status
          <select name="status">
            <option value="">Todos</option>
            <?php foreach (STATUS_PEDIDO as $s): ?><option value="<?= h($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label>De <input type="date" name="de" value="<?= h($de) ?>"></label>
        <label>Até <input type="date" name="ate" value="<?= h($ate) ?>"></label>
        <button type="submit" class="vd-btn">Filtrar</button>
        <a href="vendas.php" class="vd-btn vd-btn--claro">Limpar</a>
        <a href="relatorio.php" class="vd-btn vd-btn--claro">📊 Ver relatório e gráficos</a>
      </form>

      <section class="form-card table-card">
        <div class="table-responsive">
          <table class="data-table vd-tabela">
            <thead>
              <tr><th>Pedido</th><th>Data</th><th>Cliente</th><th class="vd-num">Peças</th><th>Pagamento</th><th class="vd-num">Total</th><th>Status</th><th>Alterar</th></tr>
            </thead>
            <tbody>
              <?php if (!$pedidos): ?>
                <tr><td colspan="8" class="empty-table"><p>Nenhum pedido encontrado.</p></td></tr>
              <?php endif; ?>
              <?php foreach ($pedidos as $p): ?>
                <tr>
                  <td><a class="vd-link" href="vendas.php?id=<?= (int)$p['id'] ?>">Pedido de <?= h($p['nome']) ?></a></td>
                  <td><?= h(date('d/m/Y H:i', strtotime($p['data_criacao']))) ?></td>
                  <td>
                    <a class="vd-link" href="compras-usuario.php?id=<?= (int)$p['uid'] ?>"><?= h($p['nome']) ?></a><br>
                    <span class="vd-muted"><?= h($p['email']) ?></span>
                  </td>
                  <td class="vd-num"><?= (int)$p['pecas'] ?></td>
                  <td><?= h(FORMAS_PAGAMENTO[$p['forma_pagamento'] ?? ''] ?? '—') ?></td>
                  <td class="vd-num"><strong><?= brl($p['valor_total']) ?></strong></td>
                  <td><span class="vd-status <?= h(classe_status($p['status'])) ?>"><?= h($p['status']) ?></span></td>
                  <td><?= form_status($p) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($paginas > 1): ?>
          <div class="vd-barra-acoes" style="justify-content:center;margin-top:18px">
            <?php if ($pagina > 1): ?><a class="vd-btn vd-btn--claro vd-btn--pequeno" href="?<?= h($qs_filtros . ($qs_filtros ? '&' : '') . 'p=' . ($pagina - 1)) ?>">← Anterior</a><?php endif; ?>
            <span class="vd-muted" style="align-self:center">Página <?= $pagina ?> de <?= $paginas ?></span>
            <?php if ($pagina < $paginas): ?><a class="vd-btn vd-btn--claro vd-btn--pequeno" href="?<?= h($qs_filtros . ($qs_filtros ? '&' : '') . 'p=' . ($pagina + 1)) ?>">Próxima →</a><?php endif; ?>
          </div>
        <?php endif; ?>
      </section>

<?php endif; ?>

    </div>
  </main>
  <script>window.CICRO_NOTIF = {pedidos: false};</script>
  <script src="../../js/notificacoes-admin.js"></script>
  <script src="../../js/confirmar-saida.js"></script>
</body>
</html>
