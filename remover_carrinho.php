<?php
require_once 'config/config.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Usuário não logado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $item_id = (int)$_POST['id'];
    $usuario_id = $_SESSION['usuario_id'];

    // Remover item do carrinho
    $sql = "DELETE FROM carrinho WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $item_id, $usuario_id);
    
    if ($stmt->execute()) {
        // Buscar novo total de itens
        $sql = "SELECT SUM(quantidade) as total FROM carrinho WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $total_itens = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        echo json_encode([
            'success' => true,
            'total_itens' => $total_itens
        ]);
    } else {
        echo json_encode([
            'error' => 'Erro ao remover item'
        ]);
    }
}