<?php

const AVALIACAO_COMENTARIO_MAX = 500;

function produtos_para_avaliar(PDO $pdo, int $id_pedido, int $id_cliente): array
{
    $st = $pdo->prepare("SELECT status FROM pedido WHERE id = ? AND fk_cliente = ?");
    $st->execute([$id_pedido, $id_cliente]);
    if ($st->fetchColumn() !== 'Entregue') {
        return [];
    }
    $st = $pdo->prepare(
        "SELECT p.id AS fk_produto, p.nome,
                (SELECT url_imagem FROM imagem_produto WHERE fk_produto = p.id ORDER BY capa DESC, ordem ASC, id ASC LIMIT 1) AS url_imagem,
                a.nota, a.comentario
         FROM item_pedido i
         JOIN variacao_produto vp ON vp.id = i.fk_variacao
         JOIN produto p ON p.id = vp.fk_produto
         LEFT JOIN avaliacao a ON a.fk_produto = p.id AND a.fk_cliente = ?
         WHERE i.fk_pedido = ?
         GROUP BY p.id, p.nome, a.nota, a.comentario
         ORDER BY p.id");
    $st->execute([$id_cliente, $id_pedido]);
    return $st->fetchAll();
}

function salvar_avaliacao(PDO $pdo, int $id_pedido, int $id_cliente, int $id_produto, int $nota, string $comentario): void
{
    if ($nota < 1 || $nota > 5) {
        throw new RuntimeException('Escolha uma nota de 1 a 5 estrelas.');
    }
    $comentario = trim($comentario);
    if (mb_strlen($comentario) > AVALIACAO_COMENTARIO_MAX) {
        throw new RuntimeException('O comentário pode ter no máximo ' . AVALIACAO_COMENTARIO_MAX . ' caracteres.');
    }
    $permitidos = array_column(produtos_para_avaliar($pdo, $id_pedido, $id_cliente), 'fk_produto');
    if (!in_array($id_produto, array_map('intval', $permitidos), true)) {
        throw new RuntimeException('Você só pode avaliar produtos que comprou e já recebeu (pedido entregue).');
    }
    $st = $pdo->prepare("SELECT id FROM avaliacao WHERE fk_produto = ? AND fk_cliente = ?");
    $st->execute([$id_produto, $id_cliente]);
    $existente = $st->fetchColumn();
    $com = $comentario === '' ? null : $comentario;
    if ($existente) {
        $pdo->prepare("UPDATE avaliacao SET nota = ?, comentario = ? WHERE id = ?")->execute([$nota, $com, $existente]);
    } else {
        $pdo->prepare("INSERT INTO avaliacao (fk_produto, fk_cliente, fk_pedido, nota, comentario) VALUES (?, ?, ?, ?, ?)")
            ->execute([$id_produto, $id_cliente, $id_pedido, $nota, $com]);
    }
}
