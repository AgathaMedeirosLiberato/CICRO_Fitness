<?php
session_start();
require_once __DIR__ . '/../models/carrinho_selecao.php';
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/estoque_funcoes.php';
require_once __DIR__ . '/../models/pedido_funcoes.php';
require_once __DIR__ . '/../models/pagamento_funcoes.php';

const VOLTAR_CARRINHO = '../views/user/checkout.php';

function voltar_carrinho(string $msg): void
{
    $_SESSION['flash_carrinho'] = $msg;
    header('Location: ' . VOLTAR_CARRINHO);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/user/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . VOLTAR_CARRINHO);
    exit;
}
if (!hash_equals($_SESSION['csrf_cliente'] ?? '', (string)($_POST['csrf'] ?? ''))) {
    voltar_carrinho('Sessão expirada. Tente finalizar de novo.');
}
if (($_SESSION['usuario_tipo'] ?? '') !== 'cliente') {
    voltar_carrinho('Apenas contas de cliente podem fazer pedidos.');
}

$id_cliente = (int)$_SESSION['usuario_id'];

try {
    expirar_pedidos_pendentes($pdo);

    $s = $pdo->prepare("SELECT cpf FROM cliente WHERE id_usuario = ?");
    $s->execute([$id_cliente]);
    if (!$s->fetchColumn()) {
        header('Location: ../views/user/perfil.php');
        $_SESSION['flash_perfil'] = ['secao' => 'dados', 'ok' => false, 'msg' => 'Informe seu CPF para poder finalizar a compra.'];
        exit;
    }

    $id_endereco = (int)($_POST['endereco_id'] ?? 0);
    $s = $pdo->prepare(
        "SELECT id, cep, rua, numero, complemento, bairro, cidade, estado
         FROM endereco WHERE id = ? AND fk_cliente = ?"
    );
    $s->execute([$id_endereco, $id_cliente]);
    $endereco = $s->fetch();
    if (!$endereco) {
        voltar_carrinho('Escolha um endereço de entrega (ou cadastre um no seu perfil).');
    }
    $endereco_texto = formatar_endereco($endereco);

    $forma_pagamento = (string)($_POST['forma_pagamento'] ?? '');
    if (!isset(formas_disponiveis()[$forma_pagamento])) {
        voltar_carrinho(formas_disponiveis() ? 'Escolha a forma de pagamento.' : 'O pagamento por Pix ainda não foi configurado pela loja.');
    }

    $sel = carrinho_selecao();
    if (!$sel) {
        voltar_carrinho('Marque no carrinho os itens que você quer comprar.');
    }
    $marcas = carrinho_marcas($sel);

    $pdo->beginTransaction();

    $s = $pdo->prepare(
        "SELECT ic.id AS item_id, ic.quantidade,
                vp.id AS variacao_id, vp.tamanho,
                COALESCE(ip.cor, vp.cor) AS cor,   -- cor da foto escolhida pelo cliente
                p.nome, p.status,
                (p.preco_base + vp.preco_adicional) AS preco
         FROM item_carrinho ic
         JOIN carrinho c          ON c.id  = ic.fk_carrinho
         JOIN variacao_produto vp ON vp.id = ic.fk_variacao
         JOIN produto p           ON p.id  = vp.fk_produto
         LEFT JOIN imagem_produto ip ON ip.id = ic.fk_imagem
         WHERE c.fk_cliente = ? AND ic.id IN ($marcas)
         ORDER BY vp.id ASC"
    );
    $s->execute(array_merge([$id_cliente], $sel));
    $itens = $s->fetchAll();

    if (!$itens) {
        $pdo->rollBack();
        voltar_carrinho('Seu carrinho está vazio.');
    }

    $valor_produtos = 0.0;
    foreach ($itens as $i) {
        if (!(int)$i['status']) {
            $pdo->rollBack();
            voltar_carrinho('"' . $i['nome'] . '" não está mais disponível. Remova do carrinho para continuar.');
        }
        $valor_produtos += (float)$i['preco'] * (int)$i['quantidade'];
    }
    $valor_total = $valor_produtos;

    $pdo->prepare(
        "INSERT INTO pedido (fk_cliente, fk_endereco, endereco_entrega, forma_pagamento,
                             status, valor_total)
         VALUES (?, ?, ?, ?, 'Pendente', ?)"
    )->execute([$id_cliente, (int)$endereco['id'], $endereco_texto, $forma_pagamento, $valor_total]);
    $id_pedido = (int)$pdo->lastInsertId();

    $ins = $pdo->prepare(
        "INSERT INTO item_pedido (fk_pedido, fk_variacao, nome_produto, tamanho, cor, quantidade, preco_unitario)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($itens as $i) {
        $ins->execute([
            $id_pedido, (int)$i['variacao_id'], $i['nome'], $i['tamanho'], $i['cor'],
            (int)$i['quantidade'], $i['preco'],
        ]);

        movimentar_estoque($pdo, (int)$i['variacao_id'], 'saida', (int)$i['quantidade'], 'venda', $id_pedido);
    }

    criar_pagamento_pix($pdo, $id_pedido, $valor_total);

    $pdo->prepare(
        "DELETE FROM item_carrinho
         WHERE id IN ($marcas) AND fk_carrinho IN (SELECT id FROM carrinho WHERE fk_cliente = ?)"
    )->execute(array_merge($sel, [$id_cliente]));

    $pdo->commit();
    unset($_SESSION['carrinho_sel']);

    header('Location: ../views/user/pagamento.php?id=' . $id_pedido);
    exit;

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    voltar_carrinho($e->getMessage());
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erro ao finalizar pedido: ' . $e->getMessage());
    voltar_carrinho('Não foi possível finalizar o pedido. Tente novamente.');
}