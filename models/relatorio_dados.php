<?php

require_once __DIR__ . '/pedido_funcoes.php';

function dados_relatorio(PDO $pdo, string $de, string $ate): array
{
    $ini = $de . ' 00:00:00';
    $fim = date('Y-m-d', strtotime($ate . ' +1 day')) . ' 00:00:00';
    $par = [':ini' => $ini, ':fim' => $fim];
    $no_periodo = "p.data_criacao >= :ini AND p.data_criacao < :fim";

    $q = static function (string $sql) use ($pdo, $par): array {
        $st = $pdo->prepare($sql);
        $st->execute($par);
        return $st->fetchAll();
    };

    $r = $q("SELECT COUNT(*) AS pedidos,
                    COALESCE(SUM(p.valor_total), 0)    AS faturamento
             FROM pedido p
             WHERE $no_periodo AND " . SQL_VENDA_VALIDA)[0];

    $itens = $q("SELECT COALESCE(SUM(ip.quantidade), 0) AS pecas
                 FROM item_pedido ip JOIN pedido p ON p.id = ip.fk_pedido
                 WHERE $no_periodo AND " . SQL_VENDA_VALIDA)[0];

    $canc = $q("SELECT COUNT(*) AS pedidos, COALESCE(SUM(p.valor_total), 0) AS valor
                FROM pedido p WHERE $no_periodo AND p.status = 'Cancelado'")[0];

    $resumo = [
        'pedidos'      => (int)$r['pedidos'],
        'faturamento'  => (float)$r['faturamento'],
        'ticket_medio' => $r['pedidos'] > 0 ? (float)$r['faturamento'] / (int)$r['pedidos'] : 0.0,
        'pecas'        => (int)$itens['pecas'],
        'cancelados'   => (int)$canc['pedidos'],
        'valor_cancelado' => (float)$canc['valor'],
    ];

    $linhas = $q("SELECT DATE(p.data_criacao) AS dia, COUNT(*) AS pedidos, COALESCE(SUM(p.valor_total), 0) AS valor
                  FROM pedido p
                  WHERE $no_periodo AND " . SQL_VENDA_VALIDA . "
                  GROUP BY DATE(p.data_criacao)");
    $mapa = [];
    foreach ($linhas as $l) {
        $mapa[$l['dia']] = $l;
    }
    $por_dia = [];
    for ($d = strtotime($de); $d <= strtotime($ate); $d = strtotime('+1 day', $d)) {
        $k = date('Y-m-d', $d);
        $por_dia[] = [
            'dia'     => $k,
            'pedidos' => (int)($mapa[$k]['pedidos'] ?? 0),
            'valor'   => round((float)($mapa[$k]['valor'] ?? 0), 2),
        ];
    }

    $por_status = $q("SELECT p.status, COUNT(*) AS pedidos, COALESCE(SUM(p.valor_total), 0) AS valor
                      FROM pedido p WHERE $no_periodo
                      GROUP BY p.status ORDER BY pedidos DESC");

    $por_pagamento = $q("SELECT COALESCE(p.forma_pagamento, 'nao_informado') AS forma,
                                COUNT(*) AS pedidos, COALESCE(SUM(p.valor_total), 0) AS valor
                         FROM pedido p
                         WHERE $no_periodo AND " . SQL_VENDA_VALIDA . "
                         GROUP BY COALESCE(p.forma_pagamento, 'nao_informado')
                         ORDER BY valor DESC");

    foreach ($por_pagamento as &$pg) {
        $pg['rotulo'] = rotulo_pagamento($pg['forma'] === 'nao_informado' ? null : $pg['forma']);
    }
    unset($pg);

    $top_produtos = $q("SELECT ip.nome_produto AS nome,
                               SUM(ip.quantidade) AS qtd,
                               SUM(ip.quantidade * ip.preco_unitario) AS receita
                        FROM item_pedido ip JOIN pedido p ON p.id = ip.fk_pedido
                        WHERE $no_periodo AND " . SQL_VENDA_VALIDA . "
                        GROUP BY ip.nome_produto
                        ORDER BY receita DESC, qtd DESC
                        LIMIT 10");

    $por_categoria = $q("SELECT c.nome AS nome,
                                SUM(ip.quantidade) AS qtd,
                                SUM(ip.quantidade * ip.preco_unitario) AS receita
                         FROM item_pedido ip
                         JOIN pedido p             ON p.id  = ip.fk_pedido
                         JOIN variacao_produto v   ON v.id  = ip.fk_variacao
                         JOIN produto pr           ON pr.id = v.fk_produto
                         JOIN categoria c          ON c.id  = pr.fk_categoria
                         WHERE $no_periodo AND " . SQL_VENDA_VALIDA . "
                         GROUP BY c.nome
                         ORDER BY receita DESC");

    $top_clientes = $q("SELECT u.id, u.nome, COUNT(*) AS pedidos, SUM(p.valor_total) AS valor
                        FROM pedido p JOIN usuario u ON u.id = p.fk_cliente
                        WHERE $no_periodo AND " . SQL_VENDA_VALIDA . "
                        GROUP BY u.id, u.nome
                        ORDER BY valor DESC
                        LIMIT 5");

    return compact('de', 'ate', 'resumo', 'por_dia', 'por_status', 'por_pagamento',
                   'top_produtos', 'por_categoria', 'top_clientes');
}

function rotulo_pagamento(?string $forma): string
{
    return FORMAS_PAGAMENTO[$forma ?? ''] ?? 'Não informado';
}

function json_para_pagina(array $dados): string
{
    return json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
