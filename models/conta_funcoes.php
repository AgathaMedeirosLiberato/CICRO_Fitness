<?php

const EMAIL_CONTA_EXCLUIDA = 'conta-excluida@cicro.invalid';

function pedidos_em_andamento(PDO $pdo, int $id_cliente): int
{
    $s = $pdo->prepare("SELECT COUNT(*) FROM pedido WHERE fk_cliente = ? AND status IN ('Pendente','Pago','Em Preparação','Enviado')");
    $s->execute([$id_cliente]);
    return (int)$s->fetchColumn();
}

function conta_cliente_excluido(PDO $pdo): int
{
    $s = $pdo->prepare("SELECT id FROM usuario WHERE email = ?");
    $s->execute([EMAIL_CONTA_EXCLUIDA]);
    $id = $s->fetchColumn();
    if ($id) {
        return (int)$id;
    }
    $pdo->prepare(
        "INSERT INTO usuario (nome, email, senha_hash, tipo_usuario, ativo, data_exclusao, email_verificado)
         VALUES ('Cliente excluído', ?, ?, 'cliente', 0, NOW(), 1)"
    )->execute([EMAIL_CONTA_EXCLUIDA, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
    $id = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO cliente (id_usuario, cpf) VALUES (?, NULL)")->execute([$id]);
    return $id;
}

function excluir_conta_cliente(PDO $pdo, int $id): void
{
    $pdo->beginTransaction();
    try {
        $anonimo = conta_cliente_excluido($pdo);
        $pdo->prepare("UPDATE pedido SET fk_cliente = ?, fk_endereco = NULL, endereco_entrega = 'Removido (conta excluída)' WHERE fk_cliente = ?")
            ->execute([$anonimo, $id]);

        $pdo->prepare("DELETE FROM avaliacao WHERE fk_cliente = ?")->execute([$id]);

        $pdo->prepare("DELETE FROM item_carrinho WHERE fk_carrinho IN (SELECT id FROM carrinho WHERE fk_cliente = ?)")->execute([$id]);
        $pdo->prepare("DELETE FROM categoria_carrinho WHERE fk_carrinho IN (SELECT id FROM carrinho WHERE fk_cliente = ?)")->execute([$id]);
        $pdo->prepare("DELETE FROM carrinho WHERE fk_cliente = ?")->execute([$id]);

        $pdo->prepare("DELETE FROM mensagem WHERE fk_conversa IN (SELECT id FROM conversa WHERE fk_cliente = ?) OR fk_usuario_remetente = ?")->execute([$id, $id]);
        $pdo->prepare("DELETE FROM conversa WHERE fk_cliente = ?")->execute([$id]);

        $pdo->prepare("DELETE FROM endereco WHERE fk_cliente = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM solicitacao_cpf WHERE fk_cliente = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM cliente WHERE id_usuario = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM usuario WHERE id = ? AND tipo_usuario = 'cliente'")->execute([$id]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function ultima_solicitacao_cpf(PDO $pdo, int $id_cliente): ?array
{
    try {
        $s = $pdo->prepare("SELECT * FROM solicitacao_cpf WHERE fk_cliente = ? ORDER BY id DESC LIMIT 1");
        $s->execute([$id_cliente]);
        return $s->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('solicitacao_cpf: ' . $e->getMessage());
        return null;
    }
}
