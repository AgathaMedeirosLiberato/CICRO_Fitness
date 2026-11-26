(function () {
  var el = document.getElementById('dados-relatorio');
  if (!el || typeof Chart === 'undefined') return;
  var D = JSON.parse(el.textContent);
  var PARA_PDF = document.body.hasAttribute('data-pdf');

  var brl = function (v) { return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
  var dia = function (iso) { var p = iso.split('-'); return p[2] + '/' + p[1]; };

  var COR_STATUS = { Pendente: '#d97706', Pago: '#2563eb', 'Em Preparação': '#ea580c', Enviado: '#7c3aed', Entregue: '#059669', Cancelado: '#dc2626' };
  var PALETA = ['#111827', '#2563eb', '#d97706', '#059669', '#7c3aed', '#9ca3af'];

  Chart.defaults.font.family = '"Helvetica Neue", Helvetica, Arial, sans-serif';
  Chart.defaults.color = '#444';
  Chart.defaults.animation = PARA_PDF ? false : { duration: 500 };

  function vazio(id) {
    var c = document.getElementById(id);
    if (c) c.parentNode.innerHTML = '<div class="vd-vazio-grafico">Sem vendas neste período</div>';
  }
  function criar(id, config, temDados) {
    var c = document.getElementById(id);
    if (!c) return;
    if (!temDados) { vazio(id); return; }
    new Chart(c, config);
  }

  var temDia = D.por_dia.some(function (d) { return d.valor > 0; });
  criar('g-dia', {
    type: 'line',
    data: {
      labels: D.por_dia.map(function (d) { return dia(d.dia); }),
      datasets: [{
        label: 'Faturamento', data: D.por_dia.map(function (d) { return d.valor; }),
        borderColor: '#111827', backgroundColor: 'rgba(17,24,39,.08)', fill: true, tension: .25,
        pointRadius: D.por_dia.length > 31 ? 0 : 3
      }]
    },
    options: {
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function (ctx) {
              var d = D.por_dia[ctx.dataIndex];
              return [' ' + brl(d.valor), ' ' + d.pedidos + ' pedido(s)'];
            }
          }
        }
      },
      scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return brl(v); } } }, x: { ticks: { maxTicksLimit: 12 } } }
    }
  }, temDia);

  criar('g-status', {
    type: 'doughnut',
    data: {
      labels: D.por_status.map(function (s) { return s.status; }),
      datasets: [{ data: D.por_status.map(function (s) { return Number(s.pedidos); }),
                   backgroundColor: D.por_status.map(function (s, i) { return COR_STATUS[s.status] || PALETA[i % PALETA.length]; }) }]
    },
    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' },
      tooltip: { callbacks: { label: function (c) { return ' ' + c.label + ': ' + c.parsed + ' pedido(s)'; } } } } }
  }, D.por_status.length > 0);

  criar('g-pagamento', {
    type: 'doughnut',
    data: {
      labels: D.por_pagamento.map(function (p) { return p.rotulo; }),
      datasets: [{ data: D.por_pagamento.map(function (p) { return Number(p.valor); }),
                   backgroundColor: PALETA }]
    },
    options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' },
      tooltip: { callbacks: { label: function (c) { return ' ' + c.label + ': ' + brl(c.parsed); } } } } }
  }, D.por_pagamento.length > 0);

  criar('g-produtos', {
    type: 'bar',
    data: {
      labels: D.top_produtos.map(function (p) { return p.nome; }),
      datasets: [{ label: 'Receita', data: D.top_produtos.map(function (p) { return Number(p.receita); }), backgroundColor: '#111827' }]
    },
    options: { indexAxis: 'y', maintainAspectRatio: false,
      plugins: { legend: { display: false },
        tooltip: { callbacks: { label: function (c) { var p = D.top_produtos[c.dataIndex]; return [' ' + brl(p.receita), ' ' + p.qtd + ' peça(s)']; } } } },
      scales: { x: { beginAtZero: true, ticks: { callback: function (v) { return brl(v); } } } } }
  }, D.top_produtos.length > 0);

  criar('g-categoria', {
    type: 'bar',
    data: {
      labels: D.por_categoria.map(function (c) { return c.nome; }),
      datasets: [{ label: 'Receita', data: D.por_categoria.map(function (c) { return Number(c.receita); }), backgroundColor: '#2563eb' }]
    },
    options: { maintainAspectRatio: false,
      plugins: { legend: { display: false },
        tooltip: { callbacks: { label: function (c) { var p = D.por_categoria[c.dataIndex]; return [' ' + brl(p.receita), ' ' + p.qtd + ' peça(s)']; } } } },
      scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return brl(v); } } } } }
  }, D.por_categoria.length > 0);

  document.dispatchEvent(new Event('graficos-prontos'));
})();
