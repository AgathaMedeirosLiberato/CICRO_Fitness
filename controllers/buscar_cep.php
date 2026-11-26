<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$cep = $_GET['cep'] ?? '';
$cep = preg_replace('/\D/', '', $cep);

if (strlen($cep) === 8) {
    $url = "https://viacep.com.br/ws/{$cep}/json/";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $resposta = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $resposta) {
        echo $resposta;
    } else {
        echo json_encode([
            'erro' => true, 
            'mensagem' => 'O servidor local nao conseguiu conectar a API externa.'
        ]);
    }
} else {
    echo json_encode([
        'erro' => true, 
        'mensagem' => 'Formato de CEP invalido.'
    ]);
}
