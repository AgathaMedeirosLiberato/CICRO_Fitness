<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../controllers/auth_admin.php';
require_once __DIR__ . '/../../models/cpf_funcoes.php';

if (($_SESSION['usuario_tipo'] ?? '') !== 'admin') {
    $_SESSION['flash_admin'] = ['ok' => false, 'msg' => 'Só o administrador pode analisar pedidos de troca de CPF.'];
    header('Location: inicio.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $id    = (int)($_POST['id'] ?? 0);
    $acao  = $_POST['acao'] ?? '';
    $resp  = mb_substr(trim((string)($_POST['resposta'] ?? '')), 0, 255);
    try {
        if (!in_array($acao, ['aprovar', 'recusar'], true)) {
            throw new RuntimeException('Ação inválida.');
        }
        $st = $pdo->prepare("UPDATE solicitacao_cpf SET status = ?, resposta = ?, fk_admin = ?, data_resposta = NOW()
                             WHERE id = ? AND status = 'pendente'");
        $st->execute([$acao === 'aprovar' ? 'aprovada' : 'recusada', $resp !== '' ? $resp : null, (int)$_SESSION['usuario_id'], $id]);
        if ($st->rowCount() === 0) {
            throw new RuntimeException('Este pedido já foi respondido.');
        }
        $_SESSION['flash_admin'] = ['ok' => true, 'msg' => $acao === 'aprovar'
            ? 'Troca aprovada. O cliente já pode digitar o novo CPF no perfil.' : 'Pedido recusado.'];
    } catch (RuntimeException $e) {
        $_SESSION['flash_admin'] = ['ok' => false, 'msg' => $e->getMessage()];
    } catch (PDOException $e) {
        error_log('solicitacoes-cpf: ' . $e->getMessage());
        $_SESSION['flash_admin'] = ['ok' => false, 'msg' => 'Não foi possível salvar. A migração v9 foi aplicada no banco?'];
    }
    header('Location: solicitacoes-cpf.php');
    exit;
}

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);

$pendentes = $historico = [];
$erro_tabela = false;
try {
    $base = "SELECT s.*, u.nome, u.email, c.cpf AS cpf_agora
             FROM solicitacao_cpf s
             JOIN usuario u ON u.id = s.fk_cliente
             LEFT JOIN cliente c ON c.id_usuario = s.fk_cliente";
    $pendentes = $pdo->query("$base WHERE s.status = 'pendente' ORDER BY s.id")->fetchAll();
    $historico = $pdo->query("$base WHERE s.status <> 'pendente' ORDER BY s.data_resposta DESC, s.id DESC LIMIT 30")->fetchAll();
} catch (PDOException $e) {
    error_log('solicitacoes-cpf: ' . $e->getMessage());
    $erro_tabela = true;
}
$rotulo = ['aprovada' => ['Aprovada, aguardando o cliente', 'st-pago'], 'recusada' => ['Recusada', 'st-cancelado'], 'concluida' => ['CPF trocado', 'st-entregue']];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Solicitações de CPF</title>
  <link rel="stylesheet" href="../../css/style10.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/admin-vendas.css">
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
      <?php if ($flash): ?>
        <div class="aviso vd-aviso-topo <?= $flash['ok'] ? 'aviso-ok' : 'aviso-erro' ?>" role="status"><?= h($flash['msg']) ?></div>
      <?php endif; ?>

      <div class="profile-header">
        <div class="profile-header-info">
          <h1>Solicitações de troca de CPF</h1>
          <p>O cliente só consegue editar o CPF depois que você aprova. Cada aprovação vale para uma única troca.</p>
        </div>
        <div class="vd-barra-acoes"><a href="inicio.php" class="vd-btn vd-btn--claro">← Voltar ao painel</a></div>
      </div>

      <?php if ($erro_tabela): ?>
        <div class="aviso aviso-erro">A tabela de solicitações não existe. Rode <strong>banco/migracao_v9.sql</strong> no phpMyAdmin.</div>
      <?php endif; ?>

      <h2 class="cpf-subtitulo">Aguardando resposta <span class="cpf-contador"><?= count($pendentes) ?></span></h2>
      <section class="form-card table-card">
        <div class="table-responsive">
          <table class="data-table vd-tabela">
            <thead><tr><th>Pedido</th><th>Cliente</th><th>CPF atual</th><th>Motivo</th><th>Responder</th></tr></thead>
            <tbody>
              <?php if (!$pendentes): ?><tr><td colspan="5" class="empty-table"><p>Nenhuma solicitação pendente.</p></td></tr><?php endif; ?>
              <?php foreach ($pendentes as $p): ?>
                <tr>
                  <td>#<?= (int)$p['id'] ?><br><span class="vd-muted"><?= h(date('d/m/Y H:i', strtotime($p['data_solicitacao']))) ?></span></td>
                  <td><strong><?= h($p['nome']) ?></strong><br><span class="vd-muted"><?= h($p['email']) ?></span></td>
                  <td><?= h(cpf_exibir($p['cpf_atual']) ?: cpf_ocultar(null)) ?></td>
                  <td style="max-width:280px"><?= nl2br(h($p['motivo'])) ?></td>
                  <td>
                    <form method="POST" class="cpf-resposta">
                      <?= csrf_campo() ?>
                      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                      <input type="text" name="resposta" maxlength="255" placeholder="Recado ao cliente (opcional)" aria-label="Recado ao cliente">
                      <div>
                        <button type="submit" name="acao" value="aprovar" class="vd-btn vd-btn--pequeno">Aprovar</button>
                        <button type="submit" name="acao" value="recusar" class="vd-btn vd-btn--claro vd-btn--pequeno">Recusar</button>
                      </div>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <h2 class="cpf-subtitulo">Respondidas recentemente</h2>
      <section class="form-card table-card">
        <div class="table-responsive">
          <table class="data-table vd-tabela">
            <thead><tr><th>Pedido</th><th>Cliente</th><th>Motivo</th><th>Situação</th><th>Respondida em</th></tr></thead>
            <tbody>
              <?php if (!$historico): ?><tr><td colspan="5" class="empty-table"><p>Ainda não há respostas.</p></td></tr><?php endif; ?>
              <?php foreach ($historico as $p): [$txt, $cls] = $rotulo[$p['status']] ?? [$p['status'], '']; ?>
                <tr>
                  <td>#<?= (int)$p['id'] ?></td>
                  <td><?= h($p['nome']) ?><br><span class="vd-muted"><?= h($p['email']) ?></span></td>
                  <td style="max-width:280px"><?= h($p['motivo']) ?><?= $p['resposta'] ? '<br><span class="vd-muted">Resposta: ' . h($p['resposta']) . '</span>' : '' ?></td>
                  <td><span class="vd-status <?= h($cls) ?>"><?= h($txt) ?></span></td>
                  <td><?= $p['data_resposta'] ? h(date('d/m/Y H:i', strtotime($p['data_resposta']))) : '—' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </main>
  <script>window.CICRO_NOTIF = {pedidos: false, cpf: false};</script>
  <script src="../../js/notificacoes-admin.js"></script>
  <script src="../../js/confirmar-saida.js"></script>
</body>
</html>
