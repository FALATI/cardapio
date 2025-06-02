<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verificar a ação solicitada
$action = $_POST['action'] ?? '';

// Ação para excluir endereço
if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }
    
    // Excluir o endereço
    $sql = "DELETE FROM enderecos_entrega WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir endereço: ' . $conn->error]);
    }
    exit;
}

// Ação desconhecida
else {
    echo json_encode(['success' => false, 'message' => 'Ação desconhecida']);
    exit;
}
?>