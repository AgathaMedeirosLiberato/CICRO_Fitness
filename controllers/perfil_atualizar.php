<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/cpf_funcoes.php';
require_once __DIR__ . '/../models/conta_funcoes.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../views/user/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/user/perfil.php');
    exit;
}

function voltar(string $secao, string $msg, bool $ok = false): void
{
    $_SESSION['flash_perfil'] = ['secao' => $secao, 'ok' => $ok, 'msg' => $msg];
    header('Location: ../views/user/perfil.php');
    exit;
}

$id   = (int)$_SESSION['usuario_id'];
$acao = $_POST['acao'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT nome, email, senha_hash, tipo_usuario FROM usuario WHERE id = ? AND ativo = 1");
    $stmt->execute([$id]);
    $atual = $stmt->fetch();
    if (!$atual) {
        header('Location: logout.php');
        exit;
    }

    if ($acao === 'dados') {
        $nome     = trim($_POST['nome'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $senha_atual = $_POST['senha_atual'] ?? '';

        if ($nome === '' || mb_strlen($nome) > 150) {
            voltar('dados', 'Informe um nome válido (até 150 caracteres).');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            voltar('dados', 'Informe um e-mail válido.');
        }

        $tel_final = null;
        if ($telefone !== '') {
            if (preg_match('/[^0-9()\s\-]/', $telefone)) {
                voltar('dados', 'Telefone inválido. Use apenas números, ex.: (48) 99999-9999.');
            }
            $d = preg_replace('/\D/', '', $telefone);
            if (strlen($d) < 10 || strlen($d) > 11) {
                voltar('dados', 'Telefone inválido. Use DDD + número, ex.: (48) 99999-9999.');
            }
            $meio = strlen($d) === 11 ? substr($d, 2, 5) : substr($d, 2, 4);
            $fim  = strlen($d) === 11 ? substr($d, 7) : substr($d, 6);
            $tel_final = '(' . substr($d, 0, 2) . ') ' . $meio . '-' . $fim;
        }

        if (strcasecmp($email, $atual['email']) !== 0) {
            if (!password_verify($senha_atual, $atual['senha_hash'])) {
                voltar('dados', 'Para trocar o e-mail, informe sua senha atual corretamente.');
            }
            $stmt = $pdo->prepare("SELECT id FROM usuario WHERE email = ? AND id <> ? LIMIT 1");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                voltar('dados', 'Este e-mail já está sendo usado por outra conta.');
            }
        }

        $cpf_novo = null;
        $troca_aprovada = null;
        if ($atual['tipo_usuario'] === 'cliente') {
            $st = $pdo->prepare("SELECT cpf FROM cliente WHERE id_usuario = ?");
            $st->execute([$id]);
            $cpf_salvo = $st->fetchColumn() ?: null;
            $cpf_digitado = trim($_POST['cpf'] ?? '');
            if ($cpf_salvo !== null) {
                $troca_aprovada = ultima_solicitacao_cpf($pdo, $id);
                if (!$troca_aprovada || $troca_aprovada['status'] !== 'aprovada') {
                    $troca_aprovada = null;
                }
            }
            $pode_gravar = ($cpf_salvo === null) || $troca_aprovada;
            if ($pode_gravar && $cpf_digitado !== '') {
                $cpf_novo = cpf_normalizar($cpf_digitado);
                if ($cpf_novo === null || !cpf_validar_externo($cpf_novo)) {
                    voltar('dados', 'CPF inválido. Confira os números e tente de novo.');
                }
                if ($cpf_salvo !== null && $cpf_novo === cpf_decifrar($cpf_salvo)) {
                    $cpf_novo = null;
                    if ($troca_aprovada) {
                        voltar('dados', 'O CPF informado é igual ao atual. Digite o novo CPF para concluir a troca aprovada.');
                    }
                }
                if ($cpf_novo !== null && cpf_em_uso($pdo, $cpf_novo, $id)) {
                    voltar('dados', 'Este CPF já está cadastrado em outra conta.');
                }
            }
        }

        $pdo->prepare("UPDATE usuario SET nome = ?, email = ?, telefone = ? WHERE id = ?")
            ->execute([$nome, $email, $tel_final, $id]);
        if ($cpf_novo !== null) {
            if ($troca_aprovada) {
                $pdo->prepare("UPDATE cliente SET cpf = ?, cpf_hash = ? WHERE id_usuario = ?")
                    ->execute([cpf_cifrar($cpf_novo), cpf_indice($cpf_novo), $id]);
                $pdo->prepare("UPDATE solicitacao_cpf SET status = 'concluida', data_conclusao = NOW() WHERE id = ? AND status = 'aprovada'")
                    ->execute([(int)$troca_aprovada['id']]);
            } else {
                $pdo->prepare("UPDATE cliente SET cpf = ?, cpf_hash = ? WHERE id_usuario = ? AND cpf IS NULL")
                    ->execute([cpf_cifrar($cpf_novo), cpf_indice($cpf_novo), $id]);
            }
        }

        $_SESSION['usuario_nome'] = $nome;
        voltar('dados', 'Dados atualizados com sucesso!', true);
    }

    if ($acao === 'solicitar_cpf') {
        if (!hash_equals($_SESSION['csrf_cliente'] ?? '', (string)($_POST['csrf'] ?? ''))) {
            voltar('dados', 'Sessão expirada. Tente de novo.');
        }
        if ($atual['tipo_usuario'] !== 'cliente') {
            voltar('dados', 'Ação inválida.');
        }
        $st = $pdo->prepare("SELECT cpf FROM cliente WHERE id_usuario = ?");
        $st->execute([$id]);
        $cpf_salvo = $st->fetchColumn() ?: null;
        if ($cpf_salvo === null) {
            voltar('dados', 'Você ainda não tem CPF cadastrado: é só informá-lo no campo e salvar.');
        }
        $motivo = trim((string)($_POST['motivo'] ?? ''));
        if (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 255) {
            voltar('dados', 'Conte o motivo da alteração (de 5 a 255 caracteres).');
        }
        $ult = ultima_solicitacao_cpf($pdo, $id);
        if ($ult && in_array($ult['status'], ['pendente', 'aprovada'], true)) {
            voltar('dados', $ult['status'] === 'pendente' ? 'Você já tem um pedido em análise.' : 'Seu pedido já foi aprovado: digite o novo CPF e salve.');
        }
        $pdo->prepare("INSERT INTO solicitacao_cpf (fk_cliente, cpf_atual, motivo) VALUES (?, ?, ?)")->execute([$id, cpf_cifrar(cpf_decifrar($cpf_salvo)), $motivo]);
        voltar('dados', 'Pedido enviado! O administrador vai analisar e você verá a resposta aqui no perfil.', true);
    }

    if ($acao === 'senha') {
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova        = $_POST['nova_senha'] ?? '';
        $confirmar   = $_POST['confirmar_senha'] ?? '';

        if (!password_verify($senha_atual, $atual['senha_hash'])) {
            voltar('senha', 'A senha atual está incorreta.');
        }
        if (strlen($nova) < 6) {
            voltar('senha', 'A nova senha precisa ter pelo menos 6 caracteres.');
        }
        if ($nova !== $confirmar) {
            voltar('senha', 'A confirmação não é igual à nova senha.');
        }

        $pdo->prepare("UPDATE usuario SET senha_hash = ? WHERE id = ?")
            ->execute([password_hash($nova, PASSWORD_DEFAULT), $id]);

        session_regenerate_id(true);
        voltar('senha', 'Senha alterada com sucesso!', true);
    }

    voltar('dados', 'Ação inválida.');
} catch (RuntimeException $e) {
    error_log('Erro ao atualizar perfil: ' . $e->getMessage());
    $msg = ($e->getCode() === '23000' && stripos($e->getMessage(), 'cpf') !== false) ? 'Este CPF já está cadastrado em outra conta.' : 'Não foi possível salvar. Tente novamente.';
    voltar($acao === 'senha' ? 'senha' : 'dados', $msg);
}
