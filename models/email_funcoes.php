<?php
require_once __DIR__ . '/../config/config_email.php';

function url_base_site(): string
{
    if (SITE_URL !== '') {
        return rtrim(SITE_URL, '/');
    }
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $pasta  = preg_replace('#/(controllers|views/(user|admin))(/.*)?$#', '', dirname($_SERVER['SCRIPT_NAME'] ?? '/') . '/x');
    $pasta  = preg_replace('#/x$#', '', $pasta);
    return $https . '://' . $host . $pasta;
}

function smtp_resposta($f): array
{
    $texto = '';
    while (($l = fgets($f, 515)) !== false) {
        $texto .= $l;
        if (strlen($l) < 4 || $l[3] === ' ') { break; }
    }
    return [(int)substr($texto, 0, 3), $texto];
}

function smtp_cmd($f, string $cmd, array $esperados): string
{
    if ($cmd !== '') { fwrite($f, $cmd . "\r\n"); }
    [$cod, $txt] = smtp_resposta($f);
    if (!in_array($cod, $esperados, true)) {
        throw new RuntimeException('SMTP: ' . trim($txt));
    }
    return $txt;
}

function smtp_enviar(string $para, string $assunto, string $html): bool
{
    $f = null;
    try {
        if (SMTP_USUARIO === '' || SMTP_SENHA === '') {
            throw new RuntimeException('SMTP_USUARIO / SMTP_SENHA não preenchidos em config/config_email.php');
        }
        $alvo = (SMTP_SEGURANCA === 'ssl' ? 'ssl://' : 'tcp://') . SMTP_HOST . ':' . SMTP_PORTA;
        $ctx = stream_context_create(['ssl' => [
            'verify_peer'      => SMTP_VERIFICAR_CERTIFICADO,
            'verify_peer_name' => SMTP_VERIFICAR_CERTIFICADO,
            'peer_name'        => SMTP_HOST,
        ]]);
        $f = @stream_socket_client($alvo, $en, $es, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$f) { throw new RuntimeException("Não conectou em $alvo ($es). Firewall/antivírus ou porta errada?"); }
        stream_set_timeout($f, 15);
        smtp_cmd($f, '', [220]);
        $host = preg_replace('/[^A-Za-z0-9.\-]/', '', $_SERVER['SERVER_NAME'] ?? 'localhost') ?: 'localhost';
        smtp_cmd($f, "EHLO $host", [250]);
        if (SMTP_SEGURANCA === 'tls') {
            smtp_cmd($f, 'STARTTLS', [220]);
            if (!@stream_socket_enable_crypto($f, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Falha ao ativar a criptografia TLS (extensão openssl ligada no php.ini?).');
            }
            smtp_cmd($f, "EHLO $host", [250]);
        }
        smtp_cmd($f, 'AUTH LOGIN', [334]);
        smtp_cmd($f, base64_encode(SMTP_USUARIO), [334]);
        smtp_cmd($f, base64_encode(str_replace(' ', '', SMTP_SENHA)), [235]);
        smtp_cmd($f, 'MAIL FROM:<' . EMAIL_REMETENTE_EMAIL . '>', [250]);
        smtp_cmd($f, "RCPT TO:<$para>", [250, 251]);
        smtp_cmd($f, 'DATA', [354]);
        $msg  = 'From: =?UTF-8?B?' . base64_encode(EMAIL_REMETENTE_NOME) . '?= <' . EMAIL_REMETENTE_EMAIL . ">\r\n"
              . "To: <$para>\r\n"
              . 'Subject: =?UTF-8?B?' . base64_encode($assunto) . "?=\r\n"
              . 'Date: ' . date('r') . "\r\n"
              . "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
              . chunk_split(base64_encode($html));
        fwrite($f, $msg . "\r\n.\r\n");
        [$cod, $txt] = smtp_resposta($f);
        if ($cod !== 250) { throw new RuntimeException('SMTP: ' . trim($txt)); }
        @fwrite($f, "QUIT\r\n");
        fclose($f);
        return true;
    } catch (Throwable $e) {
        if (is_resource($f)) { @fclose($f); }
        $pasta = __DIR__ . '/../logs';
        if (!is_dir($pasta)) { @mkdir($pasta, 0775, true); }
        @file_put_contents($pasta . '/emails_erro.log', '[' . date('Y-m-d H:i:s') . "] PARA: $para | " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        error_log('E-mail não enviado: ' . $e->getMessage());
        return false;
    }
}

function enviar_email(string $para, string $assunto, string $html): bool
{
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (EMAIL_MODO === 'smtp') {
        return smtp_enviar($para, $assunto, $html);
    }
    if (EMAIL_MODO === 'mail') {
        $cab  = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $cab .= 'From: =?UTF-8?B?' . base64_encode(EMAIL_REMETENTE_NOME) . '?= <' . EMAIL_REMETENTE_EMAIL . ">\r\n";
        return @mail($para, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $html, $cab);
    }
    $pasta = __DIR__ . '/../logs';
    if (!is_dir($pasta)) { @mkdir($pasta, 0775, true); }
    $linha = '[' . date('Y-m-d H:i:s') . "] PARA: $para | ASSUNTO: $assunto\n" . strip_tags(str_replace(['</p>', '<br>'], "\n", $html)) . "\n----\n";
    return @file_put_contents($pasta . '/emails.log', $linha, FILE_APPEND | LOCK_EX) !== false;
}

function enviar_verificacao_email(PDO $pdo, int $id_usuario, string $nome, string $email): array
{
    $codigo = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE usuario SET token_verificacao = ?, token_expira = ?, verificacao_enviada_em = ? WHERE id = ?")
        ->execute([hash('sha256', $codigo), date('Y-m-d H:i:s', time() + VERIFICACAO_VALIDADE_HORAS * 3600), date('Y-m-d H:i:s'), $id_usuario]);

    $link = url_base_site() . '/views/user/verificar_email.php?token=' . $codigo;
    $n = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
    $html = "<p>Olá, $n!</p><p>Para ativar a sua conta na CICRO, confirme o seu e-mail clicando no link abaixo:</p>"
          . "<p><a href=\"$link\">$link</a></p>"
          . '<p>O link vale por ' . VERIFICACAO_VALIDADE_HORAS . ' horas. Se você não criou uma conta, ignore esta mensagem.</p>';
    return ['enviado' => enviar_email($email, 'CICRO - confirme o seu e-mail', $html), 'link' => $link];
}
