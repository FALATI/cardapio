<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

header('Content-Type: application/json');

try {
    if (empty($_POST['nome'])) {
        throw new Exception("O nome da categoria é obrigatório");
    }

    $nome = cleanInput($_POST['nome']);
    $descricao = cleanInput($_POST['descricao']);

    $sql = "INSERT INTO categorias (nome, descricao, status) VALUES (?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nome, $descricao);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar categoria: " . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Categoria salva com sucesso!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}