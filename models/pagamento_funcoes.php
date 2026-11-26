<?php
require_once __DIR__ . '/../config/config_pagamento.php';
require_once __DIR__ . '/pedido_funcoes.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Piggly\Pix\Parser;
use Piggly\Pix\StaticPayload;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

const FORMAS_FUTURAS = ['cartao', 'boleto'];

function pix_configurado(): bool
{
    return trim(PIX_CHAVE) !== '';
}

function formas_disponiveis(): array
{
    return pix_configurado() ? ['pix' => FORMAS_PAGAMENTO['pix']] : [];
}

const PAGAMENTO_STATUS = ['aguardando', 'pago', 'cancelado', 'expirado', 'estornado'];

const ROTULO_PAGAMENTO = ['aguardando' => 'Aguardando pagamento', 'pago' => 'Pago', 'cancelado' => 'Cancelado', 'expirado' => 'Expirado (prazo vencido)', 'estornado' => 'Estornado'];

function pix_demonstracao(): bool
{
    return PIX_DEMONSTRACAO;
}

function pix_txid(int $id_pedido): string
{
    return 'CICRO' . str_pad((string)$id_pedido, 6, '0', STR_PAD_LEFT);
}

function pix_codigo(int $id_pedido, float $valor): string
{
    if (!pix_configurado()) {
        throw new RuntimeException('O Pix ainda não foi configurado pela loja.');
    }
    $tipos = ['document' => Parser::KEY_TYPE_DOCUMENT, 'email' => Parser::KEY_TYPE_EMAIL,
              'phone' => Parser::KEY_TYPE_PHONE, 'random' => Parser::KEY_TYPE_RANDOM];
    if (!isset($tipos[PIX_TIPO_CHAVE])) {
        throw new RuntimeException('Tipo de chave Pix inválido em config/config_pagamento.php.');
    }
    try {
        $txid = pix_txid($id_pedido);
        $payload = (new StaticPayload())
            ->setPixKey($tipos[PIX_TIPO_CHAVE], trim(PIX_CHAVE))
            ->setMerchantName(PIX_NOME)
            ->setMerchantCity(PIX_CIDADE)
            ->setAmount(round($valor, 2))
            ->setTid($txid);
        return $payload->getPixCode();
    } catch (Throwable $e) {
        error_log('Erro ao gerar Pix do pedido ' . $id_pedido . ': ' . $e->getMessage());
        throw new RuntimeException('Não foi possível gerar o Pix. Confira a chave em config/config_pagamento.php.');
    }
}

function pix_qr(string $codigo): string
{
    return (new QRCode(new QROptions([
        'outputType'  => QRCode::OUTPUT_MARKUP_SVG,
        'eccLevel'    => QRCode::ECC_M,
        'imageBase64' => true,
        'svgViewBox'  => true,
    ])))->render($codigo);
}

function criar_pagamento_pix(PDO $pdo, int $id_pedido, float $valor): void
{
    $pdo->prepare(
        "INSERT INTO pagamento (fk_pedido, metodo, status, valor, txid, codigo, simulado)
         VALUES (?, 'pix', 'aguardando', ?, ?, ?, ?)"
    )->execute([$id_pedido, round($valor, 2), pix_txid($id_pedido), pix_codigo($id_pedido, $valor), pix_demonstracao() ? 1 : 0]);
}

function pagamento_do_pedido(PDO $pdo, int $id_pedido): ?array
{
    $st = $pdo->prepare("SELECT * FROM pagamento WHERE fk_pedido = ?");
    $st->execute([$id_pedido]);
    $pg = $st->fetch();
    if ($pg) {
        return $pg;
    }
    $pd = $pdo->prepare("SELECT status, valor_total, forma_pagamento FROM pedido WHERE id = ?");
    $pd->execute([$id_pedido]);
    $p = $pd->fetch();
    if (!$p || ($p['forma_pagamento'] ?? 'pix') !== 'pix' || !pix_configurado()) {
        return null;
    }
    $mapa = ['Pendente' => 'aguardando', 'Cancelado' => 'cancelado'];
    $status = $mapa[$p['status']] ?? 'pago';
    $pdo->prepare(
        "INSERT INTO pagamento (fk_pedido, metodo, status, valor, txid, codigo, simulado, data_pagamento)
         VALUES (?, 'pix', ?, ?, ?, ?, ?, " . ($status === 'pago' ? 'CURRENT_TIMESTAMP' : 'NULL') . ")"
    )->execute([$id_pedido, $status, $p['valor_total'], pix_txid($id_pedido), pix_codigo($id_pedido, (float)$p['valor_total']), pix_demonstracao() ? 1 : 0]);
    return pagamento_do_pedido($pdo, $id_pedido);
}

function sincronizar_pagamento(PDO $pdo, int $id_pedido, string $novo_pedido, bool $expirou = false): void
{
    $pg = pagamento_do_pedido($pdo, $id_pedido);
    if (!$pg) {
        return;
    }
    if ($novo_pedido === 'Pago') {
        $pdo->prepare("UPDATE pagamento SET status = 'pago', data_pagamento = COALESCE(data_pagamento, CURRENT_TIMESTAMP) WHERE fk_pedido = ?")
            ->execute([$id_pedido]);
    } elseif ($novo_pedido === 'Cancelado') {
        $novo = $pg['status'] === 'pago' ? 'estornado' : ($expirou ? 'expirado' : 'cancelado');
        if (in_array($pg['status'], ['aguardando', 'pago'], true)) {
            $pdo->prepare("UPDATE pagamento SET status = ? WHERE fk_pedido = ?")->execute([$novo, $id_pedido]);
        }
    }
}
