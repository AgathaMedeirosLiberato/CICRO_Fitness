<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../controllers/auth_admin.php';
require_once __DIR__ . '/../../models/relatorio_dados.php';

[$de, $ate] = periodo_do_filtro($_GET);
$D  = dados_relatorio($pdo, $de, $ate);
$R  = $D['resumo'];
$qs = http_build_query(['de' => $de, 'ate' => $ate]);

$hoje = date('Y-m-d');
$atalhos = [
    'Hoje'            => [$hoje, $hoje],
    '7 dias'          => [date('Y-m-d', strtotime('-6 days')), $hoje],
    '30 dias'         => [date('Y-m-d', strtotime('-29 days')), $hoje],
    'Este mês'        => [date('Y-m-01'), $hoje],
    'Mês passado'     => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('first day of last month'))],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Relatório de vendas</title>
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

      <div class="profile-header">
        <div class="profile-header-info">
          <h1>Relatório de vendas</h1>
          <p>Período: <strong><?= h(date('d/m/Y', strtotime($de))) ?></strong> a <strong><?= h(date('d/m/Y', strtotime($ate))) ?></strong>. Só pedidos pagos (Pago, Em Preparação, Enviado, Entregue) entram no faturamento.</p>
        </div>
      </div>

      <form method="GET" class="vd-filtros">
        <label>De <input type="date" name="de" value="<?= h($de) ?>" max="<?= h($hoje) ?>"></label>
        <label>Até <input type="date" name="ate" value="<?= h($ate) ?>" max="<?= h($hoje) ?>"></label>
        <button type="submit" class="vd-btn">Aplicar</button>
        <a class="vd-btn vd-btn--claro" href="relatorio-pdf.php?<?= h($qs) ?>" target="_blank" rel="noopener">⬇ Gerar PDF</a>
      </form>
      <div class="vd-barra-acoes" style="margin:-8px 0 20px">
        <?php foreach ($atalhos as $nome => [$a, $b]): ?>
          <a class="vd-btn vd-btn--claro vd-btn--pequeno" href="?<?= h(http_build_query(['de' => $a, 'ate' => $b])) ?>"><?= h($nome) ?></a>
        <?php endforeach; ?>
      </div>

      <!-- Resumo -->
      <section class="vd-kpis" aria-label="Resumo do período">
        <div class="vd-kpi"><span class="vd-kpi__valor"><?= brl($R['faturamento']) ?></span><span class="vd-kpi__rotulo">faturamento</span></div>
        <div class="vd-kpi"><span class="vd-kpi__valor"><?= (int)$R['pedidos'] ?></span><span class="vd-kpi__rotulo">pedidos</span></div>
        <div class="vd-kpi"><span class="vd-kpi__valor"><?= brl($R['ticket_medio']) ?></span><span class="vd-kpi__rotulo">ticket médio</span></div>
        <div class="vd-kpi"><span class="vd-kpi__valor"><?= (int)$R['pecas'] ?></span><span class="vd-kpi__rotulo">peças vendidas</span></div>
        <div class="vd-kpi vd-kpi--alerta"><span class="vd-kpi__valor"><?= (int)$R['cancelados'] ?></span><span class="vd-kpi__rotulo">cancelados (<?= brl($R['valor_cancelado']) ?>)</span></div>
      </section>

      <!-- Gráficos -->
      <section class="vd-graficos" aria-label="Gráficos de vendas">
        <div class="vd-grafico vd-grafico--largo">
          <h3>Faturamento por dia</h3>
          <div class="vd-grafico__area"><canvas id="g-dia" role="img" aria-label="Gráfico de linha do faturamento por dia"></canvas></div>
        </div>
        <div class="vd-grafico">
          <h3>Pedidos por status</h3>
          <div class="vd-grafico__area"><canvas id="g-status" role="img" aria-label="Pedidos por status"></canvas></div>
        </div>
        <div class="vd-grafico">
          <h3>Receita por forma de pagamento</h3>
          <div class="vd-grafico__area"><canvas id="g-pagamento" role="img" aria-label="Receita por forma de pagamento"></canvas></div>
        </div>
        <div class="vd-grafico">
          <h3>Produtos mais vendidos (receita)</h3>
          <div class="vd-grafico__area"><canvas id="g-produtos" role="img" aria-label="Produtos mais vendidos"></canvas></div>
        </div>
        <div class="vd-grafico">
          <h3>Receita por categoria</h3>
          <div class="vd-grafico__area"><canvas id="g-categoria" role="img" aria-label="Receita por categoria"></canvas></div>
        </div>
      </section>

      <!-- Tabelas -->
      <div class="vd-secoes-2">
        <section class="vd-secao">
          <h2>Top produtos</h2>
          <table class="vd-mini">
            <thead><tr><th>Produto</th><th class="vd-num">Peças</th><th class="vd-num">Receita</th></tr></thead>
            <tbody>
              <?php if (!$D['top_produtos']): ?><tr><td colspan="3" class="vd-muted">Sem vendas no período.</td></tr><?php endif; ?>
              <?php foreach ($D['top_produtos'] as $p): ?>
                <tr><td><?= h($p['nome']) ?></td><td class="vd-num"><?= (int)$p['qtd'] ?></td><td class="vd-num"><?= brl($p['receita']) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>
        <section class="vd-secao">
          <h2>Melhores clientes</h2>
          <table class="vd-mini">
            <thead><tr><th>Cliente</th><th class="vd-num">Pedidos</th><th class="vd-num">Total</th></tr></thead>
            <tbody>
              <?php if (!$D['top_clientes']): ?><tr><td colspan="3" class="vd-muted">Sem vendas no período.</td></tr><?php endif; ?>
              <?php foreach ($D['top_clientes'] as $c): ?>
                <tr>
                  <td><a class="vd-link" href="compras-usuario.php?id=<?= (int)$c['id'] ?>"><?= h($c['nome']) ?></a></td>
                  <td class="vd-num"><?= (int)$c['pedidos'] ?></td><td class="vd-num"><?= brl($c['valor']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </section>
      </div>

    </div>
  </main>

  <script type="application/json" id="dados-relatorio"><?= json_para_pagina($D) ?></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
  <script src="../../js/relatorio-graficos.js"></script>
  <script>window.CICRO_NOTIF = {pedidos: false};</script>
  <script src="../../js/notificacoes-admin.js"></script>
  <script src="../../js/confirmar-saida.js"></script>
</body>
</html>
