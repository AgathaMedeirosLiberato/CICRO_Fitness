<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../controllers/auth_admin.php';
require_once __DIR__ . '/../../models/pedido_funcoes.php';
require_once __DIR__ . '/../../models/cpf_cripto.php';

$id = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("SELECT u.id, u.nome, u.email, u.telefone, u.tipo_usuario, u.ativo, u.data_cadastro, c.cpf
                     FROM usuario u LEFT JOIN cliente c ON c.id_usuario = u.id WHERE u.id = ?");
$st->execute([$id]);
$usuario = $st->fetch();
if (!$usuario) {
    $_SESSION['flash_admin'] = ['ok' => false, 'msg' => 'Usuário não encontrado.'];
    header('Location: cadastros.php');
    exit;
}

$st = $pdo->prepare(
    "SELECT id, data_criacao, status, valor_total,
            forma_pagamento, endereco_entrega
     FROM pedido WHERE fk_cliente = ? ORDER BY id DESC"
);
$st->execute([$id]);
$pedidos = $st->fetchAll();
$itens   = itens_dos_pedidos($pdo, array_column($pedidos, 'id'));

$validos = array_filter($pedidos, static fn($p) => in_array($p['status'], ['Pago', 'Em Preparação', 'Enviado', 'Entregue'], true));
$gasto   = array_sum(array_column($validos, 'valor_total'));
$n_valid = count($validos);
$ultima  = $pedidos ? $pedidos[0]['data_criacao'] : null;

$st = $pdo->prepare("SELECT COUNT(*) FROM endereco WHERE fk_cliente = ?");
$st->execute([$id]);
$n_enderecos = (int)$st->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Compras de <?= h($usuario['nome']) ?></title>
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
          <h1>Compras de <?= h($usuario['nome']) ?></h1>
          <p>
            <?= h($usuario['email']) ?> · <?= h($usuario['telefone'] ?: 'sem telefone') ?> · CPF <?= h(cpf_exibir($usuario['cpf']) ?: 'não informado') ?> ·
            cliente desde <?= h(date('d/m/Y', strtotime($usuario['data_cadastro']))) ?> ·
            <?= (int)$usuario['ativo'] ? 'conta ativa' : 'conta inativa' ?> ·
            <?= $n_enderecos ?> endereço(s) cadastrado(s)
          </p>
        </div>
        <div class="vd-barra-acoes no-print">
          <a href="cadastros.php" class="vd-btn vd-btn--claro">← Voltar aos cadastros</a>
          <button type="button" class="vd-btn vd-btn--claro" onclick="window.print()">🖨 Imprimir / PDF</button>
        </div>
      </div>

      <?php if ($usuario['tipo_usuario'] !== 'cliente'): ?>
        <div class="aviso aviso-erro vd-aviso-topo">Este usuário é <?= h($usuario['tipo_usuario']) ?> (não é cliente); normalmente não tem compras.</div>
      <?php endif; ?>

      <section class="vd-kpis">
        <div class="vd-kpi"><span class="vd-kpi__valor"><?= count($pedidos) ?></span><span class="vd-kpi__rotulo">pedidos no total</span></div>
        <div class="vd-kpi"><span class="vd-kpi__valor"><?= brl($gasto) ?></span><span class="vd-kpi__rotulo">total comprado (só pedidos pagos)</span></div>
        <div class="vd-kpi"><span class="vd-kpi__valor"><?= brl($n_valid > 0 ? $gasto / $n_valid : 0) ?></span><span class="vd-kpi__rotulo">ticket médio</span></div>
        <div class="vd-kpi"><span class="vd-kpi__valor"><?= $ultima ? h(date('d/m/Y', strtotime($ultima))) : '—' ?></span><span class="vd-kpi__rotulo">última compra</span></div>
      </section>

      <?php if (!$pedidos): ?>
        <section class="form-card table-card">
          <p class="empty-table" style="text-align:center;padding:30px;color:#777">Este usuário ainda não fez nenhuma compra.</p>
        </section>
      <?php endif; ?>

      <?php foreach ($pedidos as $p): ?>
        <article class="vd-pedido">
          <div class="vd-pedido__topo">
            <div>
              <strong><a class="vd-link" href="vendas.php?id=<?= (int)$p['id'] ?>">Pedido de <?= h($usuario['nome']) ?></a></strong>
              <span class="vd-muted"> · <?= h(date('d/m/Y H:i', strtotime($p['data_criacao']))) ?></span>
            </div>
            <div>
              <span class="vd-status <?= h(classe_status($p['status'])) ?>"><?= h($p['status']) ?></span>
              <strong style="margin-left:12px"><?= brl($p['valor_total']) ?></strong>
            </div>
          </div>
          <div class="vd-pedido__corpo">
            <table>
              <thead><tr><th>Produto</th><th>Tam.</th><th class="vd-num">Qtd.</th><th class="vd-num">Unitário</th><th class="vd-num">Subtotal</th></tr></thead>
              <tbody>
                <?php foreach ($itens[(int)$p['id']] ?? [] as $i): ?>
                  <tr>
                    <td><?= h($i['nome_produto']) ?></td><td><?= h($i['tamanho']) ?><?= !in_array($i['cor'], ['', 'Padrão'], true) ? ' · ' . h($i['cor']) : '' ?></td>
                    <td class="vd-num"><?= (int)$i['quantidade'] ?></td>
                    <td class="vd-num"><?= brl($i['preco_unitario']) ?></td>
                    <td class="vd-num"><?= brl($i['preco_unitario'] * $i['quantidade']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <p class="vd-pedido__rodape">
              <strong>Entrega:</strong> <?= h($p['endereco_entrega'] ?: 'não registrada') ?><br>
              <strong>Pagamento:</strong> <?= h(FORMAS_PAGAMENTO[$p['forma_pagamento'] ?? ''] ?? 'não informado') ?>
            </p>
          </div>
        </article>
      <?php endforeach; ?>

    </div>
  </main>
  <script>window.CICRO_NOTIF = {pedidos: false};</script>
  <script src="../../js/notificacoes-admin.js"></script>
  <script src="../../js/confirmar-saida.js"></script>
</body>
</html>
