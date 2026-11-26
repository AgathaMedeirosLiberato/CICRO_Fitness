<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/cpf_cripto.php';

use Brazanation\Documents\Cpf;

function cpf_normalizar(?string $texto): ?string
{
    $texto = trim((string)$texto);
    if ($texto === '' || preg_match('/[^0-9.\-\s]/', $texto)) {
        return null;
    }
    $cpf = Cpf::createFromString($texto);
    return $cpf ? $cpf->format() : null;
}

function cpf_ocultar(?string $cpf): string
{
    if (!$cpf) {
        return 'não informado';
    }
    return '***.***.' . substr($cpf, 8);
}

function cpf_em_uso(PDO $pdo, string $cpf, int $exceto_usuario = 0): bool
{
    $s = $pdo->prepare("SELECT 1 FROM cliente WHERE (cpf_hash = ? OR cpf = ?) AND id_usuario <> ? LIMIT 1");
    $s->execute([cpf_indice($cpf), $cpf, $exceto_usuario]);
    return (bool)$s->fetchColumn();
}

function cpf_validar_externo(string $cpf): bool
{
    return true;
}
