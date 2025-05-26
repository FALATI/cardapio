<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

header('Content-Type: application/json');

try {
    if (empty($_POST['nome'])) {
        throw new Exception("O nome é obrigatório");
    }

    if (empty($_POST['email'])) {
        throw new Exception("O email é obrigatório");
    }

    if (empty($_POST['senha'])) {
        throw new Exception("A senha é obrigatória");
    }

    if (empty($_POST['telefone'])) {
        throw new Exception("O telefone é obrigatório");
    }

    if (empty($_POST['endereco'])) {
        throw new Exception("O endereço é obrigatório");
    }

    if (empty($_POST['bairro'])) {
        throw new Exception("O bairro é obrigatório");
    }

    $nome = cleanInput($_POST['nome']);
    $email = cleanInput($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $telefone = cleanInput($_POST['telefone']);
    $endereco = cleanInput($_POST['endereco']);
    $bairro = cleanInput($_POST['bairro']);
    $status = isset($_POST['status']) ? 1 : 0;

    // Verifica se email já existe
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("Este email já está cadastrado");
    }

    // No momento de salvar, adicionar o campo bairro
    $sql = "INSERT INTO usuarios (nome, email, senha, telefone, endereco, bairro, status, tipo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'cliente')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", 
        $nome, 
        $email, 
        $senha,
        $telefone,
        $endereco,
        $bairro,
        $status
    );

    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar usuário: " . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Usuário salvo com sucesso!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}