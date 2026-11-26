<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../controllers/auth_admin.php';
require_once __DIR__ . '/../../models/relatorio_dados.php';

[$de, $ate] = periodo_do_filtro($_GET);
$D = dados_relatorio($pdo, $de, $ate);
$R = $D['resumo'];
$gerado_por = $_SESSION['usuario_nome'] ?? 'Administrador';
$dias_com_venda = array_values(array_filter($D['por_dia'], static fn($d) => $d['pedidos'] > 0));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Relatorio-CICRO_<?= h($de) ?>_a_<?= h($ate) ?></title><!-- vira o nome do arquivo PDF -->
  <style>
    @page { size: A4; margin: 14mm; }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;color:#111;font-size:12px;line-height:1.45;background:#eee}
    .pagina{max-width:190mm;margin:0 auto;background:#fff;padding:14mm}
    .barra{max-width:190mm;margin:12px auto;display:flex;gap:10px;align-items:center;justify-content:space-between;font-size:13px}
    .barra button{padding:9px 18px;border:0;border-radius:99px;background:#111;color:#fff;font-weight:700;cursor:pointer}
    .topo{display:flex;justify-content:space-between;align-items:flex-end;border-bottom:2px solid #111;padding-bottom:10px;margin-bottom:16px}
    .topo h1{font-size:22px;letter-spacing:.02em}
    .topo .logo{font-weight:900;letter-spacing:.2em;font-size:13px}
    .meta{color:#555;font-size:11px;text-align:right}
    h2{font-size:13px;margin:18px 0 8px;text-transform:uppercase;letter-spacing:.06em;color:#333}
    .kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:8px}
    .kpi{border:1px solid #ccc;border-radius:6px;padding:8px 10px}
    .kpi b{display:block;font-size:15px}
    .kpi span{font-size:10px;color:#666}
    .graf{border:1px solid #ddd;border-radius:6px;padding:10px;margin-top:8px;break-inside:avoid}
    .graf h3{font-size:11px;margin-bottom:6px}
    .graf .area{position:relative;height:210px}
    .graf--curto .area{height:190px}
    .duas{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    table{width:100%;border-collapse:collapse;font-size:11px;break-inside:auto}
    tr{break-inside:avoid}
    th{background:#f2f2f2;text-align:left;padding:5px 7px;font-size:10px;text-transform:uppercase;letter-spacing:.04em}
    td{padding:5px 7px;border-top:1px solid #e5e5e5}
    .n{text-align:right}
    .vazio{color:#888;padding:8px 0}
    .rodape{margin-top:18px;border-top:1px solid #ccc;padding-top:6px;color:#777;font-size:10px;text-align:center}
    @media print{
      body{background:#fff}
      .barra{display:none}
      .pagina{padding:0;max-width:none}
      * { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    }
  </style>
</head>
<body data-pdf>

  <div class="barra">
    <span>Na janela de impressão, escolha <strong>Salvar como PDF</strong> como destino.</span>
    <span><button type="button" onclick="window.print()">Imprimir / Salvar PDF</button></span>
  </div>

  <div class="pagina">
    <div class="topo">
      <div>
        <div class="logo">CICRO</div>
        <h1>Relatório de vendas</h1>
      </div>
      <div class="meta">
        Período: <strong><?= h(date('d/m/Y', strtotime($de))) ?> a <?= h(date('d/m/Y', strtotime($ate))) ?></strong><br>
        Gerado em <?= h(date('d/m/Y H:i')) ?> por <?= h($gerado_por) ?>
      </div>
    </div>

    <h2>Resumo</h2>
    <div class="kpis">
      <div class="kpi"><b><?= brl($R['faturamento']) ?></b><span>faturamento</span></div>
      <div class="kpi"><b><?= (int)$R['pedidos'] ?></b><span>pedidos</span></div>
      <div class="kpi"><b><?= brl($R['ticket_medio']) ?></b><span>ticket médio</span></div>
      <div class="kpi"><b><?= (int)$R['pecas'] ?></b><span>peças vendidas</span></div>
      <div class="kpi"><b><?= (int)$R['cancelados'] ?></b><span>cancelados (<?= brl($R['valor_cancelado']) ?>)</span></div>
    </div>
    <p class="vazio" style="margin-top:6px">Só pedidos pagos entram no faturamento.</p>

    <h2>Gráficos</h2>
    <div class="graf"><h3>Faturamento por dia</h3><div class="area"><canvas id="g-dia"></canvas></div></div>
    <div class="duas">
      <div class="graf graf--curto"><h3>Pedidos por status</h3><div class="area"><canvas id="g-status"></canvas></div></div>
      <div class="graf graf--curto"><h3>Receita por forma de pagamento</h3><div class="area"><canvas id="g-pagamento"></canvas></div></div>
    </div>
    <div class="duas">
      <div class="graf graf--curto"><h3>Produtos mais vendidos</h3><div class="area"><canvas id="g-produtos"></canvas></div></div>
      <div class="graf graf--curto"><h3>Receita por categoria</h3><div class="area"><canvas id="g-categoria"></canvas></div></div>
    </div>

    <h2>Top produtos</h2>
    <table>
      <thead><tr><th>Produto</th><th class="n">Peças</th><th class="n">Receita</th></tr></thead>
      <tbody>
        <?php if (!$D['top_produtos']): ?><tr><td colspan="3" class="vazio">Sem vendas no período.</td></tr><?php endif; ?>
        <?php foreach ($D['top_produtos'] as $p): ?>
          <tr><td><?= h($p['nome']) ?></td><td class="n"><?= (int)$p['qtd'] ?></td><td class="n"><?= brl($p['receita']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h2>Melhores clientes</h2>
    <table>
      <thead><tr><th>Cliente</th><th class="n">Pedidos</th><th class="n">Total</th></tr></thead>
      <tbody>
        <?php if (!$D['top_clientes']): ?><tr><td colspan="3" class="vazio">Sem vendas no período.</td></tr><?php endif; ?>
        <?php foreach ($D['top_clientes'] as $c): ?>
          <tr><td><?= h($c['nome']) ?></td><td class="n"><?= (int)$c['pedidos'] ?></td><td class="n"><?= brl($c['valor']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h2>Vendas por dia</h2>
    <table>
      <thead><tr><th>Data</th><th class="n">Pedidos</th><th class="n">Faturamento</th></tr></thead>
      <tbody>
        <?php if (!$dias_com_venda): ?><tr><td colspan="3" class="vazio">Sem vendas no período.</td></tr><?php endif; ?>
        <?php foreach ($dias_com_venda as $d): ?>
          <tr><td><?= h(date('d/m/Y', strtotime($d['dia']))) ?></td><td class="n"><?= (int)$d['pedidos'] ?></td><td class="n"><?= brl($d['valor']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="rodape">CICRO · relatório gerado automaticamente a partir dos pedidos registrados no sistema.</div>
  </div>

  <script type="application/json" id="dados-relatorio"><?= json_para_pagina($D) ?></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
  <script>
    (function () {
      var disparou = false;
      function imprimir() { if (disparou) return; disparou = true; setTimeout(function () { window.print(); }, 400); }
      document.addEventListener('graficos-prontos', imprimir);
      window.addEventListener('load', function () { setTimeout(imprimir, 1500); });
    })();
  </script>
  <script src="../../js/relatorio-graficos.js"></script>
</body>
</html>
