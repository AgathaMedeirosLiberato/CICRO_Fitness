<?php
session_start();
require_once __DIR__ . '/../../config/conexao.php';

$imagem_padrao = '../../images/uploads/CICRO.png';

function caminho_imagem_web($url) {
    return '../../' . ltrim($url, './');
}

$slug_categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

function esc($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function ler_preco($valor) {
    $valor = str_replace(',', '.', trim((string)$valor));
    if ($valor === '' || !is_numeric($valor) || (float)$valor < 0) {
        return null;
    }
    return round((float)$valor, 2);
}

$tamanhos_filtro = [];
if (isset($_GET['tamanhos']) && is_array($_GET['tamanhos'])) {
    foreach ($_GET['tamanhos'] as $t) {
        $t = strtoupper(trim((string)$t));
        if ($t !== '' && strlen($t) <= 10) {
            $tamanhos_filtro[$t] = $t;
        }
    }
    $tamanhos_filtro = array_values($tamanhos_filtro);
}

$preco_min = ler_preco($_GET['preco_min'] ?? '');
$preco_max = ler_preco($_GET['preco_max'] ?? '');
if ($preco_min !== null && $preco_max !== null && $preco_min > $preco_max) {
    [$preco_min, $preco_max] = [$preco_max, $preco_min];
}

$filtros_ativos = count($tamanhos_filtro) + ($preco_min !== null ? 1 : 0) + ($preco_max !== null ? 1 : 0);

$params_filtro = [];
if ($tamanhos_filtro) { $params_filtro['tamanhos'] = $tamanhos_filtro; }
if ($preco_min !== null) { $params_filtro['preco_min'] = $preco_min; }
if ($preco_max !== null) { $params_filtro['preco_max'] = $preco_max; }

$categorias_menu = [];
$resCats = $conn->query("SELECT nome, slug FROM categoria WHERE status = 1 ORDER BY ordem_exibicao ASC, nome ASC");
if ($resCats) {
    while ($c = $resCats->fetch_assoc()) { $categorias_menu[] = $c; }
}

function link_categoria($slug, array $params_filtro) {
    $q = $params_filtro;
    if ($slug !== '') {
        $q = ['categoria' => $slug] + $q;
    }
    return 'catalogo.php' . ($q ? '?' . http_build_query($q) : '');
}

$sql = "SELECT p.id, p.nome, p.descricao, p.preco_base
        FROM produto p
        INNER JOIN categoria c ON c.id = p.fk_categoria
        WHERE p.status = 1 AND c.status = 1";

$tipos = '';
$parametros = [];

if ($slug_categoria !== '') {
    $sql .= " AND c.slug = ?";
    $tipos .= 's';
    $parametros[] = $slug_categoria;
}

if ($busca !== '') {
    $sql .= " AND (p.nome LIKE ? OR p.descricao LIKE ?)";
    $termo = '%' . addcslashes($busca, '%_\\') . '%';
    $tipos .= 'ss';
    $parametros[] = $termo;
    $parametros[] = $termo;
}

if (!empty($tamanhos_filtro)) {
    $marcas = implode(',', array_fill(0, count($tamanhos_filtro), '?'));
    $sql .= " AND EXISTS (SELECT 1 FROM variacao_produto v
                          WHERE v.fk_produto = p.id AND v.quantidade_estoque > 0 AND v.tamanho IN ($marcas))";
    $tipos .= str_repeat('s', count($tamanhos_filtro));
    $parametros = array_merge($parametros, $tamanhos_filtro);
}

if ($preco_min !== null) {
    $sql .= " AND p.preco_base >= ?";
    $tipos .= 'd';
    $parametros[] = $preco_min;
}
if ($preco_max !== null) {
    $sql .= " AND p.preco_base <= ?";
    $tipos .= 'd';
    $parametros[] = $preco_max;
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($sql);
if ($tipos !== '') {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$lista = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$produtos_js = [];
foreach ($lista as $p) {
    $produtos_js[(int)$p['id']] = [
        'id'        => (int)$p['id'],
        'nome'      => $p['nome'],
        'descricao' => $p['descricao'] ?? '',
        'preco'     => (float)$p['preco_base'],
        'imagens'   => [],
        'tamanhos'  => [],
    ];
}

if (!empty($produtos_js)) {
    $ids = implode(',', array_map('intval', array_keys($produtos_js)));

    $resImg = $conn->query("SELECT id, fk_produto, url_imagem, cor FROM imagem_produto
                            WHERE fk_produto IN ($ids) ORDER BY fk_produto, ordem ASC, id ASC");
    if ($resImg) {
        while ($r = $resImg->fetch_assoc()) {
            $produtos_js[(int)$r['fk_produto']]['imagens'][] = [
                'id'  => (int)$r['id'],
                'url' => caminho_imagem_web($r['url_imagem']),
                'cor' => (string)$r['cor'],
            ];
        }
    }

    $resAv = $conn->query("SELECT a.fk_produto, a.nota, a.comentario, a.data_avaliacao, u.nome
                           FROM avaliacao a JOIN usuario u ON u.id = a.fk_cliente
                           WHERE a.fk_produto IN ($ids) ORDER BY a.data_avaliacao DESC, a.id DESC");
    if ($resAv) {
        while ($r = $resAv->fetch_assoc()) {
            $pid = (int)$r['fk_produto'];
            $a = &$produtos_js[$pid];
            $a['aval_soma'] = ($a['aval_soma'] ?? 0) + (int)$r['nota'];
            $a['aval_n']    = ($a['aval_n'] ?? 0) + 1;
            if (count($a['avaliacoes'] ?? []) < 5) {
                $a['avaliacoes'][] = [
                    'nome' => explode(' ', trim($r['nome']))[0],
                    'nota' => (int)$r['nota'],
                    'texto' => (string)$r['comentario'],
                    'data' => date('d/m/Y', strtotime($r['data_avaliacao'])),
                ];
            }
            unset($a);
        }
        foreach ($produtos_js as $pid => &$pj) {
            $pj['aval_media'] = !empty($pj['aval_n']) ? round($pj['aval_soma'] / $pj['aval_n'], 1) : null;
            unset($pj['aval_soma']);
        }
        unset($pj);
    }

    $resVar = $conn->query("SELECT id, fk_produto, tamanho, preco_adicional, quantidade_estoque
                            FROM variacao_produto WHERE fk_produto IN ($ids) ORDER BY fk_produto, id ASC");
    $tamanhos_por_produto = [];
    if ($resVar) {
        while ($r = $resVar->fetch_assoc()) {
            $id_produto = (int)$r['fk_produto'];
            $tam = $r['tamanho'];
            if (isset($tamanhos_por_produto[$id_produto][$tam])) {
                continue;
            }
            $tamanhos_por_produto[$id_produto][$tam] = [
                'id'        => (int)$r['id'],
                'tamanho'   => $tam,
                'estoque'   => (int)$r['quantidade_estoque'],
                'adicional' => (float)$r['preco_adicional'],
            ];
        }
    }

    $ordem_tamanhos = ['PP' => 1, 'P' => 2, 'M' => 3, 'G' => 4, 'GG' => 5, 'XG' => 6];
    foreach ($tamanhos_por_produto as $id_produto => $tamanhos) {
        $tamanhos = array_values($tamanhos);
        usort($tamanhos, function ($a, $b) use ($ordem_tamanhos) {
            $oa = $ordem_tamanhos[$a['tamanho']] ?? 99;
            $ob = $ordem_tamanhos[$b['tamanho']] ?? 99;
            return $oa <=> $ob;
        });
        $produtos_js[$id_produto]['tamanhos'] = $tamanhos;
    }
}

$ordem_tam_filtro = ['PP' => 1, 'P' => 2, 'M' => 3, 'G' => 4, 'GG' => 5, 'XG' => 6];
$tamanhos_opcoes = [];
$resTamDisp = $conn->query("SELECT DISTINCT v.tamanho FROM variacao_produto v
                            INNER JOIN produto p ON p.id = v.fk_produto
                            WHERE p.status = 1 AND v.quantidade_estoque > 0");
if ($resTamDisp) {
    while ($r = $resTamDisp->fetch_assoc()) {
        $tamanhos_opcoes[strtoupper($r['tamanho'])] = strtoupper($r['tamanho']);
    }
}
foreach ($tamanhos_filtro as $t) {
    $tamanhos_opcoes[$t] = $t;
}
$tamanhos_opcoes = array_values($tamanhos_opcoes);
usort($tamanhos_opcoes, function ($a, $b) use ($ordem_tam_filtro) {
    return ($ordem_tam_filtro[$a] ?? 99) <=> ($ordem_tam_filtro[$b] ?? 99) ?: strcmp($a, $b);
});

$faixa_preco = ['min' => null, 'max' => null];
$resFaixa = $conn->query("SELECT MIN(preco_base) AS mn, MAX(preco_base) AS mx FROM produto WHERE status = 1");
if ($resFaixa && ($f = $resFaixa->fetch_assoc())) {
    $faixa_preco = ['min' => $f['mn'], 'max' => $f['mx']];
}

$json_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO - Catálogo</title>
  <link rel="stylesheet" href="../../css/style3.css">
  <link rel="stylesheet" href="../../css/header.css">
</head>
<body>

  <header class="site-header">
    <a class="site-header__logo" href="index.php">CICRO</a>
    <form class="busca" action="catalogo.php" method="GET" role="search">
          <input type="text" name="busca" placeholder="Buscar produtos..." aria-label="Buscar produtos"
                 value="<?php echo htmlspecialchars($busca); ?>" enterkeyhint="search" autocomplete="off">
        </form>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <li><a href="index.php">Início</a></li>
        <li><a href="catalogo.php" class="is-active">Catálogo</a></li>
        <li><a href="suporte.php">Suporte</a></li>
        <?php if (isset($_SESSION["usuario_id"])): ?><li><a href="meus_pedidos.php">Meus pedidos</a></li><?php endif; ?>
        <li><?php if (isset($_SESSION['usuario_id'])): ?><a href="perfil.php">Meu Perfil</a><?php else: ?><a href="login.php">Entrar</a><?php endif; ?></li>
        <li><a href="carrinho.php" class="site-header__cta">Carrinho</a></li>
      </ul>
    </nav>
  </header>

  <nav class="nav-categorias" aria-label="Categorias">
    <ul>
      <?php foreach ($categorias_menu as $cat): ?>
        <li><a href="<?php echo esc(link_categoria($cat['slug'], $params_filtro)); ?>" class="<?php echo $slug_categoria === $cat['slug'] ? 'ativo' : ''; ?>"><?php echo esc($cat['nome']); ?></a></li>
      <?php endforeach; ?>
      <li><a href="<?php echo esc(link_categoria('', $params_filtro)); ?>" class="<?php echo $slug_categoria === '' ? 'ativo' : ''; ?>">Todos</a></li>
    </ul>
  </nav>

  <!-- ================= Filtros: tamanho e preço ================= -->
  <form class="filtros" method="GET" action="catalogo.php" aria-label="Filtrar produtos">
    <details class="filtros-caixa" <?php echo $filtros_ativos > 0 ? 'open' : ''; ?>>
      <summary>
        Filtrar
        <?php if ($filtros_ativos > 0): ?><span class="filtros-contagem"><?php echo $filtros_ativos; ?></span><?php endif; ?>
      </summary>

      <div class="filtros-corpo">
        <?php if ($slug_categoria !== ''): ?>
          <input type="hidden" name="categoria" value="<?php echo esc($slug_categoria); ?>">
        <?php endif; ?>
        <?php if ($busca !== ''): ?>
          <input type="hidden" name="busca" value="<?php echo esc($busca); ?>">
        <?php endif; ?>

        <?php if (!empty($tamanhos_opcoes)): ?>
        <fieldset class="filtro-grupo">
          <legend>Tamanho</legend>
          <div class="filtro-tamanhos">
            <?php foreach ($tamanhos_opcoes as $t): ?>
              <label class="filtro-pilula">
                <input type="checkbox" name="tamanhos[]" value="<?php echo esc($t); ?>"
                       <?php echo in_array($t, $tamanhos_filtro, true) ? 'checked' : ''; ?>>
                <span><?php echo esc($t); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <?php endif; ?>

        <fieldset class="filtro-grupo">
          <legend>Preço (R$)</legend>
          <div class="filtro-preco">
            <input type="number" name="preco_min" min="0" step="0.01" inputmode="decimal" aria-label="Preço mínimo"
                   placeholder="<?php echo $faixa_preco['min'] !== null ? 'mín. ' . number_format((float)$faixa_preco['min'], 2, ',', '.') : 'mínimo'; ?>"
                   value="<?php echo $preco_min !== null ? number_format($preco_min, 2, '.', '') : ''; ?>">
            <span>até</span>
            <input type="number" name="preco_max" min="0" step="0.01" inputmode="decimal" aria-label="Preço máximo"
                   placeholder="<?php echo $faixa_preco['max'] !== null ? 'máx. ' . number_format((float)$faixa_preco['max'], 2, ',', '.') : 'máximo'; ?>"
                   value="<?php echo $preco_max !== null ? number_format($preco_max, 2, '.', '') : ''; ?>">
          </div>
        </fieldset>

        <div class="filtro-acoes">
          <button type="submit" class="filtro-aplicar">Aplicar</button>
          <?php
            $limpar = [];
            if ($slug_categoria !== '') { $limpar['categoria'] = $slug_categoria; }
            if ($busca !== '') { $limpar['busca'] = $busca; }
          ?>
          <a class="filtro-limpar" href="catalogo.php<?php echo $limpar ? '?' . esc(http_build_query($limpar)) : ''; ?>">Limpar filtros</a>
        </div>
      </div>
    </details>
  </form>

  <main class="catalogo">
  <?php if (!empty($lista)): ?>
    <?php if ($busca !== '' || $filtros_ativos > 0): ?>
      <p class="resultado-busca">
        <?php echo count($lista); ?> resultado(s)<?php if ($busca !== ''): ?> para “<?php echo htmlspecialchars($busca); ?>”<?php endif; ?>.
        <a href="catalogo.php">Limpar tudo</a>
      </p>
    <?php endif; ?>
    <?php foreach ($lista as $prod): ?>
      <?php
        $id_prod = (int)$prod['id'];
        $imagens_prod = $produtos_js[$id_prod]['imagens'];
        $caminho_imagem = !empty($imagens_prod) ? $imagens_prod[0]['url'] : $imagem_padrao;
      ?>

      <div class="produto" data-id="<?php echo $id_prod; ?>" tabindex="0" role="button"
           aria-label="Ver detalhes de <?php echo htmlspecialchars($prod['nome']); ?>">
        <div class="img-wrapper">
          <img src="<?php echo htmlspecialchars($caminho_imagem); ?>"
               alt="<?php echo htmlspecialchars($prod['nome']); ?>"
               onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imagem_padrao); ?>';">
        </div>
        <h2><?php echo htmlspecialchars($prod['nome']); ?></h2>
        <p>R$ <?php echo number_format($prod['preco_base'], 2, ',', '.'); ?></p>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <?php if ($busca !== '' || $filtros_ativos > 0): ?>
      <p style="text-align: center; grid-column: 1/-1;">
        Nenhum produto encontrado<?php if ($busca !== ''): ?> para “<?php echo htmlspecialchars($busca); ?>”<?php endif; ?><?php if ($filtros_ativos > 0): ?> com esses filtros<?php endif; ?>.
        <a href="catalogo.php">Ver todos os produtos</a>
      </p>
    <?php else: ?>
      <p style="text-align: center; grid-column: 1/-1;">Nenhum produto cadastrado no momento.</p>
    <?php endif; ?>
  <?php endif; ?>
  </main>

  <!-- ================= Janela de detalhes do produto ================= -->
  <div class="modal-fundo" id="modal-produto" hidden>
    <div class="modal-produto" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
      <button type="button" class="modal-fechar" id="modal-fechar" aria-label="Fechar">✕</button>

      <!-- Galeria de imagens -->
      <div class="galeria">
        <div class="galeria-principal">
          <img id="galeria-img" src="" alt="">
          <button type="button" class="galeria-seta galeria-anterior" id="galeria-anterior" aria-label="Imagem anterior">&#10094;</button>
          <button type="button" class="galeria-seta galeria-proximo" id="galeria-proximo" aria-label="Próxima imagem">&#10095;</button>
          <span class="galeria-contador" id="galeria-contador"></span>
        </div>
        <div class="galeria-miniaturas" id="galeria-miniaturas"></div>
      </div>

      <!-- Informações e opções -->
      <div class="detalhes">
        <div class="detalhes-passo-1" id="passo-detalhes">
          <h2 id="modal-titulo"></h2>
          <p class="modal-preco" id="modal-preco"></p>
          <p class="modal-descricao" id="modal-descricao"></p>
          <div class="modal-avaliacoes" id="modal-avaliacoes" hidden></div>

          <div class="bloco-opcao" id="bloco-variacao" hidden>
            <span class="rotulo" id="rotulo-variacao">Variação</span>
            <div class="opcoes-variacao" id="opcoes-variacao"></div>
          </div>

          <div class="bloco-opcao">
            <span class="rotulo">Tamanho</span>
            <div class="grupo-selecao" id="opcoes-tamanho"></div>
          </div>

          <button type="button" class="btn-adicionar" id="btn-adicionar">ADICIONAR AO CARRINHO</button>
          <button type="button" class="btn-comprar" id="btn-comprar">COMPRAR</button>
        </div>

        <div class="detalhes-passo-2" id="passo-categorias" hidden>
          <p class="aviso-adicionado">✓ Produto adicionado ao carrinho!</p>
          <h3>QUER ORGANIZAR EM UMA CATEGORIA? <small>(opcional)</small></h3>
          <div class="lista-categorias" id="lista-categorias">
            <button type="button" class="btn-cat" data-cat="CONJUNTOS">CONJUNTOS</button>
            <button type="button" class="btn-cat" data-cat="REGATAS">REGATAS</button>
            <button type="button" class="btn-cat" data-cat="SHORTS">SHORTS</button>
            <button type="button" class="btn-cat" data-cat="CAMISAS">CAMISAS</button>
          </div>
          <button type="button" class="btn-criar-categoria" id="btn-criar-categoria">+ CRIAR CATEGORIA</button>
          <!-- sem categoria: o item já está no carrinho, só fecha a janela -->
          <button type="button" class="btn-mandar-carrinho" id="btn-mandar-carrinho">MANDAR PARA O CARRINHO</button>
          <a class="link-ver-carrinho" href="carrinho.php">Ir para o carrinho →</a>
        </div>
      </div>
    </div>
  </div>

  <footer>
    <p>&copy; 2026 CICRO - Todos os direitos reservados</p>
  </footer>

  <script>
    const PRODUTOS = <?php echo json_encode($produtos_js, $json_flags); ?>;
    const IMAGEM_PADRAO = <?php echo json_encode($imagem_padrao, $json_flags); ?>;
    const CATEGORIAS_PADRAO = ['CONJUNTOS', 'REGATAS', 'SHORTS', 'CAMISAS'];

    const el = {
      fundo: document.getElementById('modal-produto'),
      fechar: document.getElementById('modal-fechar'),
      img: document.getElementById('galeria-img'),
      anterior: document.getElementById('galeria-anterior'),
      proximo: document.getElementById('galeria-proximo'),
      contador: document.getElementById('galeria-contador'),
      miniaturas: document.getElementById('galeria-miniaturas'),
      titulo: document.getElementById('modal-titulo'),
      preco: document.getElementById('modal-preco'),
      descricao: document.getElementById('modal-descricao'),
      avaliacoes: document.getElementById('modal-avaliacoes'),
      blocoVariacao: document.getElementById('bloco-variacao'),
      opcoesVariacao: document.getElementById('opcoes-variacao'),
      opcoesTamanho: document.getElementById('opcoes-tamanho'),
      btnAdicionar: document.getElementById('btn-adicionar'),
      passoDetalhes: document.getElementById('passo-detalhes'),
      passoCategorias: document.getElementById('passo-categorias'),
      listaCategorias: document.getElementById('lista-categorias'),
      btnCriarCategoria: document.getElementById('btn-criar-categoria'),
      btnMandarCarrinho: document.getElementById('btn-mandar-carrinho'),
      btnComprar: document.getElementById('btn-comprar')
    };

    let estado = null;
    let focoAnterior = null;

    function formatarPreco(valor) {
      return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function aplicarFallback(img) {
      img.addEventListener('error', function () {
        if (!img.src.endsWith('uploads/CICRO.png')) {
          img.src = IMAGEM_PADRAO;
        }
      });
    }
    aplicarFallback(el.img);

    function renderAvaliacoes(produto) {
      const box = el.avaliacoes;
      box.innerHTML = '';
      if (!produto.aval_n) { box.hidden = true; return; }
      const resumo = document.createElement('p');
      resumo.className = 'aval-resumo';
      const est = document.createElement('span');
      est.className = 'aval-estrelas';
      est.textContent = '★'.repeat(Math.round(produto.aval_media)) + '☆'.repeat(5 - Math.round(produto.aval_media));
      resumo.append(est, ' ' + String(produto.aval_media).replace('.', ',') + ' · ' + produto.aval_n + (produto.aval_n === 1 ? ' avaliação' : ' avaliações'));
      box.appendChild(resumo);
      (produto.avaliacoes || []).forEach(a => {
        if (!a.texto) return;
        const item = document.createElement('div');
        item.className = 'aval-item';
        const topo = document.createElement('strong');
        topo.textContent = a.nome + ' · ' + '★'.repeat(a.nota) + ' · ' + a.data;
        const txt = document.createElement('p');
        txt.textContent = a.texto;
        item.append(topo, txt);
        box.appendChild(item);
      });
      box.hidden = false;
    }

    function abrirProduto(id) {
      const produto = PRODUTOS[id];
      if (!produto) return;

      focoAnterior = document.activeElement;
      estado = { produto: produto, imagem: 0, tamanho: null, itemId: null };

      el.titulo.textContent = produto.nome;
      const descricao = (produto.descricao || '').trim();
      el.descricao.textContent = descricao;
      el.descricao.hidden = (descricao === '');
      renderAvaliacoes(produto);

      el.passoDetalhes.hidden = false;
      el.passoCategorias.hidden = true;
      el.listaCategorias.querySelectorAll('.btn-cat-custom').forEach(b => b.remove());

      renderMiniaturas();
      renderVariacoes();
      renderTamanhos();
      mostrarImagem(0);

      el.fundo.hidden = false;
      document.body.classList.add('modal-aberto');
      el.fechar.focus();
    }

    function fecharProduto() {
      el.fundo.hidden = true;
      document.body.classList.remove('modal-aberto');
      estado = null;
      if (focoAnterior && typeof focoAnterior.focus === 'function') {
        focoAnterior.focus();
      }
    }

    function renderMiniaturas() {
      const imagens = estado.produto.imagens;
      el.miniaturas.innerHTML = '';
      el.miniaturas.hidden = imagens.length < 2;

      imagens.forEach(function (imagem, i) {
        const botao = document.createElement('button');
        botao.type = 'button';
        botao.className = 'miniatura';
        botao.setAttribute('aria-label', 'Ver imagem ' + (i + 1));

        const img = document.createElement('img');
        img.src = imagem.url;
        img.alt = '';
        aplicarFallback(img);

        botao.appendChild(img);
        botao.addEventListener('click', function () { mostrarImagem(i); });
        el.miniaturas.appendChild(botao);
      });
    }

    function mostrarImagem(indice) {
      const imagens = estado.produto.imagens;
      const total = imagens.length;

      indice = total > 0 ? ((indice % total) + total) % total : 0;
      estado.imagem = indice;

      el.img.src = total > 0 ? imagens[indice].url : IMAGEM_PADRAO;
      const cor = total > 0 ? imagens[indice].cor : '';
      el.img.alt = estado.produto.nome + (cor ? ' - ' + cor : (total > 1 ? ' - variação ' + (indice + 1) : ''));

      const varias = total > 1;
      el.anterior.hidden = !varias;
      el.proximo.hidden = !varias;
      el.contador.hidden = !varias;
      el.contador.textContent = (indice + 1) + ' / ' + total;

      el.miniaturas.querySelectorAll('.miniatura').forEach(function (m, i) {
        m.classList.toggle('ativo', i === indice);
      });

      el.opcoesVariacao.querySelectorAll('.chip-variacao').forEach(function (b) {
        const i = Number(b.dataset.indice);
        const ativo = i === indice || (cor !== '' && imagens[i].cor.toLowerCase() === cor.toLowerCase());
        b.classList.toggle('ativo', ativo);
        b.setAttribute('aria-pressed', ativo ? 'true' : 'false');
      });
    }

    function primeiraDaCor(indice) {
      const imagens = estado.produto.imagens;
      const cor = imagens[indice].cor.toLowerCase();
      return cor ? imagens.findIndex(i => i.cor.toLowerCase() === cor) : indice;
    }

    function renderVariacoes() {
      const imagens = estado.produto.imagens;
      const temCor = imagens.some(i => i.cor);
      el.opcoesVariacao.innerHTML = '';
      el.blocoVariacao.hidden = imagens.length < 2 && !temCor;
      document.getElementById('rotulo-variacao').textContent = imagens.every(i => i.cor) ? 'Cor' : 'Variação';
      if (el.blocoVariacao.hidden) return;

      imagens.forEach(function (imagem, i) {
        if (primeiraDaCor(i) !== i) return;
        const botao = document.createElement('button');
        botao.type = 'button';
        botao.className = 'chip-variacao';
        botao.dataset.indice = i;
        botao.textContent = imagem.cor || String(i + 1);
        botao.setAttribute('aria-label', imagem.cor ? 'Cor ' + imagem.cor : 'Variação ' + (i + 1));
        botao.setAttribute('aria-pressed', 'false');
        botao.addEventListener('click', function () { mostrarImagem(i); });
        el.opcoesVariacao.appendChild(botao);
      });
    }

    function renderTamanhos() {
      const tamanhos = estado.produto.tamanhos;
      el.opcoesTamanho.innerHTML = '';
      estado.tamanho = null;

      if (tamanhos.length === 0) {
        const aviso = document.createElement('span');
        aviso.className = 'sem-estoque';
        aviso.textContent = 'Nenhum tamanho disponível';
        el.opcoesTamanho.appendChild(aviso);
        atualizarCompra();
        return;
      }

      const escolhido = tamanhos.find(t => t.estoque > 0) || null;

      tamanhos.forEach(function (t) {
        const botao = document.createElement('button');
        botao.type = 'button';
        botao.textContent = t.tamanho;
        botao.dataset.id = t.id;

        if (t.estoque < 1) {
          botao.disabled = true;
          botao.classList.add('esgotado');
          botao.title = 'Esgotado';
        } else {
          botao.addEventListener('click', function () { marcarTamanho(t); });
        }
        el.opcoesTamanho.appendChild(botao);
      });

      if (escolhido) {
        marcarTamanho(escolhido);
      } else {
        atualizarCompra();
      }
    }

    function marcarTamanho(t) {
      estado.tamanho = t;
      el.opcoesTamanho.querySelectorAll('button').forEach(function (b) {
        b.classList.toggle('ativo', Number(b.dataset.id) === t.id);
      });
      atualizarCompra();
    }

    function atualizarCompra() {
      const adicional = estado.tamanho ? estado.tamanho.adicional : 0;
      el.preco.textContent = formatarPreco(estado.produto.preco + adicional);

      el.btnAdicionar.disabled = !estado.tamanho;
      el.btnComprar.disabled = !estado.tamanho;
      el.btnAdicionar.textContent = estado.tamanho ? 'ADICIONAR AO CARRINHO' : 'INDISPONÍVEL';
    }

    async function adicionarAoCarrinho(comprarAgora) {
      if (!estado || !estado.tamanho) return;
      comprarAgora = comprarAgora === true;

      const dados = new FormData();
      dados.append('ajax', '1');
      dados.append('acao', 'adicionar');
      dados.append('produto_id', estado.produto.id);
      dados.append('variacao_id', estado.tamanho.id);

      const imagemEscolhida = estado.produto.imagens.length
        ? estado.produto.imagens[primeiraDaCor(estado.imagem)] : null;
      if (imagemEscolhida) {
        dados.append('imagem_id', imagemEscolhida.id);
      }

      el.btnAdicionar.disabled = true;
      el.btnComprar.disabled = true;
      try {
        const resp = await fetch('carrinho.php', { method: 'POST', body: dados });
        const json = await resp.json();

        if (!json.ok) {
          alert(json.erro);
          if (json.login) window.location.href = 'login.php';
          el.btnAdicionar.disabled = false;
          el.btnComprar.disabled = false;
          return;
        }

        if (comprarAgora) {
          window.location.href = 'checkout.php';
          return;
        }

        estado.itemId = json.item_id;

        el.listaCategorias.querySelectorAll('.btn-cat-custom').forEach(b => b.remove());
        (json.categorias || []).forEach(function (nome) {
          if (!CATEGORIAS_PADRAO.includes(nome.toUpperCase())) {
            criarBotaoCategoria(nome);
          }
        });

        el.passoDetalhes.hidden = true;
        el.passoCategorias.hidden = false;
      } catch (erro) {
        alert('Não foi possível adicionar ao carrinho. Tente novamente.');
        el.btnAdicionar.disabled = false;
        el.btnComprar.disabled = false;
      }
    }

    function criarBotaoCategoria(nome) {
      const botao = document.createElement('button');
      botao.type = 'button';
      botao.className = 'btn-cat btn-cat-custom';
      botao.textContent = nome;
      el.listaCategorias.appendChild(botao);
    }

    async function salvarCategoria(nome) {
      const dados = new FormData();
      dados.append('ajax', '1');
      dados.append('acao', 'categorizar');
      dados.append('item_id', estado.itemId);
      dados.append('categoria', nome);

      const resp = await fetch('carrinho.php', { method: 'POST', body: dados });
      return await resp.json();
    }

    async function escolherCategoria(nome) {
      try {
        const json = await salvarCategoria(nome);
        if (!json.ok) { alert(json.erro); return; }
        fecharProduto();
      } catch (erro) {
        alert('Não foi possível salvar a categoria. Tente novamente.');
      }
    }

    async function criarCategoria() {
      const nome = (prompt('Nome da nova categoria:') || '').trim();
      if (!nome) return;
      await escolherCategoria(nome);
    }

    document.querySelectorAll('.produto').forEach(function (card) {
      card.addEventListener('click', function () { abrirProduto(card.dataset.id); });
      card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          abrirProduto(card.dataset.id);
        }
      });
    });

    el.fechar.addEventListener('click', fecharProduto);
    el.fundo.addEventListener('click', function (e) {
      if (e.target === el.fundo) fecharProduto();
    });

    el.anterior.addEventListener('click', function () { mostrarImagem(estado.imagem - 1); });
    el.proximo.addEventListener('click', function () { mostrarImagem(estado.imagem + 1); });

    el.btnAdicionar.addEventListener('click', function () { adicionarAoCarrinho(false); });
    el.btnComprar.addEventListener('click', function () { adicionarAoCarrinho(true); });
    el.btnMandarCarrinho.addEventListener('click', fecharProduto);
    el.btnCriarCategoria.addEventListener('click', criarCategoria);

    el.listaCategorias.addEventListener('click', function (e) {
      const botao = e.target.closest('.btn-cat');
      if (botao) escolherCategoria(botao.textContent.trim());
    });

    document.addEventListener('keydown', function (e) {
      if (el.fundo.hidden) return;

      if (e.key === 'Escape') {
        fecharProduto();
      } else if (e.key === 'ArrowLeft' && !el.passoDetalhes.hidden && estado.produto.imagens.length > 1) {
        mostrarImagem(estado.imagem - 1);
      } else if (e.key === 'ArrowRight' && !el.passoDetalhes.hidden && estado.produto.imagens.length > 1) {
        mostrarImagem(estado.imagem + 1);
      } else if (e.key === 'Tab') {
        const focaveis = Array.from(el.fundo.querySelectorAll('button:not([disabled])'))
          .filter(n => !n.closest('[hidden]'));
        if (focaveis.length === 0) return;

        const primeiro = focaveis[0];
        const ultimo = focaveis[focaveis.length - 1];
        if (e.shiftKey && document.activeElement === primeiro) {
          e.preventDefault();
          ultimo.focus();
        } else if (!e.shiftKey && document.activeElement === ultimo) {
          e.preventDefault();
          primeiro.focus();
        }
      }
    });
  </script>

</body>
</html>