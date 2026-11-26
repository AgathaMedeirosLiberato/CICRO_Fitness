<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode([
    'logado' => isset($_SESSION['usuario_id']),
    'nome'   => $_SESSION['usuario_nome'] ?? null,
    'tipo'   => $_SESSION['usuario_tipo'] ?? null,
]);
