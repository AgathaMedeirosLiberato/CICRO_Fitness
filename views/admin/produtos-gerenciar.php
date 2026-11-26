<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../controllers/auth_admin.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('h')) {
    function h($valor): string
    {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }
}

function flash(string $msg, bool $ok = true): void
{
    $_SESSION['flash_admin'] = ['ok' => $ok, 'msg' => $msg];
    header('Location: produtos-gerenciar.php');
    exit;
}

function limpar_prefixo(string $url): string
{
    return preg_replace('#^(?:\.{1,2}/)+#', '', ltrim($url, '/'));
}

function apagar_arquivo_imagem(string $url): void
{
    $base    = realpath(dirname(__DIR__, 2) . '/admin-pages/uploads');
    $arquivo = realpath(dirname(__DIR__, 2) . '/' . limpar_prefixo($url));
    if ($base && $arquivo && strpos($arquivo, $base . DIRECTORY_SEPARATOR) === 0 && is_file($arquivo)) {
        @unlink($arquivo);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $acao = $_POST['acao'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT id, nome, status FROM produto WHERE id = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch();
    if (!$produto) {
        flash('Produto não encontrado.', false);
    }

    if ($acao === 'desativar' || $acao === 'ativar') {
        $novo = $acao === 'ativar' ? 1 : 0;
        $pdo->prepare("UPDATE produto SET status = ? WHERE id = ?")->execute([$novo, $id]);
        flash('Produto "' . $produto['nome'] . '" ' . ($novo ? 'ativado (volta a aparecer no catálogo).' : 'desativado (some do catálogo, mas continua cadastrado).'));
    }

    if ($acao === 'excluir') {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM item_pedido
             WHERE fk_variacao IN (SELECT id FROM variacao_produto WHERE fk_produto = ?)"
        );
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            flash('"' . $produto['nome'] . '" já foi vendido e não pode ser excluído (isso apagaria o histórico de pedidos). Use "Desativar".', false);
        }

        $stmt = $pdo->prepare("SELECT url_imagem FROM imagem_produto WHERE fk_produto = ?");
        $stmt->execute([$id]);
        $arquivos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        try {
            $pdo->beginTransaction();
            $sub = "(SELECT id FROM variacao_produto WHERE fk_produto = ?)";
            $pdo->prepare("DELETE FROM item_carrinho WHERE fk_variacao IN $sub")->execute([$id]);
            $pdo->prepare("DELETE FROM movimentacao_estoque WHERE fk_variacao IN $sub")->execute([$id]);
            $pdo->prepare("DELETE FROM avaliacao WHERE fk_produto = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM imagem_produto WHERE fk_produto = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM variacao_produto WHERE fk_produto = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM produto WHERE id = ?")->execute([$id]);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro ao excluir produto: ' . $e->getMessage());
            flash('Não foi possível excluir o produto. Tente desativá-lo.', false);
        }
        foreach ($arquivos as $url) {
            apagar_arquivo_imagem($url);
        }
        flash('Produto "' . $produto['nome'] . '" excluído.');
    }

    flash('Ação inválida.', false);
}

$busca  = trim($_GET['busca'] ?? '');
$filtro = $_GET['status'] ?? 'todos';

$sql = "SELECT p.id, p.nome, p.preco_base, p.status, c.nome AS categoria,
               (SELECT COALESCE(SUM(quantidade_estoque), 0) FROM variacao_produto WHERE fk_produto = p.id) AS estoque,
               (SELECT url_imagem FROM imagem_produto WHERE fk_produto = p.id ORDER BY capa DESC, ordem ASC, id ASC LIMIT 1) AS imagem
        FROM produto p
        JOIN categoria c ON c.id = p.fk_categoria
        WHERE 1 = 1";
$params = [];
if ($busca !== '') {
    $sql .= " AND p.nome LIKE ?";
    $params[] = '%' . addcslashes($busca, '%_\\') . '%';
}
if ($filtro === 'ativos')   { $sql .= " AND p.status = 1"; }
if ($filtro === 'inativos') { $sql .= " AND p.status = 0"; }
$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);

function img_url(?string $url): string
{
    return $url ? '../../' . limpar_prefixo($url) : '../../images/uploads/CICRO.png';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CICRO - Gerenciar produtos</title>
    <link rel="stylesheet" href="../../css/style8.css">
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

<main class="adm-page">
    <header class="adm-topo">
        <h1>Produtos</h1>
        <div class="adm-topo-acoes">
            <a href="inicio.php" class="btn btn-claro">← Painel</a>
            <a href="produtos.php" class="btn btn-escuro">+ Novo produto</a>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="aviso <?= $flash['ok'] ? 'aviso-ok' : 'aviso-erro' ?>"><?= h($flash['msg']) ?></div>
    <?php endif; ?>

    <form method="GET" class="adm-filtros">
        <input type="text" name="busca" value="<?= h($busca) ?>" placeholder="Buscar por nome...">
        <select name="status">
            <option value="todos"    <?= $filtro === 'todos' ? 'selected' : '' ?>>Todos</option>
            <option value="ativos"   <?= $filtro === 'ativos' ? 'selected' : '' ?>>Ativos</option>
            <option value="inativos" <?= $filtro === 'inativos' ? 'selected' : '' ?>>Desativados</option>
        </select>
        <button type="submit" class="btn btn-escuro">Filtrar</button>
    </form>

    <?php if (!$produtos): ?>
        <p class="vazio">Nenhum produto encontrado.</p>
    <?php else: ?>
    <div class="tabela-wrap">
    <table class="tabela">
        <thead>
            <tr><th></th><th>Produto</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Status</th><th>Ações</th></tr>
        </thead>
        <tbody>
        <?php foreach ($produtos as $p): ?>
            <tr class="<?= $p['status'] ? '' : 'inativo' ?>">
                <td><img class="mini" src="<?= h(img_url($p['imagem'])) ?>" alt=""></td>
                <td><strong><?= h($p['nome']) ?></strong></td>
                <td><?= h($p['categoria']) ?></td>
                <td>R$ <?= number_format((float)$p['preco_base'], 2, ',', '.') ?></td>
                <td><?= (int)$p['estoque'] ?></td>
                <td><span class="badge <?= $p['status'] ? 'badge-ok' : 'badge-off' ?>"><?= $p['status'] ? 'Ativo' : 'Desativado' ?></span></td>
                <td class="acoes">
                    <a class="btn btn-claro" href="produto-editar.php?id=<?= (int)$p['id'] ?>">Editar</a>

                    <form method="POST" class="inline">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <input type="hidden" name="acao" value="<?= $p['status'] ? 'desativar' : 'ativar' ?>">
                        <button class="btn btn-claro" type="submit"><?= $p['status'] ? 'Desativar' : 'Ativar' ?></button>
                    </form>

                    <form method="POST" class="inline"
                          onsubmit="return confirm(<?= h(json_encode('Excluir "' . $p['nome'] . '" de vez? Isso não pode ser desfeito.', JSON_UNESCAPED_UNICODE)) ?>);">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <input type="hidden" name="acao" value="excluir">
                        <button class="btn btn-perigo" type="submit">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</main>
  <script>window.CICRO_NOTIF = {pedidos: false};</script>
  <script src="../../js/notificacoes-admin.js"></script>
  <script src="../../js/confirmar-saida.js"></script>
</body>
</html>