<?php

const CPF_ARQUIVO_CHAVE = __DIR__ . '/../config/chave_cpf.php';
const CPF_PREFIXO       = 'v1:';

function cpf_chave(string $uso): string
{
    static $mestra = null;
    if ($mestra === null) {
        $lida = is_file(CPF_ARQUIVO_CHAVE) ? base64_decode((string)(require CPF_ARQUIVO_CHAVE), true) : false;
        if ($lida === false || strlen($lida) !== 32) {
            throw new RuntimeException('Chave do CPF ausente ou inválida. Rode ferramentas/cifrar_cpf.php (veja docs/CRIPTOGRAFIA_CPF.md).');
        }
        $mestra = $lida;
    }
    return hash_hmac('sha256', 'cicro-cpf-' . $uso, $mestra, true);
}

function cpf_esta_cifrado(?string $valor): bool
{
    return $valor !== null && strncmp($valor, CPF_PREFIXO, strlen(CPF_PREFIXO)) === 0;
}

function cpf_cifrar(?string $cpf): ?string
{
    if ($cpf === null || $cpf === '') {
        return null;
    }
    $iv  = random_bytes(12);
    $tag = '';
    $cifrado = openssl_encrypt($cpf, 'aes-256-gcm', cpf_chave('cifra'), OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($cifrado === false) {
        throw new RuntimeException('Não foi possível cifrar o CPF.');
    }
    return CPF_PREFIXO . base64_encode($iv . $tag . $cifrado);
}

function cpf_decifrar(?string $valor): ?string
{
    if ($valor === null || $valor === '') {
        return null;
    }
    if (!cpf_esta_cifrado($valor)) {
        return $valor;
    }
    $bruto = base64_decode(substr($valor, strlen(CPF_PREFIXO)), true);
    $cpf = ($bruto !== false && strlen($bruto) > 28)
        ? openssl_decrypt(substr($bruto, 28), 'aes-256-gcm', cpf_chave('cifra'), OPENSSL_RAW_DATA,
                          substr($bruto, 0, 12), substr($bruto, 12, 16))
        : false;
    if ($cpf === false) {
        throw new RuntimeException('Não foi possível decifrar o CPF (chave errada ou dado corrompido).');
    }
    return $cpf;
}

function cpf_exibir(?string $valor): ?string
{
    try {
        return cpf_decifrar($valor);
    } catch (RuntimeException $e) {
        error_log('cpf: ' . $e->getMessage());
        return 'indisponível';
    }
}

function cpf_indice(?string $cpf): ?string
{
    $numeros = preg_replace('/\D/', '', (string)$cpf);
    return $numeros === '' ? null : hash_hmac('sha256', $numeros, cpf_chave('indice'));
}
