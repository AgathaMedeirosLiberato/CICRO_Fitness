<?php
require_once __DIR__ . '/../../config/conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_cliente = $_SESSION['usuario_id'] ?? null;

$pagina_login = 'login.php';

$categorias_padrao = ['CONJUNTOS', 'REGATAS', 'SHORTS', 'CAMISAS'];

$eh_ajax = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === '1');

function responder_json($dados) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados);
    exit;
}

if (!$id_cliente) {
    if ($eh_ajax) {
        responder_json(['ok' => false, 'login' => true, 'erro' => 'Faça login para adicionar ao carrinho.']);
    }
    header('Location: ' . $pagina_login);
    exit;
}
$id_cliente = (int)$id_cliente;

function obter_carrinho(mysqli $conn, int $id_cliente): int {
    $stmt = $conn->prepare("SELECT id FROM carrinho WHERE fk_cliente = ? LIMIT 1");
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        return (int)$row['id'];
    }

    $stmt = $conn->prepare("INSERT IGNORE INTO cliente (id_usuario) VALUES (?)");
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO carrinho (fk_cliente) VALUES (?)");
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $id = $conn->insert_id;
    $stmt->close();
    return (int)$id;
}

function listar_categorias(mysqli $conn, int $id_carrinho): array {
    $stmt = $conn->prepare("SELECT nome FROM categoria_carrinho WHERE fk_carrinho = ? ORDER BY nome ASC");
    $stmt->bind_param("i", $id_carrinho);
    $stmt->execute();
    $res = $stmt->get_result();
    $nomes = [];
    while ($r = $res->fetch_assoc()) {
        $nomes[] = $r['nome'];
    }
    $stmt->close();
    return $nomes;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'adicionar') {
        $produto_id  = (int)($_POST['produto_id'] ?? 0);
        $variacao_id = (int)($_POST['variacao_id'] ?? 0);
        $tamanho     = trim($_POST['tamanho'] ?? '');

        if ($variacao_id > 0) {
            $stmt = $conn->prepare("SELECT id, quantidade_estoque FROM variacao_produto WHERE id = ? AND fk_produto = ? LIMIT 1");
            $stmt->bind_param("ii", $variacao_id, $produto_id);
        } else {
            $stmt = $conn->prepare("SELECT id, quantidade_estoque FROM variacao_produto WHERE fk_produto = ? AND tamanho = ? LIMIT 1");
            $stmt->bind_param("is", $produto_id, $tamanho);
        }
        $stmt->execute();
        $var = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$var) {
            responder_json(['ok' => false, 'erro' => 'Tamanho indisponível para este produto.']);
        }
        if ((int)$var['quantidade_estoque'] < 1) {
            responder_json(['ok' => false, 'erro' => 'Produto sem estoque neste tamanho.']);
        }

        $id_carrinho = obter_carrinho($conn, $id_cliente);
        $id_variacao = (int)$var['id'];
        $estoque     = (int)$var['quantidade_estoque'];

        $imagem_id = (int)($_POST['imagem_id'] ?? 0);
        $fk_imagem = null;
        if ($imagem_id > 0) {
            $stmt = $conn->prepare("SELECT id FROM imagem_produto WHERE id = ? AND fk_produto = ? LIMIT 1");
            $stmt->bind_param("ii", $imagem_id, $produto_id);
            $stmt->execute();
            $img = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($img) {
                $fk_imagem = (int)$img['id'];
            }
        }

        $stmt = $conn->prepare("SELECT COALESCE(SUM(quantidade), 0) AS total FROM item_carrinho WHERE fk_carrinho = ? AND fk_variacao = ?");
        $stmt->bind_param("ii", $id_carrinho, $id_variacao);
        $stmt->execute();
        $ja_no_carrinho = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        if ($ja_no_carrinho >= $estoque) {
            responder_json(['ok' => false, 'erro' => 'Você já adicionou todo o estoque disponível deste tamanho.']);
        }

        $stmt = $conn->prepare("SELECT id FROM item_carrinho WHERE fk_carrinho = ? AND fk_variacao = ? AND fk_imagem <=> ? LIMIT 1");
        $stmt->bind_param("iii", $id_carrinho, $id_variacao, $fk_imagem);
        $stmt->execute();
        $existente = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existente) {
            $item_id = (int)$existente['id'];
            $stmt = $conn->prepare("UPDATE item_carrinho SET quantidade = quantidade + 1 WHERE id = ?");
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO item_carrinho (fk_carrinho, fk_variacao, quantidade, fk_imagem) VALUES (?, ?, 1, ?)");
            $stmt->bind_param("iii", $id_carrinho, $id_variacao, $fk_imagem);
            $stmt->execute();
            $item_id = (int)$conn->insert_id;
            $stmt->close();
        }

        responder_json([
            'ok'         => true,
            'item_id'    => $item_id,
            'categorias' => listar_categorias($conn, $id_carrinho),
        ]);
    }

    if ($acao === 'categorizar') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        $nome    = trim($_POST['categoria'] ?? '');
        $nome    = mb_strtoupper(mb_substr($nome, 0, 50, 'UTF-8'), 'UTF-8');

        $stmt = $conn->prepare(
            "SELECT ic.id, c.id AS id_carrinho
             FROM item_carrinho ic
             JOIN carrinho c ON c.id = ic.fk_carrinho
             WHERE ic.id = ? AND c.fk_cliente = ? LIMIT 1"
        );
        $stmt->bind_param("ii", $item_id, $id_cliente);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$item) {
            if ($eh_ajax) {
                responder_json(['ok' => false, 'erro' => 'Item não encontrado no seu carrinho.']);
            }
            header('Location: carrinho.php');
            exit;
        }
        $id_carrinho = (int)$item['id_carrinho'];

        $id_categoria = null;
        if ($nome !== '') {
            $stmt = $conn->prepare("SELECT id FROM categoria_carrinho WHERE fk_carrinho = ? AND nome = ? LIMIT 1");
            $stmt->bind_param("is", $id_carrinho, $nome);
            $stmt->execute();
            $cat = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($cat) {
                $id_categoria = (int)$cat['id'];
            } else {
                $stmt = $conn->prepare("INSERT INTO categoria_carrinho (fk_carrinho, nome) VALUES (?, ?)");
                $stmt->bind_param("is", $id_carrinho, $nome);
                $stmt->execute();
                $id_categoria = (int)$conn->insert_id;
                $stmt->close();
            }
        }

        $stmt = $conn->prepare("UPDATE item_carrinho SET fk_categoria_carrinho = ? WHERE id = ?");
        $stmt->bind_param("ii", $id_categoria, $item_id);
        $stmt->execute();
        $stmt->close();

        if ($eh_ajax) {
            responder_json(['ok' => true]);
        }
    }

    if ($acao === 'atualizar') {
        $item_id    = (int)($_POST['item_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 1);

        if ($quantidade < 1) {
            $acao = 'remover';
        } else {
            $stmt = $conn->prepare(
                "UPDATE item_carrinho ic
                 JOIN carrinho c ON c.id = ic.fk_carrinho
                 JOIN variacao_produto vp ON vp.id = ic.fk_variacao
                 SET ic.quantidade = LEAST(?, vp.quantidade_estoque)
                 WHERE ic.id = ? AND c.fk_cliente = ?"
            );
            $stmt->bind_param("iii", $quantidade, $item_id, $id_cliente);
            $stmt->execute();
            $stmt->close();
        }
    }

    if ($acao === 'remover') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        $stmt = $conn->prepare(
            "DELETE ic FROM item_carrinho ic
             JOIN carrinho c ON c.id = ic.fk_carrinho
             WHERE ic.id = ? AND c.fk_cliente = ?"
        );
        $stmt->bind_param("ii", $item_id, $id_cliente);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: carrinho.php');
    exit;
}

$sql = "SELECT ic.id AS item_id, ic.quantidade,
               vp.tamanho, vp.quantidade_estoque,
               p.id AS produto_id, p.nome,
               cc.nome AS categoria,
               (p.preco_base + vp.preco_adicional) AS preco,
               -- foto da variação escolhida; se não houver, usa a 1ª foto do produto
               COALESCE(ip.url_imagem,
                        (SELECT url_imagem FROM imagem_produto WHERE fk_produto = p.id ORDER BY ordem ASC, id ASC LIMIT 1)) AS url_imagem,
               ip.cor,
               (SELECT COUNT(*) FROM imagem_produto WHERE fk_produto = p.id) AS total_imagens,
               (SELECT COUNT(*) FROM imagem_produto i2
                 WHERE i2.fk_produto = p.id AND ip.id IS NOT NULL
                   AND (i2.ordem < ip.ordem OR (i2.ordem = ip.ordem AND i2.id <= ip.id))) AS num_variacao
        FROM item_carrinho ic
        JOIN carrinho c ON c.id = ic.fk_carrinho
        JOIN variacao_produto vp ON vp.id = ic.fk_variacao
        JOIN produto p ON p.id = vp.fk_produto
        LEFT JOIN imagem_produto ip ON ip.id = ic.fk_imagem
        LEFT JOIN categoria_carrinho cc ON cc.id = ic.fk_categoria_carrinho
        WHERE c.fk_cliente = ?
        ORDER BY (cc.nome IS NULL), cc.nome ASC, ic.id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$grupos = [];
$total = 0;
foreach ($itens as $i) {
    $chave = $i['categoria'] ?? 'SEM CATEGORIA';
    $grupos[$chave][] = $i;
    $total += $i['preco'] * $i['quantidade'];
}

$opcoes_categoria = $categorias_padrao;
$id_carrinho_atual = obter_carrinho($conn, $id_cliente);
foreach (listar_categorias($conn, $id_carrinho_atual) as $nome_cat) {
    if (!in_array($nome_cat, $opcoes_categoria, true)) {
        $opcoes_categoria[] = $nome_cat;
    }
}

$imagem_padrao = '../../images/uploads/CICRO.png';

function caminho_imagem($url) {
    global $imagem_padrao;
    if (!empty($url)) {
        return '../../' . ltrim($url, './');
    }
    return $imagem_padrao;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Carrinho</title>
  <link rel="stylesheet" href="../../css/style3.css">
  <link rel="stylesheet" href="../../css/header.css">
</head>
<body>

  <header class="site-header">
    <a class="site-header__logo" href="index.php">CICRO</a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <li><a href="index.php">Início</a></li>
        <li><a href="catalogo.php">Catálogo</a></li>
        <li><a href="suporte.php">Suporte</a></li>
        <?php if (isset($_SESSION["usuario_id"])): ?><li><a href="meus_pedidos.php">Meus pedidos</a></li><?php endif; ?>
        <li><?php if (isset($_SESSION['usuario_id'])): ?><a href="perfil.php">Meu Perfil</a><?php else: ?><a href="login.php">Entrar</a><?php endif; ?></li>
        <li><a href="carrinho.php" class="is-active site-header__cta">Carrinho</a></li>
      </ul>
    </nav>
  </header>

  <main class="carrinho-page">

    <div class="carrinho-topo">
      <div>
        <span class="carrinho-kicker">CICRO / CARRINHO</span>
        <h2>Seu carrinho</h2>
        <?php if (!empty($itens)): ?>
          <p><?php echo count($itens); ?> item(ns) no carrinho</p>
        <?php endif; ?>
      </div>

      <a href="catalogo.php" class="continuar-topo">
        ← Continuar comprando
      </a>
    </div>

    <?php if (empty($itens)): ?>

      <section class="carrinho-vazio">
        <div class="vazio-icone">🛍</div>
        <h3>Seu carrinho está vazio</h3>
        <p>Você ainda não adicionou nenhum produto.</p>
        <a href="catalogo.php" class="btn-principal">Ver produtos</a>
      </section>

    <?php else: ?>

      <div class="carrinho-layout">

        <section class="lista-carrinho">

          <?php foreach ($grupos as $nome_grupo => $itens_grupo): ?>

            <div class="grupo-carrinho">
              <div class="grupo-cabecalho">
                <h3><?php echo htmlspecialchars($nome_grupo); ?></h3>
                <span>
                  <?php echo count($itens_grupo); ?>
                  <?php echo count($itens_grupo) === 1 ? 'item' : 'itens'; ?>
                </span>
              </div>

              <div class="grupo-itens">

                <?php foreach ($itens_grupo as $item): ?>

                  <article class="item-carrinho">

                    <label class="item-selecionar" title="Comprar este item agora">
                      <input type="checkbox" class="sel-item" name="sel[]" value="<?php echo (int)$item['item_id']; ?>"
                             form="form-finalizar" data-valor="<?php echo number_format($item['preco'] * $item['quantidade'], 2, '.', ''); ?>" checked>
                    </label>

                    <div class="item-foto">
                      <img
                        src="<?php echo htmlspecialchars(caminho_imagem($item['url_imagem'])); ?>"
                        alt="<?php echo htmlspecialchars($item['nome']); ?>"
                        onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imagem_padrao); ?>';"
                      >

                      <?php if ((int)$item['total_imagens'] > 1 && (int)$item['num_variacao'] > 0): ?>
                        <span class="item-foto-num">
                          <?php echo (int)$item['num_variacao']; ?>
                        </span>
                      <?php endif; ?>
                    </div>

                    <div class="item-info">

                      <div class="item-principal">
                        <div>
                          <h3><?php echo htmlspecialchars($item['nome']); ?></h3>

                          <div class="item-detalhes">
                            <span>
                              Tamanho:
                              <strong><?php echo htmlspecialchars($item['tamanho']); ?></strong>
                            </span>

                            <?php if (!empty($item['cor'])): ?>
                              <span class="separador">•</span>
                              <span>Cor: <strong><?php echo htmlspecialchars($item['cor']); ?></strong></span>
                            <?php endif; ?>

                            <span class="separador">•</span>

                            <span>
                              R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?> cada
                            </span>
                          </div>
                        </div>

                        <form
                          method="POST"
                          class="form-remover"
                          onsubmit="return confirm('Remover este item do carrinho?');"
                        >
                          <input type="hidden" name="acao" value="remover">
                          <input type="hidden" name="item_id" value="<?php echo (int)$item['item_id']; ?>">

                          <button type="submit" class="btn-remover" title="Remover produto">
                            <span>×</span>
                          </button>
                        </form>
                      </div>

                      <div class="item-parte-inferior">

                        <form method="POST" class="form-categoria">
                          <input type="hidden" name="acao" value="categorizar">
                          <input type="hidden" name="item_id" value="<?php echo (int)$item['item_id']; ?>">

                          <label for="categoria-<?php echo (int)$item['item_id']; ?>">
                            Categoria
                          </label>

                          <select
                            id="categoria-<?php echo (int)$item['item_id']; ?>"
                            name="categoria"
                            onchange="this.form.submit()"
                          >
                            <option value="">Sem categoria</option>

                            <?php foreach ($opcoes_categoria as $op): ?>
                              <option
                                value="<?php echo htmlspecialchars($op); ?>"
                                <?php echo ($item['categoria'] === $op) ? 'selected' : ''; ?>
                              >
                                <?php echo htmlspecialchars($op); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </form>

                        <div class="item-acoes">

                          <div class="controle-qtd">

                            <form method="POST">
                              <input type="hidden" name="acao" value="atualizar">
                              <input type="hidden" name="item_id" value="<?php echo (int)$item['item_id']; ?>">
                              <input type="hidden" name="quantidade" value="<?php echo (int)$item['quantidade'] - 1; ?>">

                              <button
                                type="submit"
                                title="Diminuir quantidade"
                                <?php echo ((int)$item['quantidade'] <= 1) ? 'disabled' : ''; ?>
                              >
                                −
                              </button>
                            </form>

                            <span><?php echo (int)$item['quantidade']; ?></span>

                            <form method="POST">
                              <input type="hidden" name="acao" value="atualizar">
                              <input type="hidden" name="item_id" value="<?php echo (int)$item['item_id']; ?>">
                              <input type="hidden" name="quantidade" value="<?php echo (int)$item['quantidade'] + 1; ?>">

                              <button
                                type="submit"
                                title="Aumentar quantidade"
                                <?php echo ($item['quantidade'] >= $item['quantidade_estoque']) ? 'disabled' : ''; ?>
                              >
                                +
                              </button>
                            </form>

                          </div>

                          <div class="item-preco">
                            <span>Subtotal</span>
                            <strong>
                              R$ <?php echo number_format($item['preco'] * $item['quantidade'], 2, ',', '.'); ?>
                            </strong>
                          </div>

                        </div>

                      </div>

                    </div>

                  </article>

                <?php endforeach; ?>

              </div>
            </div>

          <?php endforeach; ?>

        </section>

        <aside class="resumo-pedido">

          <div class="resumo-cabecalho">
            <span>RESUMO</span>
            <h3>Seu pedido</h3>
          </div>

          <label class="sel-todos"><input type="checkbox" id="sel-todos" checked> Selecionar todos</label>

          <div class="resumo-linha">
            <span>Itens selecionados</span>
            <span id="res-qtd"><?php echo count($itens); ?></span>
          </div>

          <div class="resumo-linha">
            <span>Subtotal</span>
            <span id="res-subtotal">R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
          </div>

          <div class="resumo-divisor"></div>

          <div class="resumo-total">
            <span>Total</span>
            <strong id="res-total">R$ <?php echo number_format($total, 2, ',', '.'); ?></strong>
          </div>

          <form method="POST" action="checkout.php" id="form-finalizar">
            <button type="submit" class="btn-finalizar" id="btn-finalizar" style="border:0;width:100%;cursor:pointer;font:inherit">
              Finalizar compra
              <span>→</span>
            </button>
          </form>
          <p id="res-aviso" style="display:none;margin:8px 0 0;font-size:.85rem;color:#b00020">Marque pelo menos um item para comprar.</p>

          <a href="catalogo.php" class="btn-continuar">
            Continuar comprando
          </a>

          <p class="resumo-seguranca">
            Compra segura · CICRO
          </p>

        </aside>

      </div>

    <?php endif; ?>

  </main>

  <script>
    (function () {
      var itens = Array.prototype.slice.call(document.querySelectorAll('.sel-item'));
      if (!itens.length) { return; }
      var todos = document.getElementById('sel-todos'), btn = document.getElementById('btn-finalizar');
      function brl(v) { return 'R$ ' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
      function atualizar() {
        var n = 0, soma = 0;
        itens.forEach(function (c) { if (c.checked) { n++; soma += parseFloat(c.dataset.valor); } });
        document.getElementById('res-qtd').textContent = n;
        document.getElementById('res-subtotal').textContent = brl(soma);
        document.getElementById('res-total').textContent = brl(soma);
        todos.checked = n === itens.length;
        btn.disabled = n === 0;
        btn.style.opacity = n === 0 ? '.5' : '1';
        document.getElementById('res-aviso').style.display = n === 0 ? 'block' : 'none';
      }
      itens.forEach(function (c) { c.addEventListener('change', atualizar); });
      todos.addEventListener('change', function () { itens.forEach(function (c) { c.checked = todos.checked; }); atualizar(); });
      atualizar();
    })();
  </script>

  <footer>
    <p>&copy; 2026 CICRO - Todos os direitos reservados</p>
  </footer>

</body>
</html>