<?php

const ESTOQUE_BAIXO = 5;

function movimentar_estoque(
    PDO $pdo,
    int $id_variacao,
    string $tipo,
    int $quantidade,
    string $motivo,
    ?int $fk_pedido = null
): int {
    if (!in_array($tipo, ['entrada', 'saida'], true)) {
        throw new InvalidArgumentException('Tipo de movimentação inválido.');
    }
    if ($quantidade < 1) {
        throw new InvalidArgumentException('A quantidade precisa ser maior que zero.');
    }

    $transacao_propria = !$pdo->inTransaction();
    if ($transacao_propria) {
        $pdo->beginTransaction();
    }

    try {
        $s = $pdo->prepare("SELECT quantidade_estoque FROM variacao_produto WHERE id = ? FOR UPDATE");
        $s->execute([$id_variacao]);
        $atual = $s->fetchColumn();
        if ($atual === false) {
            throw new RuntimeException('Variação de produto não encontrada.');
        }
        $atual = (int)$atual;
        $novo  = $tipo === 'entrada' ? $atual + $quantidade : $atual - $quantidade;

        if ($novo < 0) {
            throw new RuntimeException("Estoque insuficiente: há $atual unidade(s) e a saída é de $quantidade.");
        }

        $pdo->prepare("UPDATE variacao_produto SET quantidade_estoque = ? WHERE id = ?")
            ->execute([$novo, $id_variacao]);

        $pdo->prepare(
            "INSERT INTO movimentacao_estoque (fk_variacao, tipo, quantidade, motivo, fk_pedido)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$id_variacao, $tipo, $quantidade, mb_substr($motivo, 0, 50), $fk_pedido]);

        if ($transacao_propria) {
            $pdo->commit();
        }
        return $novo;
    } catch (Throwable $e) {
        if ($transacao_propria && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function devolver_estoque_do_pedido(PDO $pdo, int $id_pedido): void
{
    $pdo->beginTransaction();
    try {
        $p = $pdo->prepare("SELECT status FROM pedido WHERE id = ? FOR UPDATE");
        $p->execute([$id_pedido]);
        $status = $p->fetchColumn();
        if ($status === false) {
            throw new RuntimeException('Pedido não encontrado.');
        }
        if ($status === 'Cancelado') {
            $pdo->rollBack();
            return;
        }

        $i = $pdo->prepare("SELECT fk_variacao, quantidade FROM item_pedido WHERE fk_pedido = ?");
        $i->execute([$id_pedido]);
        foreach ($i->fetchAll() as $item) {
            movimentar_estoque($pdo, (int)$item['fk_variacao'], 'entrada', (int)$item['quantidade'], 'cancelamento', $id_pedido);
        }

        $pdo->prepare("UPDATE pedido SET status = 'Cancelado' WHERE id = ?")->execute([$id_pedido]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}