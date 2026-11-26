<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/usuarios.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const CHAT_MAX_CARACTERES = 1000;

function resposta(array $dados, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

function erro(string $msg, int $codigo = 400, array $extra = []): void
{
    resposta(['ok' => false, 'erro' => $msg] + $extra, $codigo);
}

$id_usuario = (int)($_SESSION['usuario_id'] ?? 0);
$tipo       = $_SESSION['usuario_tipo'] ?? '';

if ($id_usuario <= 0) {
    erro('Faça login para usar o chat.', 401, ['login' => true]);
}

$eh_cliente = ($tipo === 'cliente');
$eh_equipe  = in_array($tipo, ['admin', 'vendedor'], true);
if (!$eh_cliente && !$eh_equipe) {
    erro('Tipo de usuário sem acesso ao chat.', 403);
}

$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

if (in_array($acao, ['enviar', 'encerrar'], true)) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        erro('Método inválido.', 405);
    }
    if (!hash_equals($_SESSION['csrf_chat'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        erro('Sessão expirada. Recarregue a página e tente de novo.', 403);
    }
}

function buscar_conversa(PDO $pdo, int $id_conversa, int $id_usuario, bool $eh_equipe): ?array
{
    if ($id_conversa <= 0) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT id, fk_cliente, fk_atendente, status FROM conversa WHERE id = ? LIMIT 1");
    $stmt->execute([$id_conversa]);
    $c = $stmt->fetch();
    if (!$c) {
        return null;
    }
    if (!$eh_equipe && (int)$c['fk_cliente'] !== $id_usuario) {
        return null;
    }
    return $c;
}

try {
    switch ($acao) {

        case 'estado':
            if (!$eh_cliente) {
                erro('Ação exclusiva de clientes.', 403);
            }
            $stmt = $pdo->prepare("SELECT id, status FROM conversa WHERE fk_cliente = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$id_usuario]);
            $conversa = $stmt->fetch() ?: null;

            $nao_lidas = 0;
            if ($conversa) {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM mensagem
                     WHERE fk_conversa = ? AND fk_usuario_remetente <> ? AND lida = 0"
                );
                $stmt->execute([$conversa['id'], $id_usuario]);
                $nao_lidas = (int)$stmt->fetchColumn();
            }
            resposta([
                'ok'        => true,
                'conversa'  => $conversa ? ['id' => (int)$conversa['id'], 'status' => $conversa['status']] : null,
                'nao_lidas' => $nao_lidas,
            ]);

        case 'listar':
            if (!$eh_equipe) {
                erro('Ação exclusiva da equipe.', 403);
            }
            $status = (($_GET['status'] ?? 'Aberta') === 'Encerrada') ? 'Encerrada' : 'Aberta';

            $stmt = $pdo->prepare(
                "SELECT c.id AS conversa_id, c.status, c.data_criacao,
                        u.nome AS cliente_nome, a.nome AS atendente_nome,
                        (SELECT m.conteudo FROM mensagem m WHERE m.fk_conversa = c.id ORDER BY m.id DESC LIMIT 1) AS ultima,
                        COALESCE((SELECT MAX(m.data_envio) FROM mensagem m WHERE m.fk_conversa = c.id), c.data_criacao) AS atividade,
                        (SELECT COUNT(*) FROM mensagem m
                           JOIN usuario r ON r.id = m.fk_usuario_remetente
                          WHERE m.fk_conversa = c.id AND r.tipo_usuario = 'cliente' AND m.lida = 0) AS nao_lidas
                 FROM conversa c
                 JOIN usuario u ON u.id = c.fk_cliente
                 LEFT JOIN usuario a ON a.id = c.fk_atendente
                 WHERE c.status = ?
                 ORDER BY atividade DESC
                 LIMIT 100"
            );
            $stmt->execute([$status]);
            $conversas = $stmt->fetchAll();
            foreach ($conversas as &$c) {
                $c['conversa_id'] = (int)$c['conversa_id'];
                $c['nao_lidas']   = (int)$c['nao_lidas'];
            }
            unset($c);

            $total = (int)$pdo->query(
                "SELECT COUNT(*) FROM mensagem m
                   JOIN conversa c ON c.id = m.fk_conversa
                   JOIN usuario r ON r.id = m.fk_usuario_remetente
                  WHERE c.status = 'Aberta' AND r.tipo_usuario = 'cliente' AND m.lida = 0"
            )->fetchColumn();

            resposta(['ok' => true, 'conversas' => $conversas, 'total_nao_lidas' => $total]);

        case 'mensagens':
            $conversa = buscar_conversa($pdo, (int)($_GET['conversa_id'] ?? 0), $id_usuario, $eh_equipe);
            if (!$conversa) {
                erro('Conversa não encontrada.', 404);
            }
            $depois_id = max(0, (int)($_GET['depois_id'] ?? 0));

            $stmt = $pdo->prepare(
                "SELECT m.id, m.conteudo, m.data_envio, u.nome AS autor, u.tipo_usuario AS tipo_autor
                 FROM mensagem m
                 JOIN usuario u ON u.id = m.fk_usuario_remetente
                 WHERE m.fk_conversa = ? AND m.id > ?
                 ORDER BY m.id ASC
                 LIMIT 200"
            );
            $stmt->execute([$conversa['id'], $depois_id]);

            $mensagens = [];
            foreach ($stmt->fetchAll() as $m) {
                $mensagens[] = [
                    'id'       => (int)$m['id'],
                    'de'       => ($m['tipo_autor'] === 'cliente') ? 'cliente' : 'equipe',
                    'autor'    => $m['autor'],
                    'conteudo' => $m['conteudo'],
                    'data'     => $m['data_envio'],
                ];
            }

            if ($eh_equipe) {
                $pdo->prepare(
                    "UPDATE mensagem m JOIN usuario r ON r.id = m.fk_usuario_remetente
                        SET m.lida = 1
                      WHERE m.fk_conversa = ? AND r.tipo_usuario = 'cliente' AND m.lida = 0"
                )->execute([$conversa['id']]);
            } else {
                $pdo->prepare(
                    "UPDATE mensagem SET lida = 1
                     WHERE fk_conversa = ? AND fk_usuario_remetente <> ? AND lida = 0"
                )->execute([$conversa['id'], $id_usuario]);
            }

            resposta([
                'ok'        => true,
                'conversa'  => ['id' => (int)$conversa['id'], 'status' => $conversa['status']],
                'mensagens' => $mensagens,
            ]);

        case 'enviar':
            $conteudo = trim((string)($_POST['conteudo'] ?? ''));
            $conteudo = str_replace("\0", '', $conteudo);
            if ($conteudo === '') {
                erro('Digite uma mensagem.');
            }
            if (mb_strlen($conteudo) > CHAT_MAX_CARACTERES) {
                erro('A mensagem pode ter no máximo ' . CHAT_MAX_CARACTERES . ' caracteres.');
            }

            if ($eh_cliente) {
                $stmt = $pdo->prepare(
                    "SELECT id FROM conversa WHERE fk_cliente = ? AND status = 'Aberta' ORDER BY id DESC LIMIT 1"
                );
                $stmt->execute([$id_usuario]);
                $id_conversa = (int)$stmt->fetchColumn();

                if ($id_conversa <= 0) {
                    garantir_perfil_filho($pdo, $id_usuario, 'cliente');
                    $pdo->prepare("INSERT INTO conversa (fk_cliente, status) VALUES (?, 'Aberta')")
                        ->execute([$id_usuario]);
                    $id_conversa = (int)$pdo->lastInsertId();
                }
            } else {
                $conversa = buscar_conversa($pdo, (int)($_POST['conversa_id'] ?? 0), $id_usuario, true);
                if (!$conversa) {
                    erro('Conversa não encontrada.', 404);
                }
                if ($conversa['status'] !== 'Aberta') {
                    erro('Esta conversa já foi encerrada.', 409);
                }
                $id_conversa = (int)$conversa['id'];

                if ($conversa['fk_atendente'] === null) {
                    garantir_perfil_filho($pdo, $id_usuario, $tipo);
                    $pdo->prepare("UPDATE conversa SET fk_atendente = ? WHERE id = ? AND fk_atendente IS NULL")
                        ->execute([$id_usuario, $id_conversa]);
                }
            }

            $pdo->prepare("INSERT INTO mensagem (fk_conversa, fk_usuario_remetente, conteudo) VALUES (?, ?, ?)")
                ->execute([$id_conversa, $id_usuario, $conteudo]);

            resposta(['ok' => true, 'conversa_id' => $id_conversa, 'mensagem_id' => (int)$pdo->lastInsertId()]);

        case 'encerrar':
            $conversa = buscar_conversa($pdo, (int)($_POST['conversa_id'] ?? 0), $id_usuario, $eh_equipe);
            if (!$conversa) {
                erro('Conversa não encontrada.', 404);
            }
            $pdo->prepare("UPDATE conversa SET status = 'Encerrada' WHERE id = ? AND status = 'Aberta'")
                ->execute([$conversa['id']]);
            resposta(['ok' => true]);

        default:
            erro('Ação inválida.');
    }
} catch (PDOException $e) {
    error_log('Erro no chat: ' . $e->getMessage());
    erro('Não foi possível concluir agora. Tente novamente.', 500);
}
