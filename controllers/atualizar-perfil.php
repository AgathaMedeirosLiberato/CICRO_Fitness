<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/conexao.php';

$id_usuario = (int)($_SESSION['usuario_id'] ?? 0);
if ($id_usuario === 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

$nome = $_POST['adminName'] ?? '';
$email = $_POST['adminEmail'] ?? '';

if (empty($nome) || empty($email)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nome e e-mail são obrigatórios']);
    exit;
}

$caminhoFotoBD = null;

if (isset($_FILES['avatarInput']) && $_FILES['avatarInput']['error'] === UPLOAD_ERR_OK) {
    $diretorioUpload = '../uploads/';
    
    if (!is_dir($diretorioUpload)) {
        mkdir($diretorioUpload, 0777, true);
    }

    $extensao = pathinfo($_FILES['avatarInput']['name'], PATHINFO_EXTENSION);
    $nomeArquivo = 'avatar_' . $id_usuario . '_' . time() . '.' . $extensao;
    $caminhoDestino = $diretorioUpload . $nomeArquivo;

if (move_uploaded_file($_FILES['avatarInput']['tmp_name'], $caminhoDestino)) {
        $caminhoFotoBD = 'uploads/' . $nomeArquivo;
    }
}

if ($caminhoFotoBD) {
    $sql = "UPDATE usuario SET nome = ?, email = ?, foto_perfil = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $nome, $email, $caminhoFotoBD, $id_usuario);
} else {
    $sql = "UPDATE usuario SET nome = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $nome, $email, $id_usuario);
}

if ($stmt->execute()) {
    echo json_encode([
        'sucesso' => true, 
        'mensagem' => 'Perfil atualizado com sucesso!',
        'nome' => $nome,
        'foto' => $caminhoFotoBD
    ]);
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar registro']);
}

$stmt->close();
$conn->close();
?>