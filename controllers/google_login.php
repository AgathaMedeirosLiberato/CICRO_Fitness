<?php
session_start();
require_once __DIR__ . '/../config/config_google.php';

if (!google_configurado()) {
    header('Location: ../views/user/login.php');
    exit;
}
$_SESSION['google_state'] = bin2hex(random_bytes(16));
$_SESSION['google_nonce'] = bin2hex(random_bytes(16));

$url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => google_redirect_uri(),
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $_SESSION['google_state'],
    'nonce'         => $_SESSION['google_nonce'],
    'prompt'        => 'select_account',
]);
header('Location: ' . $url);
exit;
