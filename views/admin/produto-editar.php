<?php
require_once __DIR__ . '/../../config/conexao.php';
require_once __DIR__ . '/../../controllers/auth_admin.php';
require_once __DIR__ . '/../../models/cor_funcoes.php';

const TAMANHOS = ['PP', 'P', 'M', 'G', 'GG'];
const EXT_IMG  = ['jpg', 'jpeg', 'png', 'webp'];
const MAX_IMG  = 5 * 1024 * 1024;

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM produto WHERE id = ?");
$stmt->execute([$id]);
$produto = $stmt->fetch();
if (!$produto) {
    $_SESSION['flash_admin'] = ['ok' => false, 'msg' => 'Produto não encontrado.'];
    header('Location: produtos-gerenciar.php');
    exit;
}

$erros = [];
$avisos = [];

function apagar_arquivo_imagem(string $url): void
{
    $base    = realpath(dirname(__DIR__, 2) . '/admin-pages/uploads');
    $arquivo = realpath(dirname(__DIR__, 2) . '/' . ltrim($url, './'));
    if ($base && $arquivo && strpos($arquivo, $base . DIRECTORY_SEPARATOR) === 0 && is_file($arquivo)) {
        @unlink($arquivo);
    }
}

function variacao_vendida(PDO $pdo, int $id_variacao): bool
{
    $s = $pdo->prepare("SELECT COUNT(*) FROM item_pedido WHERE fk_variacao = ?");
    $s->execute([$id_variacao]);
    return (int)$s->fetchColumn() > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $nome      = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['desc'] ?? '');
    $preco     = (float)str_replace(',', '.', $_POST['preco'] ?? '0');
    $categoria = (int)($_POST['categoria'] ?? 0);
    $status    = isset($_POST['status']) ? 1 : 0;

    if ($nome === '' || mb_strlen($nome) > 150) { $erros[] = 'Informe o nome do produto (até 150 caracteres).'; }
    if ($preco <= 0)                            { $erros[] = 'O preço precisa ser maior que zero.'; }
    $c = $pdo->prepare("SELECT id FROM categoria WHERE id = ?");
    $c->execute([$categoria]);
    if (!$c->fetch())                           { $erros[] = 'Escolha uma categoria válida.'; }

    if (!$erros) {
        $arquivos_para_apagar = [];
        $arquivos_novos = [];
        try {
            $pdo->beginTransaction();

            $pdo->prepare("UPDATE produto SET nome = ?, descricao = ?, preco_base = ?, fk_categoria = ?, status = ? WHERE id = ?")
                ->execute([$nome, $descricao !== '' ? $descricao : null, $preco, $categoria, $status, $id]);

            $vs = $pdo->prepare("SELECT id, tamanho, quantidade_estoque FROM variacao_produto WHERE fk_produto = ?");
            $vs->execute([$id]);
            $existentes = [];
            foreach ($vs->fetchAll() as $v) {
                $existentes[(int)$v['id']] = $v;
            }

            foreach ($existentes as $vid => $v) {
                if (isset($_POST['remover'][$vid])) {
                    if (variacao_vendida($pdo, $vid)) {
                        $avisos[] = 'O tamanho ' . $v['tamanho'] . ' já foi vendido e não pode ser removido; deixe o estoque em 0.';
                    } else {
                        $pdo->prepare("DELETE FROM item_carrinho WHERE fk_variacao = ?")->execute([$vid]);
                        $pdo->prepare("DELETE FROM movimentacao_estoque WHERE fk_variacao = ?")->execute([$vid]);
                        $pdo->prepare("DELETE FROM variacao_produto WHERE id = ?")->execute([$vid]);
                        continue;
                    }
                }
                $novo_estoque = max(0, (int)($_POST['estoque'][$vid] ?? $v['quantidade_estoque']));
                $antigo = (int)$v['quantidade_estoque'];
                if ($novo_estoque !== $antigo) {
                    $pdo->prepare("UPDATE variacao_produto SET quantidade_estoque = ? WHERE id = ?")->execute([$novo_estoque, $vid]);
                    $pdo->prepare("INSERT INTO movimentacao_estoque (fk_variacao, tipo, quantidade, motivo) VALUES (?, ?, ?, 'ajuste manual')")
                        ->execute([$vid, $novo_estoque > $antigo ? 'entrada' : 'saida', abs($novo_estoque - $antigo)]);
                }
            }

            $ja_tem = array_map(fn($v) => $v['tamanho'], $existentes);
            $qtd_nova = max(0, (int)($_POST['novo_estoque'] ?? 0));
            foreach ((array)($_POST['novo_tamanho'] ?? []) as $t) {
                if (!in_array($t, TAMANHOS, true) || in_array($t, $ja_tem, true)) {
                    continue;
                }
                $sku = 'PROD-' . $id . '-' . $t . '-' . substr(uniqid(), -4);
                $pdo->prepare("INSERT INTO variacao_produto (fk_produto, sku, tamanho, cor, quantidade_estoque) VALUES (?, ?, ?, 'Padrão', ?)")
                    ->execute([$id, $sku, $t, $qtd_nova]);
            }

            $is = $pdo->prepare("SELECT id, url_imagem FROM imagem_produto WHERE fk_produto = ?");
            $is->execute([$id]);
            $imgs = [];
            foreach ($is->fetchAll() as $i) {
                $imgs[(int)$i['id']] = $i;
            }
            foreach ((array)($_POST['remover_img'] ?? []) as $iid) {
                $iid = (int)$iid;
                if (isset($imgs[$iid])) {
                    $pdo->prepare("DELETE FROM imagem_produto WHERE id = ?")->execute([$iid]);
                    $arquivos_para_apagar[] = $imgs[$iid]['url_imagem'];
                    unset($imgs[$iid]);
                }
            }

            foreach (array_keys($imgs) as $iid) {
                if (isset($_POST['cor_img'][$iid])) {
                    $pdo->prepare("UPDATE imagem_produto SET cor = ? WHERE id = ?")
                        ->execute([cor_do_form($_POST['cor_img'][$iid], $_POST['cor_img_outra'][$iid] ?? ''), $iid]);
                }
            }

            if (!empty($_FILES['imagens']['name'][0])) {
                $dir = dirname(__DIR__, 2) . '/admin-pages/uploads/';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $m = $pdo->prepare("SELECT COALESCE(MAX(ordem), -1) FROM imagem_produto WHERE fk_produto = ?");
                $m->execute([$id]);
                $ordem = (int)$m->fetchColumn() + 1;

                foreach ($_FILES['imagens']['tmp_name'] as $k => $tmp) {
                    $nome_orig = $_FILES['imagens']['name'][$k];
                    $err = $_FILES['imagens']['error'][$k];
                    if ($err === UPLOAD_ERR_NO_FILE) { continue; }
                    if ($err !== UPLOAD_ERR_OK) { throw new RuntimeException("Falha no upload de '$nome_orig' (erro $err)."); }
                    $ext = strtolower(pathinfo($nome_orig, PATHINFO_EXTENSION));
                    if (!in_array($ext, EXT_IMG, true) || @getimagesize($tmp) === false) {
                        throw new RuntimeException("'$nome_orig' não é uma imagem válida (use jpg, png ou webp).");
                    }
                    if ($_FILES['imagens']['size'][$k] > MAX_IMG) {
                        throw new RuntimeException("'$nome_orig' passa de 5 MB.");
                    }
                    $novo = 'prod_' . $id . '_' . uniqid() . '.' . $ext;
                    if (!move_uploaded_file($tmp, $dir . $novo)) {
                        throw new RuntimeException("Não foi possível salvar '$nome_orig'.");
                    }
                    $arquivos_novos[] = $dir . $novo;
                    $pdo->prepare("INSERT INTO imagem_produto (fk_produto, url_imagem, ordem, capa, cor) VALUES (?, ?, ?, 0, ?)")
                        ->execute([$id, 'admin-pages/uploads/' . $novo, $ordem++,
                                   cor_do_form($_POST['cor_nova'][$k] ?? '', $_POST['cor_nova_outra'][$k] ?? '')]);
                }
            }

            $capa_id = (int)($_POST['capa'] ?? 0);
            $todas = $pdo->prepare("SELECT id FROM imagem_produto WHERE fk_produto = ? ORDER BY ordem ASC, id ASC");
            $todas->execute([$id]);
            $ids_imgs = array_map('intval', $todas->fetchAll(PDO::FETCH_COLUMN));
            if ($ids_imgs) {
                if (!in_array($capa_id, $ids_imgs, true)) {
                    $capa_id = $ids_imgs[0];
                }
                $pdo->prepare("UPDATE imagem_produto SET capa = 0 WHERE fk_produto = ?")->execute([$id]);
                $pdo->prepare("UPDATE imagem_produto SET capa = 1 WHERE id = ?")->execute([$capa_id]);
            }

            $pdo->commit();

            foreach ($arquivos_para_apagar as $u) {
                apagar_arquivo_imagem($u);
            }
            $_SESSION['flash_admin'] = ['ok' => true, 'msg' => 'Produto "' . $nome . '" atualizado.' . ($avisos ? ' Atenção: ' . implode(' ', $avisos) : '')];
            header('Location: produtos-gerenciar.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($arquivos_novos as $f) {
                @unlink($f);
            }
            error_log('Erro ao editar produto: ' . $e->getMessage());
            $erros[] = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível salvar as alterações. Tente novamente.';
        }
    }
    $produto['nome'] = $nome;
    $produto['descricao'] = $descricao;
    $produto['preco_base'] = $preco;
    $produto['fk_categoria'] = $categoria;
    $produto['status'] = $status;
}

$categorias = $pdo->query("SELECT id, nome FROM categoria WHERE status = 1 ORDER BY ordem_exibicao ASC, nome ASC")->fetchAll();

$vs = $pdo->prepare("SELECT id, tamanho, cor, quantidade_estoque FROM variacao_produto WHERE fk_produto = ? ORDER BY id ASC");
$vs->execute([$id]);
$variacoes = $vs->fetchAll();
$tem = array_map(fn($v) => $v['tamanho'], $variacoes);
$mostrar_cor = (bool)array_filter($variacoes, fn($v) => cor_visivel($v['cor']) !== '');
$faltam = array_values(array_diff(TAMANHOS, $tem));

$is = $pdo->prepare("SELECT id, url_imagem, capa, cor FROM imagem_produto WHERE fk_produto = ? ORDER BY ordem ASC, id ASC");
$is->execute([$id]);
$imagens = $is->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CICRO - Editar produto</title>
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
        <h1>Editar produto #<?= (int)$produto['id'] ?></h1>
        <div class="adm-topo-acoes">
            <a href="produtos-gerenciar.php" class="btn btn-claro">← Voltar para a lista</a>
        </div>
    </header>

    <?php foreach ($erros as $e): ?>
        <div class="aviso aviso-erro"><?= h($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" enctype="multipart/form-data" class="cartao">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= (int)$produto['id'] ?>">

        <h2>Dados do produto</h2>
        <div class="linha">
            <label class="campo grande">Nome
                <input type="text" name="nome" value="<?= h($produto['nome']) ?>" maxlength="150" required>
            </label>
            <label class="campo">Preço (R$)
                <input type="number" name="preco" step="0.01" min="0.01" value="<?= h(number_format((float)$produto['preco_base'], 2, '.', '')) ?>" required>
            </label>
        </div>
        <label class="campo">Descrição
            <textarea name="desc" rows="3"><?= h($produto['descricao']) ?></textarea>
        </label>
        <div class="linha">
            <label class="campo">Categoria
                <select name="categoria" required>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === (int)$produto['fk_categoria'] ? 'selected' : '' ?>><?= h($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="campo check">
                <input type="checkbox" name="status" value="1" <?= $produto['status'] ? 'checked' : '' ?>>
                Ativo (aparece no catálogo)
            </label>
        </div>

        <h2>Estoque por tamanho</h2>
        <?php if (!$variacoes): ?>
            <p class="vazio">Nenhum tamanho cadastrado.</p>
        <?php else: ?>
            <table class="tabela compacta">
                <thead><tr><th>Tamanho</th><?php if ($mostrar_cor): ?><th>Cor</th><?php endif; ?><th>Estoque</th><th>Remover</th></tr></thead>
                <tbody>
                <?php foreach ($variacoes as $v): ?>
                    <tr>
                        <td><strong><?= h($v['tamanho']) ?></strong></td>
                        <?php if ($mostrar_cor): ?><td><?= h($v['cor']) ?></td><?php endif; ?>
                        <td><input type="number" min="0" class="num" name="estoque[<?= (int)$v['id'] ?>]" value="<?= (int)$v['quantidade_estoque'] ?>"></td>
                        <td><input type="checkbox" name="remover[<?= (int)$v['id'] ?>]" value="1"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($faltam): ?>
            <div class="novos-tamanhos">
                <strong>Adicionar tamanhos:</strong>
                <?php foreach ($faltam as $t): ?>
                    <label class="pill"><input type="checkbox" name="novo_tamanho[]" value="<?= h($t) ?>"> <?= h($t) ?></label>
                <?php endforeach; ?>
                <label class="campo inline">com estoque de
                    <input type="number" min="0" class="num" name="novo_estoque" value="0">
                </label>
            </div>
        <?php endif; ?>

        <h2>Fotos</h2>
        <?php if ($imagens): ?>
            <div class="fotos">
                <?php foreach ($imagens as $img): ?>
                    <div class="foto">
                        <img src="../../<?= h(ltrim($img['url_imagem'], './')) ?>" alt="">
                        <label><input type="radio" name="capa" value="<?= (int)$img['id'] ?>" <?= $img['capa'] ? 'checked' : '' ?>> Capa</label>
                        <?= campo_cor('cor_img[' . (int)$img['id'] . ']', 'cor_img_outra[' . (int)$img['id'] . ']', $img['cor']) ?>
                        <label class="rm"><input type="checkbox" name="remover_img[]" value="<?= (int)$img['id'] ?>"> Remover</label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="vazio">Este produto ainda não tem fotos.</p>
        <?php endif; ?>
        <label class="campo">Adicionar fotos (jpg, png ou webp, até 5 MB cada)
            <input type="file" name="imagens[]" accept="image/*" multiple>
        </label>
        <div class="fotos" id="fotos-novas"></div>
        <template id="modelo-cor"><?= campo_cor('cor_nova[]', 'cor_nova_outra[]', null) ?></template>

        <div class="rodape-form">
            <a href="produtos-gerenciar.php" class="btn btn-claro">Cancelar</a>
            <button type="submit" class="btn btn-escuro">Salvar alterações</button>
        </div>
    </form>
</main>
<script src="../../js/recorte-imagem.js"></script>
<script src="../../js/cor-foto.js"></script>
<script>
(function () {
    var input = document.querySelector('input[type="file"][name="imagens[]"]');
    if (!input || !window.abrirRecorteImagem) return;
    input.addEventListener('change', async function () {
        var arquivos = Array.from(input.files), dt = new DataTransfer();
        for (var i = 0; i < arquivos.length; i++) {
            if (!arquivos[i].type.startsWith('image/')) continue;
            var pronta = await window.abrirRecorteImagem(arquivos[i]);
            if (pronta) dt.items.add(pronta);
        }
        input.files = dt.files;
        mostrarCoresNovas();
    });

    function mostrarCoresNovas() {
        var caixa = document.getElementById('fotos-novas');
        var modelo = document.getElementById('modelo-cor');
        caixa.innerHTML = '';
        Array.from(input.files).forEach(function (arquivo) {
            var foto = document.createElement('div');
            foto.className = 'foto';
            var img = document.createElement('img');
            img.src = URL.createObjectURL(arquivo);
            img.alt = '';
            foto.append(img, modelo.content.cloneNode(true));
            caixa.appendChild(foto);
        });
    }
})();
</script>
  <script>window.CICRO_NOTIF = {pedidos: false};</script>
  <script src="../../js/notificacoes-admin.js"></script>
  <script src="../../js/confirmar-saida.js"></script>
</body>
</html>