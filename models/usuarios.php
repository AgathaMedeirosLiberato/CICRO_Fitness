<?php
require_once __DIR__ . '/cpf_cripto.php';

const CARGOS_PADRAO = [
    'admin'    => 'Administrador',
    'vendedor' => 'Vendedor',
];

function garantir_perfil_filho(PDO $pdo, int $id_usuario, string $tipo_usuario, ?string $cpf = null): void
{
    if ($tipo_usuario === 'cliente') {
        $st = $pdo->prepare("SELECT 1 FROM cliente WHERE id_usuario = ?");
        $st->execute([$id_usuario]);
        if (!$st->fetchColumn()) {
            $pdo->prepare("INSERT INTO cliente (id_usuario, cpf, cpf_hash) VALUES (?, ?, ?)")
                ->execute([$id_usuario, cpf_cifrar($cpf), $cpf === null ? null : cpf_indice($cpf)]);
        }
        return;
    }

    if (isset(CARGOS_PADRAO[$tipo_usuario])) {
        $pdo->prepare("INSERT IGNORE INTO funcionario (id_usuario, cargo) VALUES (?, ?)")
            ->execute([$id_usuario, CARGOS_PADRAO[$tipo_usuario]]);
    }
}

function criar_usuario(PDO $pdo, string $nome, string $email, string $senha, string $tipo_usuario = 'cliente',
                       ?string $cpf = null, bool $aceitou_termos = false): int
{
    if (!in_array($tipo_usuario, ['cliente', 'vendedor', 'admin'], true)) {
        throw new InvalidArgumentException('Tipo de usuário inválido.');
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            "INSERT INTO usuario (nome, email, senha_hash, tipo_usuario, ativo, email_verificado, aceite_termos, data_aceite_termos, data_cadastro)
             VALUES (?, ?, ?, ?, 1, ?, ?, " . ($aceitou_termos ? 'NOW()' : 'NULL') . ", NOW())"
        )->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $tipo_usuario,
                    $tipo_usuario === 'cliente' ? 0 : 1,
                    $aceitou_termos ? 1 : 0]);

        $id = (int)$pdo->lastInsertId();
        garantir_perfil_filho($pdo, $id, $tipo_usuario, $cpf);

        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}


function limpar_cadastros_nao_confirmados(PDO $pdo, string $email, ?string $cpf): void
{
    $st = $pdo->prepare(
        "SELECT u.id FROM usuario u LEFT JOIN cliente c ON c.id_usuario = u.id
         WHERE u.tipo_usuario = 'cliente' AND u.email_verificado = 0 AND u.google_id IS NULL
           AND (u.email = ? OR c.cpf_hash = ? OR c.cpf = ?)"
    );
    $st->execute([$email, $cpf === null ? null : cpf_indice($cpf), $cpf]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM cliente WHERE id_usuario = ?")->execute([(int)$id]);
            $pdo->prepare("DELETE FROM usuario WHERE id = ? AND email_verificado = 0")->execute([(int)$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
        }
    }
}
