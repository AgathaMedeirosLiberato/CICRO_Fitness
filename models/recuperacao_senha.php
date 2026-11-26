<?php
require_once __DIR__ . '/email_funcoes.php';

function enviar_recuperacao_senha(PDO $pdo, int $id_usuario, string $nome, string $email): array
{
    $codigo = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE usuario SET token_recuperacao = ?, recuperacao_expira = ?, recuperacao_enviada_em = ? WHERE id = ?")
        ->execute([hash('sha256', $codigo), date('Y-m-d H:i:s', time() + RECUPERACAO_VALIDADE_MINUTOS * 60), date('Y-m-d H:i:s'), $id_usuario]);

    $link = url_base_site() . '/views/user/redefinir_senha.php?token=' . $codigo;
    $n = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
    $html = "<p>Olá, $n!</p><p>Recebemos um pedido para redefinir a senha da sua conta na CICRO. Para escolher uma nova senha, clique no link abaixo:</p>"
          . "<p><a href=\"$link\">$link</a></p>"
          . '<p>O link vale por ' . RECUPERACAO_VALIDADE_MINUTOS . ' minutos e só pode ser usado uma vez. Se você não pediu isso, ignore esta mensagem: a sua senha continua a mesma.</p>';
    return ['enviado' => enviar_email($email, 'CICRO - redefinir a sua senha', $html), 'link' => $link];
}

function usuario_do_codigo_recuperacao(PDO $pdo, string $codigo): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $codigo)) {
        return null;
    }
    $st = $pdo->prepare("SELECT id, nome, email, recuperacao_expira FROM usuario WHERE token_recuperacao = ? AND ativo = 1 LIMIT 1");
    $st->execute([hash('sha256', $codigo)]);
    $u = $st->fetch();
    if (!$u || !$u['recuperacao_expira'] || strtotime($u['recuperacao_expira']) < time()) {
        return null;
    }
    return $u;
}
