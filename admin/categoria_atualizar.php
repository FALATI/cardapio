<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['categoria_id'])) {
        throw new Exception("ID da categoria não informado");
    }

    if (empty($_POST['nome'])) {
        throw new Exception("O nome da categoria é obrigatório");
    }

    $categoria_id = (int)$_POST['categoria_id'];
    $nome = cleanInput($_POST['nome']);
    $descricao = cleanInput($_POST['descricao']);

    $sql = "UPDATE categorias SET nome = ?, descricao = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $nome, $descricao, $categoria_id);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao atualizar categoria: " . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Categoria atualizada com sucesso!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}