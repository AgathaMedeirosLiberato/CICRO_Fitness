<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../models/usuarios.php';
require_once __DIR__ . '/../config/config_google.php';

function erro_google(string $msg): void
{
    echo "<script>alert(" . json_encode($msg) . "); window.location.href = '../views/user/login.php';</script>";
    exit;
}

function google_post(string $url, array $campos): ?array
{
    $corpo = http_build_query($campos);
    if (function_exists('curl_init')) {
        $c = curl_init($url);
        curl_setopt_array($c, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $corpo, CURLOPT_RETURNTRANSFER => true,
                               CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']]);
        $resp = curl_exec($c);
        if ($resp === false) { error_log('Google token: ' . curl_error($c)); }
        curl_close($c);
    } else {
        $ctx = stream_context_create(['http' => ['method' => 'POST', 'timeout' => 15, 'ignore_errors' => true,
                                                 'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => $corpo]]);
        $resp = @file_get_contents($url, false, $ctx);
    }
    $j = is_string($resp) ? json_decode($resp, true) : null;
    return is_array($j) ? $j : null;
}

if (!google_configurado()) { header('Location: ../views/user/login.php'); exit; }
if (isset($_GET['error'])) { erro_google('Login com o Google cancelado.'); }

$state = (string)($_GET['state'] ?? ''); $code = (string)($_GET['code'] ?? '');
$esperado = (string)($_SESSION['google_state'] ?? ''); $nonce = (string)($_SESSION['google_nonce'] ?? '');
unset($_SESSION['google_state'], $_SESSION['google_nonce']);
if ($code === '' || $esperado === '' || !hash_equals($esperado, $state)) { erro_google('Sessão do login com o Google expirou. Tente de novo.'); }

$tok = google_post('https://oauth2.googleapis.com/token', [
    'code' => $code, 'client_id' => GOOGLE_CLIENT_ID, 'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => google_redirect_uri(), 'grant_type' => 'authorization_code',
]);
if (!$tok || empty($tok['id_token'])) {
    error_log('Google token inválido: ' . json_encode($tok));
    erro_google('Não foi possível entrar com o Google. Confira as credenciais em config/config_google.php.');
}

$partes = explode('.', $tok['id_token']);
$dados = count($partes) === 3 ? json_decode(base64_decode(strtr($partes[1], '-_', '+/')), true) : null;
if (!is_array($dados)
    || !in_array($dados['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true)
    || ($dados['aud'] ?? '') !== GOOGLE_CLIENT_ID
    || (int)($dados['exp'] ?? 0) < time()
    || !hash_equals($nonce, (string)($dados['nonce'] ?? ''))) {
    erro_google('Resposta do Google inválida. Tente de novo.');
}
$gid = (string)($dados['sub'] ?? ''); $email = strtolower(trim((string)($dados['email'] ?? '')));
$nome = trim((string)($dados['name'] ?? '')) ?: strstr($email, '@', true);
if ($gid === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($dados['email_verified'])) {
    erro_google('Sua conta Google precisa ter um e-mail verificado.');
}

try {
    $st = $pdo->prepare("SELECT id, nome, tipo_usuario, ativo FROM usuario WHERE google_id = ? LIMIT 1");
    $st->execute([$gid]);
    $u = $st->fetch();

    if (!$u) {
        $st = $pdo->prepare("SELECT id, nome, tipo_usuario, ativo, google_id FROM usuario WHERE email = ? LIMIT 1");
        $st->execute([$email]);
        $u = $st->fetch();
        if ($u) {
            if ($u['tipo_usuario'] !== 'cliente') { erro_google('Contas da equipe entram com e-mail e senha.'); }
            if ($u['google_id'] && $u['google_id'] !== $gid) { erro_google('Este e-mail já está ligado a outra conta Google.'); }
            $pdo->prepare("UPDATE usuario SET google_id = ?, email_verificado = 1 WHERE id = ?")->execute([$gid, (int)$u['id']]);
        }
    }

    if (!$u) {
        $id = criar_usuario($pdo, mb_substr($nome, 0, 150), $email, bin2hex(random_bytes(16)), 'cliente', null, true);
        $pdo->prepare("UPDATE usuario SET google_id = ?, email_verificado = 1 WHERE id = ?")->execute([$gid, $id]);
        $u = ['id' => $id, 'nome' => mb_substr($nome, 0, 150), 'tipo_usuario' => 'cliente', 'ativo' => 1];
    }
    if (!(int)$u['ativo']) { erro_google('Esta conta está desativada.'); }

    garantir_perfil_filho($pdo, (int)$u['id'], $u['tipo_usuario']);
    session_regenerate_id(true);
    $_SESSION['usuario_id']   = (int)$u['id'];
    $_SESSION['usuario_nome'] = $u['nome'];
    $_SESSION['usuario_tipo'] = $u['tipo_usuario'];
    $_SESSION['tipo_usuario'] = $u['tipo_usuario'];
    header('Location: ../views/user/index.php');
    exit;
} catch (PDOException $e) {
    error_log('Erro no login Google: ' . $e->getMessage());
    erro_google('Erro ao entrar com o Google. Tente novamente.');
}
