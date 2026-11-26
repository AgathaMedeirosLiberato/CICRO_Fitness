const reports = [
  {
    title: "Relatório de Vendas - Setembro",
    content: `
      <table class="report-table">
        <thead>
          <tr><th>Produto</th><th>Quantidade Vendida</th><th>Receita (R$)</th></tr>
        </thead>
        <tbody>
          <tr><td>Cropped BellaMania</td><td>120</td><td>3.598,80</td></tr>
          <tr><td>Blusa Taylor</td><td>75</td><td>7.199,25</td></tr>
          <tr><td>Shorts Active</td><td>50</td><td>2.499,50</td></tr>
        </tbody>
      </table>
    `
  },
  {
    title: "Relatório de Estoque",
    content: `
      <table class="report-table">
        <thead>
          <tr><th>Produto</th><th>Quantidade em Estoque</th><th>Status</th></tr>
        </thead>
        <tbody>
          <tr><td>Cropped BellaMania</td><td>300</td><td>Disponível</td></tr>
          <tr><td>Blusa Taylor</td><td>120</td><td>Baixo estoque</td></tr>
          <tr><td>Shorts Active</td><td>0</td><td>Esgotado</td></tr>
        </tbody>
      </table>
    `
  },
  {
    title: "Relatório de Usuários",
    content: `
      <table class="report-table">
        <thead>
          <tr><th>Nome</th><th>Email</th><th>Status</th></tr>
        </thead>
        <tbody>
          <tr><td>Ana Souza</td><td>ana@email.com</td><td>Ativo</td></tr>
          <tr><td>Lucas Silva</td><td>lucas@email.com</td><td>Inativo</td></tr>
          <tr><td>Maria Oliveira</td><td>maria@email.com</td><td>Ativo</td></tr>
        </tbody>
      </table>
    `
  },
  {
    title: "Relatório de Compras",
    content: `
      <table class="report-table">
        <thead>
          <tr><th>ID Pedido</th><th>Cliente</th><th>Valor (R$)</th><th>Status</th></tr>
        </thead>
        <tbody>
          <tr><td>#001</td><td>Ana Souza</td><td>299,90</td><td>Concluído</td></tr>
          <tr><td>#002</td><td>Lucas Silva</td><td>159,90</td><td>Pendente</td></tr>
          <tr><td>#003</td><td>Maria Oliveira</td><td>499,90</td><td>Cancelado</td></tr>
        </tbody>
      </table>
    `
  }
];

let currentIndex = 0;
const reportContent = document.getElementById("reportContent");
const prevBtn = document.getElementById("prevReport");
const nextBtn = document.getElementById("nextReport");

function renderReport(index) {
  const report = reports[index];
  reportContent.innerHTML = `<h3>${report.title}</h3>${report.content}`;
}

renderReport(currentIndex);

prevBtn.addEventListener("click", () => {
  currentIndex = (currentIndex - 1 + reports.length) % reports.length;
  renderReport(currentIndex);
});

nextBtn.addEventListener("click", () => {
  currentIndex = (currentIndex + 1) % reports.length;
  renderReport(currentIndex);
});
