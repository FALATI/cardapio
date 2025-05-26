<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['usuario_id'])) {
        throw new Exception("ID do usuário não informado");
    }

    if (empty($_POST['nome'])) {
        throw new Exception("O nome é obrigatório");
    }

    if (empty($_POST['email'])) {
        throw new Exception("O email é obrigatório");
    }

    $usuario_id = (int)$_POST['usuario_id'];
    $nome = cleanInput($_POST['nome']);
    $email = cleanInput($_POST['email']);
    $telefone = cleanInput($_POST['telefone']);
    $endereco = cleanInput($_POST['endereco']);
    $bairro = cleanInput($_POST['bairro']);
    $status = isset($_POST['status']) ? 1 : 0;

    // Verifica se email já existe para outro usuário
    $sql = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $email, $usuario_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("Este email já está sendo usado por outro usuário");
    }

    if (!empty($_POST['senha'])) {
        // Atualiza com nova senha
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET 
                nome = ?, 
                email = ?, 
                senha = ?,
                telefone = ?,
                endereco = ?,
                bairro = ?,
                status = ? 
                WHERE id = ? AND tipo = 'cliente'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssii", $nome, $email, $senha, $telefone, $endereco, $bairro, $status, $usuario_id);
    } else {
        // Atualiza sem mudar a senha
        $sql = "UPDATE usuarios SET 
                nome = ?, 
                email = ?, 
                telefone = ?,
                endereco = ?,
                bairro = ?,
                status = ? 
                WHERE id = ? AND tipo = 'cliente'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssii", $nome, $email, $telefone, $endereco, $bairro, $status, $usuario_id);
    }

    if (!$stmt->execute()) {
        throw new Exception("Erro ao atualizar usuário: " . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Usuário atualizado com sucesso!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}