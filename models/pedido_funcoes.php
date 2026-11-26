<?php

require_once __DIR__ . '/../config/config_pagamento.php';

const STATUS_PEDIDO = ['Pendente', 'Pago', 'Em Preparação', 'Enviado', 'Entregue', 'Cancelado'];
const STATUS_ADMIN = ['Pendente', 'Pago', 'Em Preparação', 'Enviado', 'Entregue'];

const SQL_VENDA_VALIDA = "p.status IN ('Pago','Em Preparação','Enviado','Entregue')";

const FORMAS_PAGAMENTO = [
    'pix'    => 'Pix',
    'cartao' => 'Cartão de crédito',
    'boleto' => 'Boleto',
];

if (!function_exists('brl')) {
    function brl($v): string
    {
        return 'R$ ' . number_format((float)$v, 2, ',', '.');
    }
}

function formatar_endereco(array $e): string
{
    $linha = $e['rua'] . ', nº ' . $e['numero'];
    if (!empty($e['complemento'])) {
        $linha .= ' - ' . $e['complemento'];
    }
    return $linha . ' · ' . $e['bairro'] . ' · ' . $e['cidade'] . '/' . $e['estado'] . ' · CEP ' . $e['cep'];
}

function classe_status(string $status): string
{
    $sem_acento = strtr($status, ['ç' => 'c', 'Ç' => 'C', 'ã' => 'a', 'Ã' => 'A', 'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'â' => 'a', 'ê' => 'e', 'ô' => 'o']);
    return 'st-' . strtolower(preg_replace('/[^a-z]/i', '', $sem_acento));
}

function nome_do_cliente_do_pedido(PDO $pdo, int $id_pedido): string
{
    $st = $pdo->prepare("SELECT u.nome FROM pedido p JOIN usuario u ON u.id = p.fk_cliente WHERE p.id = ?");
    $st->execute([$id_pedido]);
    return (string)($st->fetchColumn() ?: '');
}

function periodo_do_filtro(array $get): array
{
    $valida = static function ($s) {
        $d = DateTime::createFromFormat('Y-m-d', (string)$s);
        return ($d && $d->format('Y-m-d') === $s) ? $s : null;
    };
    $ate = $valida($get['ate'] ?? '') ?? date('Y-m-d');
    $de  = $valida($get['de']  ?? '') ?? date('Y-m-d', strtotime($ate . ' -29 days'));
    if ($de > $ate) {
        [$de, $ate] = [$ate, $de];
    }
    return [$de, $ate];
}

function itens_dos_pedidos(PDO $pdo, array $ids_pedido): array
{
    $ids_pedido = array_values(array_unique(array_map('intval', $ids_pedido)));
    if (!$ids_pedido) {
        return [];
    }
    $marcas = implode(',', array_fill(0, count($ids_pedido), '?'));
    $st = $pdo->prepare(
        "SELECT fk_pedido, nome_produto, tamanho, cor, quantidade, preco_unitario
         FROM item_pedido WHERE fk_pedido IN ($marcas) ORDER BY id ASC"
    );
    $st->execute($ids_pedido);
    $por_pedido = [];
    foreach ($st->fetchAll() as $linha) {
        $por_pedido[(int)$linha['fk_pedido']][] = $linha;
    }
    return $por_pedido;
}

function alterar_status_pedido(PDO $pdo, int $id_pedido, string $novo, bool $expirou = false): void
{
    if (!in_array($novo, STATUS_PEDIDO, true)) {
        throw new RuntimeException('Status inválido.');
    }
    $st = $pdo->prepare("SELECT status FROM pedido WHERE id = ?");
    $st->execute([$id_pedido]);
    $atual = $st->fetchColumn();
    if ($atual === false) {
        throw new RuntimeException('Pedido não encontrado.');
    }
    if ($atual === $novo) {
        return;
    }
    if ($atual === 'Cancelado') {
        throw new RuntimeException('Pedido cancelado não pode ser reativado (o estoque já foi devolvido). Peça ao cliente para fazer um novo pedido.');
    }
    require_once __DIR__ . '/pagamento_funcoes.php';
    if ($novo === 'Cancelado') {
        require_once __DIR__ . '/estoque_funcoes.php';
        devolver_estoque_do_pedido($pdo, $id_pedido);
        sincronizar_pagamento($pdo, $id_pedido, 'Cancelado', $expirou);
        return;
    }
    if ($novo === 'Pendente' && $atual !== 'Pendente') {
        throw new RuntimeException('Um pedido já pago não volta a "Pendente".');
    }
    if ($novo === 'Pago') {
        $pdo->prepare("UPDATE pedido SET status = 'Pago' WHERE id = ?")->execute([$id_pedido]);
        sincronizar_pagamento($pdo, $id_pedido, 'Pago');
        return;
    }
    if (in_array($novo, ['Em Preparação', 'Enviado', 'Entregue'], true) && $atual === 'Pendente') {
        throw new RuntimeException('Confirme o pagamento (status "Pago") antes de preparar ou enviar o pedido.');
    }
    $pdo->prepare("UPDATE pedido SET status = ? WHERE id = ?")->execute([$novo, $id_pedido]);
}

function cancelar_pedido_do_cliente(PDO $pdo, int $id_pedido, int $id_cliente): void
{
    $st = $pdo->prepare("SELECT status FROM pedido WHERE id = ? AND fk_cliente = ?");
    $st->execute([$id_pedido, $id_cliente]);
    $status = $st->fetchColumn();
    if ($status === false) {
        throw new RuntimeException('Pedido não encontrado.');
    }
    if ($status === 'Cancelado') {
        throw new RuntimeException('Este pedido já foi cancelado.');
    }
    if (!in_array($status, ['Pendente', 'Pago'], true)) {
        throw new RuntimeException('Este pedido já está em preparação ou foi enviado e não pode mais ser cancelado. Fale com o Suporte.');
    }
    alterar_status_pedido($pdo, $id_pedido, 'Cancelado');
}

function expirar_pedidos_pendentes(PDO $pdo): int
{
    $agora  = (string)$pdo->query("SELECT NOW()")->fetchColumn();
    $limite = date('Y-m-d H:i:s', strtotime($agora) - PEDIDO_PRAZO_PAGAMENTO_HORAS * 3600);
    $st = $pdo->prepare("SELECT id FROM pedido WHERE status = 'Pendente' AND data_criacao < ? ORDER BY id LIMIT 50");
    $st->execute([$limite]);
    $n = 0;
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
        try {
            alterar_status_pedido($pdo, (int)$id, 'Cancelado', true);
            $n++;
        } catch (Throwable $e) {
            error_log('Não foi possível expirar o pedido ' . $id . ': ' . $e->getMessage());
        }
    }
    return $n;
}
