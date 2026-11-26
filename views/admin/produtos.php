<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../controllers/auth_admin.php';
require_once __DIR__ . '/../../models/cor_funcoes.php';

$pagina_destino = 'produtos-gerenciar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['desc'] ?? '');
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $preco = (float)($_POST['preco'] ?? 0.0);
    $fk_categoria = (int)($_POST['categoria'] ?? 0);
    $tamanhos = $_POST['tamanhos'] ?? [];

    if (empty($nome) || $preco <= 0 || $fk_categoria <= 0) {
        die("Preencha todos os campos obrigatórios corretamente.");
    }

    $conn->begin_transaction();

    try {
        $stmtCheckCat = $conn->prepare("SELECT id FROM categoria WHERE id = ?");
        $stmtCheckCat->bind_param("i", $fk_categoria);
        $stmtCheckCat->execute();
        $resCheckCat = $stmtCheckCat->get_result();

        if ($resCheckCat->num_rows === 0) {
            throw new Exception("A categoria selecionada (ID: {$fk_categoria}) não existe no banco de dados.");
        }
        $stmtCheckCat->close();

        $stmtProd = $conn->prepare("INSERT INTO produto (nome, descricao, preco_base, fk_categoria) VALUES (?, ?, ?, ?)");
        $stmtProd->bind_param("ssdi", $nome, $descricao, $preco, $fk_categoria);
        $stmtProd->execute();

        $id_produto = $conn->insert_id;
        $stmtProd->close();

        if (!empty($tamanhos)) {
            $stmtVar = $conn->prepare("INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES (?, ?, ?, ?, ?)");
            $cor = "Padrão";
            $sku = "";
            $tamanho_atual = "";

            $stmtVar->bind_param("isssi", $id_produto, $sku, $tamanho_atual, $cor, $quantidade);

            foreach ($tamanhos as $tamanho) {
                $tamanho_atual = htmlspecialchars($tamanho);
                $sku = "PROD-" . $id_produto . "-" . $tamanho_atual . "-" . substr(uniqid(), -4);
                $stmtVar->execute();
            }
            $stmtVar->close();
        }

        if (isset($_FILES['imagens']) && !empty($_FILES['imagens']['name'][0])) {
            $diretorio_upload = dirname(__DIR__, 2) . '/admin-pages/uploads/';
            if (!is_dir($diretorio_upload)) {
                mkdir($diretorio_upload, 0755, true);
            }

            $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
            $stmtImg = $conn->prepare("INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa, cor) VALUES (?, ?, ?, ?, ?)");

            $url_imagem = "";
            $ordem = 0;
            $capa = 0;
            $cor_foto = null;
            $stmtImg->bind_param("isiis", $id_produto, $url_imagem, $ordem, $capa, $cor_foto);

            foreach ($_FILES['imagens']['tmp_name'] as $key => $tmp_name) {
                $nome_arquivo = $_FILES['imagens']['name'][$key];
                $erro = $_FILES['imagens']['error'][$key];

                if ($erro !== UPLOAD_ERR_OK) {
                    throw new Exception("Falha no upload de '{$nome_arquivo}' (código de erro PHP: {$erro}).");
                }

                $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
                if (!in_array($extensao, $extensoes_permitidas)) {
                    throw new Exception("Extensão não permitida: .{$extensao}");
                }

                $novo_nome_imagem = "prod_" . $id_produto . "_" . uniqid() . "." . $extensao;
                $caminho_final = $diretorio_upload . $novo_nome_imagem;

                if (!move_uploaded_file($tmp_name, $caminho_final)) {
                    throw new Exception("Não foi possível salvar '{$nome_arquivo}' em {$caminho_final}.");
                }

                $url_imagem = "admin-pages/uploads/" . $novo_nome_imagem;
                $ordem = $key;
                $capa = ($key === 0) ? 1 : 0;
                $cor_foto = cor_do_form($_POST['cor_foto'][$key] ?? '', $_POST['cor_foto_outra'][$key] ?? '');
                $stmtImg->execute();
            }
            $stmtImg->close();
        }

        $conn->commit();
        echo "<script>alert('Produto cadastrado com sucesso!'); window.location.href='" . $pagina_destino . "';</script>";
        exit;

    } catch (Exception $e) {
        try {
            $conn->rollback();
        } catch (Exception $rollbackEx) {
        }
        echo "Erro ao cadastrar produto: " . htmlspecialchars($e->getMessage());
        exit;
    }
}

$categoriasBD = [];
$resCat = $conn->query("SELECT id, nome FROM categoria WHERE status = 1 ORDER BY ordem_exibicao ASC, nome ASC");
if ($resCat) {
    while ($row = $resCat->fetch_assoc()) {
        $categoriasBD[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/style5.css">
    <title>Cadastro de Produto - CICRO</title>
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

  <a href="produtos-gerenciar.php" class="link-voltar">← Ver / editar produtos</a>

  <div class="modal-container">

    <header class="topo-pagina">
      <h1>Novo produto</h1>
      <p>Preencha os dados e adicione as fotos para publicar no catálogo.</p>
    </header>

    <form action="" method="POST" enctype="multipart/form-data" class="form-area-container">

      <!-- Lado Esquerdo (Upload de Fotos) -->
      <div class="upload-area" id="upload-area">
        <div class="upload-content" id="upload-content">
          <p>Adicione seus arquivos</p>
          <label for="foto-input" class="btn-upload">Adicionar arquivos</label>
          <small>JPG, PNG ou WEBP. A primeira foto será a capa.</small>
        </div>
        <input type="file" id="foto-input" name="imagens[]" accept="image/*" multiple style="display: none;">

        <div class="carousel-container" id="carousel-container" style="display: none;">
          <div class="carousel-slides" id="carousel-slides"></div>
          <button type="button" class="btn-remove" id="btn-remove" aria-label="Remover foto">&times;</button>
          <button type="button" class="carousel-prev" id="btn-prev" aria-label="Foto anterior">&#10094;</button>
          <button type="button" class="carousel-next" id="btn-next" aria-label="Próxima foto">&#10095;</button>
        </div>
      </div>

      <!-- Lado Direito (Campos do Formulário) -->
      <div class="form-area">
        <div class="form-group">
          <label for="nome">Nome</label>
          <input type="text" id="nome" name="nome" class="form-input" placeholder="Ex.: Conjunto Legging Preto" required>
        </div>

        <div class="form-group">
          <label for="desc">Descrição</label>
          <input type="text" id="desc" name="desc" class="form-input" placeholder="Material, caimento, detalhes..." required>
        </div>

        <div class="form-group">
          <label>Cor de cada foto</label>
          <p class="cor-ajuda" id="cor-ajuda">Adicione as fotos para escolher a cor de cada uma.</p>
          <div class="cores-fotos" id="cores-fotos"></div>
          <template id="modelo-cor"><?= campo_cor('cor_foto[]', 'cor_foto_outra[]', null) ?></template>
        </div>

        <div class="row-split">
          <div class="col-left">
            <div class="form-group">
              <label for="quantidade">Quantidade por tamanho</label>
              <input type="number" id="quantidade" name="quantidade" class="form-input" placeholder="10" required min="1">
            </div>
            <div class="form-group">
              <label for="preco">Preço (R$)</label>
              <input type="number" id="preco" name="preco" step="0.01" class="form-input" placeholder="29.99" required>
            </div>
          </div>

          <div class="col-right">
            <div class="form-group">
              <label>Tamanhos</label>
              <div class="tamanhos-grid">
                <input type="checkbox" name="tamanhos[]" value="PP" id="pp">
                <label for="pp" class="btn-option">PP</label>

                <input type="checkbox" name="tamanhos[]" value="P" id="p">
                <label for="p" class="btn-option">P</label>

                <input type="checkbox" name="tamanhos[]" value="M" id="m">
                <label for="m" class="btn-option">M</label>

                <input type="checkbox" name="tamanhos[]" value="G" id="g">
                <label for="g" class="btn-option">G</label>

                <input type="checkbox" name="tamanhos[]" value="GG" id="gg">
                <label for="gg" class="btn-option tamanho-gg">GG</label>
              </div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="rotulo-centro">Categoria</label>
          <div class="categorias-list">
            <?php if (!empty($categoriasBD)): ?>
              <?php foreach ($categoriasBD as $cat): ?>
                <input type="radio" name="categoria" value="<?= $cat['id'] ?>" id="cat-<?= $cat['id'] ?>" required>
                <label for="cat-<?= $cat['id'] ?>" class="btn-pill"><?= htmlspecialchars($cat['nome']) ?></label>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="aviso-vazio">Nenhuma categoria encontrada no banco de dados.</p>
            <?php endif; ?>
          </div>
        </div>

        <div class="actions">
          <a href="produtos-gerenciar.php" class="btn-cancelar">CANCELAR</a>
          <button type="submit" class="btn-enviar">ENVIAR</button>
        </div>
      </div>

    </form>

  </div>

  <script src="../../js/recorte-imagem.js"></script>
  <script src="../../js/cor-foto.js"></script>
  <script src="../../js/script-produto.js"></script>
  <script>window.CICRO_NOTIF = {pedidos: false};</script>
  <script src="../../js/notificacoes-admin.js"></script>
  <script src="../../js/confirmar-saida.js"></script>
</body>
</html>