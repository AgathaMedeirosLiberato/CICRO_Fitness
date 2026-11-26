<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$tipo = $_SESSION['usuario_tipo'] ?? '';
if (!isset($_SESSION['usuario_id']) || !in_array($tipo, ['admin', 'vendedor'], true)) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}
session_write_close();

require_once __DIR__ . '/../config/conexao.php';

$resp = ['ok' => true, 'ultimo_id' => 0, 'pedidos' => [], 'total_novos' => 0, 'cpf_pendentes' => 0, 'cpf_ultimo_id' => 0];
try {
    $resp['ultimo_id'] = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) FROM pedido")->fetchColumn();

    if (isset($_GET['desde']) && ctype_digit((string)$_GET['desde'])) {
        $desde = (int)$_GET['desde'];
        $st = $pdo->prepare("SELECT COUNT(*) FROM pedido WHERE id > ?");
        $st->execute([$desde]);
        $resp['total_novos'] = (int)$st->fetchColumn();

        $st = $pdo->prepare(
            "SELECT p.id, p.valor_total, p.data_criacao, u.nome,
                    (SELECT COALESCE(SUM(quantidade), 0) FROM item_pedido WHERE fk_pedido = p.id) AS pecas
             FROM pedido p JOIN usuario u ON u.id = p.fk_cliente
             WHERE p.id > ? ORDER BY p.id LIMIT 10"
        );
        $st->execute([$desde]);
        foreach ($st->fetchAll() as $p) {
            $resp['pedidos'][] = [
                'id'    => (int)$p['id'],
                'nome'  => $p['nome'],
                'total' => (float)$p['valor_total'],
                'pecas' => (int)$p['pecas'],
                'hora'  => date('d/m H:i', strtotime($p['data_criacao'])),
            ];
        }
    }

    if ($tipo === 'admin') {
        try {
            $r = $pdo->query("SELECT COUNT(*), COALESCE(MAX(id), 0) FROM solicitacao_cpf WHERE status = 'pendente'")->fetch(PDO::FETCH_NUM);
            $resp['cpf_pendentes'] = (int)$r[0];
            $resp['cpf_ultimo_id'] = (int)$r[1];
        } catch (PDOException $e) { }
    }
} catch (PDOException $e) {
    error_log('notificacoes_admin: ' . $e->getMessage());
    $resp['ok'] = false;
    $resp['erro'] = $e->getMessage();
}
echo json_encode($resp);
