<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/cpf_cripto.php';

function parar(string $msg): void
{
    fwrite(STDERR, "ERRO: $msg\n");
    exit(1);
}

try {
    $pdo->query("SELECT cpf_hash FROM cliente LIMIT 1");
} catch (PDOException $e) {
    parar('a coluna cliente.cpf_hash não existe. Rode antes banco/migracao_v10.sql (inclui a v11) no phpMyAdmin.');
}

if (!is_file(CPF_ARQUIVO_CHAVE)) {
    $ja = (int)$pdo->query("SELECT COUNT(*) FROM cliente WHERE cpf LIKE 'v1:%'")->fetchColumn();
    if ($ja > 0) {
        parar("há $ja CPF(s) cifrado(s), mas config/chave_cpf.php sumiu. Restaure a cópia da chave.");
    }
    $conteudo = "<?php\n// Chave da criptografia do CPF. NÃO compartilhe, não envie ao GitHub e guarde uma cópia.\n"
              . "return '" . base64_encode(random_bytes(32)) . "';\n";
    $f = @fopen(CPF_ARQUIVO_CHAVE, 'x');
    if (!$f || fwrite($f, $conteudo) !== strlen($conteudo)) {
        parar('não foi possível criar config/chave_cpf.php.');
    }
    fclose($f);
    echo "Chave criada em config/chave_cpf.php\n";
}

$pdo->beginTransaction();
try {
    $cifrados = 0;
    $gravar = $pdo->prepare("UPDATE cliente SET cpf = ?, cpf_hash = ? WHERE id_usuario = ?");
    foreach ($pdo->query("SELECT id_usuario, cpf, cpf_hash FROM cliente WHERE cpf IS NOT NULL")->fetchAll() as $c) {
        $cpf = cpf_decifrar($c['cpf']);
        if ($cpf === null) {
            continue;
        }
        $indice = cpf_indice($cpf);
        if (cpf_esta_cifrado($c['cpf']) && $c['cpf_hash'] === $indice) {
            continue;
        }
        try {
            $gravar->execute([cpf_cifrar($cpf), $indice, $c['id_usuario']]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException("o cliente {$c['id_usuario']} tem o mesmo CPF de outra conta. Corrija e rode de novo.");
            }
            throw $e;
        }
        $cifrados++;
    }

    $pedidos = 0;
    $gravar = $pdo->prepare("UPDATE solicitacao_cpf SET cpf_atual = ? WHERE id = ?");
    foreach ($pdo->query("SELECT id, cpf_atual FROM solicitacao_cpf WHERE cpf_atual IS NOT NULL")->fetchAll() as $s) {
        if ($s['cpf_atual'] === '' || cpf_esta_cifrado($s['cpf_atual'])) {
            continue;
        }
        $gravar->execute([cpf_cifrar($s['cpf_atual']), $s['id']]);
        $pedidos++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    parar($e->getMessage() . ' Nenhum registro foi alterado.');
}

echo "Pronto. CPFs de clientes cifrados agora: $cifrados | em pedidos de troca: $pedidos\n";
echo "Guarde uma cópia de config/chave_cpf.php em lugar seguro: sem ela os CPFs não podem ser recuperados.\n";
