<?php
const GOOGLE_CLIENT_ID     = '';
const GOOGLE_CLIENT_SECRET = '';

const GOOGLE_REDIRECT_URI = '';

function google_configurado(): bool
{
    return GOOGLE_CLIENT_ID !== '' && GOOGLE_CLIENT_SECRET !== '';
}

function google_redirect_uri(): string
{
    if (GOOGLE_REDIRECT_URI !== '') {
        return GOOGLE_REDIRECT_URI;
    }
    require_once __DIR__ . '/config_email.php';
    require_once __DIR__ . '/../models/email_funcoes.php';
    return url_base_site() . '/php/google_callback.php';
}
