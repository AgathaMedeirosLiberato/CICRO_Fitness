<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../models/pedido_funcoes.php';

$imagem_padrao = '../../images/uploads/CICRO.png';

function achar_banner(string $nome): ?string
{
    foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $ext) {
        if (is_file(__DIR__ . '/../../images/banners/' . $nome . '.' . $ext)) {
            return '../../images/banners/' . $nome . '.' . $ext;
        }
    }
    return null;
}

$banners_principais = [
    ['arquivo' => 'principal-1', 'rotulo' => 'Banner principal 1', 'link' => 'catalogo.php', 'alt' => 'Treine com estilo'],
    ['arquivo' => 'principal-2', 'rotulo' => 'Banner principal 2', 'link' => 'catalogo.php', 'alt' => 'Beleza e conforto em uma só peça'],
    ['arquivo' => 'principal-3', 'rotulo' => 'Banner principal 3', 'link' => 'catalogo.php', 'alt' => 'Poder e elegância'],
];
$banners_promo = [
    ['arquivo' => 'promo-1', 'rotulo' => 'Banner promocional 1', 'link' => 'catalogo.php?busca=Conjunto+Dani+Short', 'alt' => 'Conjunto Dani Short por R$ 50,00'],
    ['arquivo' => 'promo-2', 'rotulo' => 'Banner promocional 2', 'link' => 'catalogo.php?busca=Casaco+Jacard+Life', 'alt' => 'Casaco Jacard Life por R$ 65,00'],
];
foreach ($banners_principais as &$b) { $b['img'] = achar_banner($b['arquivo']); }
unset($b);
foreach ($banners_promo as &$b) { $b['img'] = achar_banner($b['arquivo']); }
unset($b);

$sql_novos = "SELECT p.id, p.nome, p.preco_base,
                     (SELECT url_imagem FROM imagem_produto
                       WHERE fk_produto = p.id
                       ORDER BY capa DESC, ordem ASC, id ASC LIMIT 1) AS imagem
              FROM produto p
              WHERE p.status = 1
              ORDER BY p.data_cadastro DESC, p.id DESC
              LIMIT 10";
$res_novos = $conn->query($sql_novos);
$novos_produtos = $res_novos ? $res_novos->fetch_all(MYSQLI_ASSOC) : [];

$sql_vendidos = "SELECT pr.id, pr.nome, pr.preco_base, SUM(ip.quantidade) AS vendidas,
                        (SELECT url_imagem FROM imagem_produto
                          WHERE fk_produto = pr.id
                          ORDER BY capa DESC, ordem ASC, id ASC LIMIT 1) AS imagem
                 FROM item_pedido ip
                 JOIN pedido p ON p.id = ip.fk_pedido
                 JOIN variacao_produto vp ON vp.id = ip.fk_variacao
                 JOIN produto pr ON pr.id = vp.fk_produto
                 WHERE " . SQL_VENDA_VALIDA . " AND pr.status = 1
                 GROUP BY pr.id, pr.nome, pr.preco_base
                 ORDER BY vendidas DESC, pr.id DESC
                 LIMIT 8";
$res_vendidos = $conn->query($sql_vendidos);
$mais_vendidos = $res_vendidos ? $res_vendidos->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CICRO Fitness</title>
  <link rel="stylesheet" href="../../css/style4.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../../css/header.css">
  <link rel="stylesheet" href="../../css/home.css">
</head>
<body>

  <!-- Cabeçalho -->
  <header class="site-header">
    <a class="site-header__logo" href="index.php">CICRO</a>
    <nav class="site-header__nav" aria-label="Principal">
      <ul>
        <li><a href="index.php" class="is-active">Início</a></li>
        <li><a href="catalogo.php">Catálogo</a></li>
        <li><a href="suporte.php">Suporte</a></li>
        <li><a href="login.php" id="nav-conta">Entrar</a></li>
        <li><a href="carrinho.php" class="site-header__cta">Carrinho</a></li>
      </ul>
    </nav>
  </header>

  <!-- Banner Hero -->
  <section class="hero">
    <div class="hero-content">
      <h1>CICRO FITNESS</h1>
      <p>Moda esportiva feminina com estilo e alto desempenho.</p>
      <a href="catalogo.php" class="btn-main">Ver Coleção</a>
    </div>
  </section>

  <!-- Banners principais -->
  <section class="banners" aria-label="Destaques da loja">
    <div class="banner-slider<?php echo count($banners_principais) < 2 ? ' unico' : ''; ?>" id="bannerSlider" aria-roledescription="carrossel">
      <?php foreach ($banners_principais as $k => $b): ?>
      <a class="banner-slide<?php echo $k === 0 ? ' ativo' : ''; ?>" href="<?php echo htmlspecialchars($b['link']); ?>" aria-label="<?php echo htmlspecialchars($b['alt']); ?>">
        <?php if ($b['img']): ?>
        <img src="<?php echo htmlspecialchars($b['img']); ?>" alt="<?php echo htmlspecialchars($b['alt']); ?>" loading="<?php echo $k === 0 ? 'eager' : 'lazy'; ?>">
        <?php else: ?>
        <div class="banner-vazio">
          <strong><?php echo htmlspecialchars($b['rotulo']); ?></strong>
          <span>1400 × 500 px</span>
          <small>images/banners/<?php echo htmlspecialchars($b['arquivo']); ?>.jpg (ou .png / .webp)</small>
        </div>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>

      <button type="button" class="banner-seta esquerda" id="bannerPrev" aria-label="Banner anterior">
        <i class="fa-solid fa-chevron-left"></i>
      </button>
      <button type="button" class="banner-seta direita" id="bannerNext" aria-label="Próximo banner">
        <i class="fa-solid fa-chevron-right"></i>
      </button>
      <div class="banner-pontos">
        <?php foreach ($banners_principais as $k => $b): ?>
        <button type="button" class="banner-ponto<?php echo $k === 0 ? ' ativo' : ''; ?>" aria-label="Ir para o banner <?php echo $k + 1; ?>"></button>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Carrossel de Produtos -->
  <section class="section-container">
    <h2 class="section-title">Novos Produtos</h2>
    
    <div class="carrossel-box">
      <button class="arrow-btn left" id="btnPrev" aria-label="Anterior">
        <i class="fa-solid fa-chevron-left"></i>
      </button>

      <div class="carrossel-track" id="carrossel">
        <?php if (!empty($novos_produtos)): ?>
          <?php foreach ($novos_produtos as $np):
            $img = !empty($np['imagem']) ? '../../' . ltrim($np['imagem'], './') : $imagem_padrao;
          ?>
        <div class="card-produto">
          <div class="img-wrapper">
            <img src="<?php echo htmlspecialchars($img); ?>"
                 alt="<?php echo htmlspecialchars($np['nome']); ?>"
                 onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imagem_padrao); ?>';">
          </div>
          <div class="card-info">
            <h3><?php echo htmlspecialchars($np['nome']); ?></h3>
            <span class="preco">R$ <?php echo number_format($np['preco_base'], 2, ',', '.'); ?></span>
            <a class="btn-comprar" href="catalogo.php?busca=<?php echo urlencode($np['nome']); ?>">Comprar</a>
          </div>
        </div>
          <?php endforeach; ?>
        <?php else: ?>
        <p style="padding:20px;">Nenhum produto novo por enquanto.</p>
        <?php endif; ?>

      </div>

      <button class="arrow-btn right" id="btnNext" aria-label="Próximo">
        <i class="fa-solid fa-chevron-right"></i>
      </button>
    </div>
  </section>

  <!-- Mais vendidos -->
  <?php if (!empty($mais_vendidos)): ?>
  <section class="faixa-vendidos">
    <div class="section-container">
      <h2 class="section-title">Mais Vendidos</h2>
      <p class="section-sub">As peças que as clientes mais levam para casa.</p>

      <div class="grid-vendidos">
        <?php foreach ($mais_vendidos as $pos => $mv):
          $img = !empty($mv['imagem']) ? '../../' . ltrim($mv['imagem'], './') : $imagem_padrao;
          $qtd = (int)$mv['vendidas'];
        ?>
        <div class="card-produto">
          <div class="img-wrapper">
            <?php if ($pos < 3): ?><span class="selo-rank"><?php echo $pos + 1; ?>º mais vendido</span><?php endif; ?>
            <img src="<?php echo htmlspecialchars($img); ?>"
                 alt="<?php echo htmlspecialchars($mv['nome']); ?>"
                 loading="lazy"
                 onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($imagem_padrao); ?>';">
          </div>
          <div class="card-info">
            <h3><?php echo htmlspecialchars($mv['nome']); ?></h3>
            <span class="preco">R$ <?php echo number_format($mv['preco_base'], 2, ',', '.'); ?></span>
            <span class="vendidas"><?php echo $qtd; ?> <?php echo $qtd === 1 ? 'vendida' : 'vendidas'; ?></span>
            <a class="btn-comprar" href="catalogo.php?busca=<?php echo urlencode($mv['nome']); ?>">Comprar</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Banners promocionais -->
  <section class="banners banners-fim" aria-label="Promoções">
    <div class="banners-promo">
      <?php foreach ($banners_promo as $b): ?>
      <a class="banner-promo" href="<?php echo htmlspecialchars($b['link']); ?>" aria-label="<?php echo htmlspecialchars($b['alt']); ?>">
        <?php if ($b['img']): ?>
        <img src="<?php echo htmlspecialchars($b['img']); ?>" alt="<?php echo htmlspecialchars($b['alt']); ?>" loading="lazy">
        <?php else: ?>
        <div class="banner-vazio">
          <strong><?php echo htmlspecialchars($b['rotulo']); ?></strong>
          <span>700 × 350 px</span>
          <small>images/banners/<?php echo htmlspecialchars($b['arquivo']); ?>.jpg (ou .png / .webp)</small>
        </div>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Rodapé -->
  <footer>
    <p>&copy; 2026 CICRO FITNESS — Todos os direitos reservados</p>
    <p><a href="#">Termos e Políticas</a> | <a href="#">Contato</a></p>
  </footer>

  <script>
    const carrossel = document.getElementById('carrossel');
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');

    btnNext.addEventListener('click', () => {
      carrossel.scrollBy({ left: 260, behavior: 'smooth' });
    });

    btnPrev.addEventListener('click', () => {
      carrossel.scrollBy({ left: -260, behavior: 'smooth' });
    });
  </script>

  <script>
    (function () {
      const slider = document.getElementById('bannerSlider');
      if (!slider) return;
      const slides = slider.querySelectorAll('.banner-slide');
      const pontos = slider.querySelectorAll('.banner-ponto');
      if (slides.length < 2) return;
      let atual = 0;
      let timer = null;

      function ir(n) {
        atual = (n + slides.length) % slides.length;
        slides.forEach((s, i) => {
          const ativo = i === atual;
          s.classList.toggle('ativo', ativo);
          s.setAttribute('aria-hidden', ativo ? 'false' : 'true');
          s.tabIndex = ativo ? 0 : -1;
        });
        pontos.forEach((p, i) => p.classList.toggle('ativo', i === atual));
      }

      function parar() {
        if (timer) { clearInterval(timer); timer = null; }
      }

      function iniciar() {
        parar();
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        timer = setInterval(() => ir(atual + 1), 5000);
      }

      document.getElementById('bannerPrev').addEventListener('click', () => { ir(atual - 1); iniciar(); });
      document.getElementById('bannerNext').addEventListener('click', () => { ir(atual + 1); iniciar(); });
      pontos.forEach((p, i) => p.addEventListener('click', () => { ir(i); iniciar(); }));
      slider.addEventListener('mouseenter', parar);
      slider.addEventListener('mouseleave', iniciar);
      slider.addEventListener('focusin', parar);
      slider.addEventListener('focusout', iniciar);

      ir(0);
      iniciar();
    })();
  </script>

  <script>
    fetch('../../controllers/sessao.php')
      .then(r => r.json())
      .then(d => {
        if (!d.logado) return;
        const a = document.getElementById('nav-conta');
        const func = d.tipo === 'admin' || d.tipo === 'vendedor';
        a.textContent = func ? 'Painel' : 'Meu Perfil';
        a.href = func ? '../admin/inicio.php' : 'perfil.php';
      })
      .catch(() => {});
  </script>
</body>
</html>