<?php
require_once 'config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Usuário não logado']);
    exit;
}

try {
    // Verifica pedidos pendentes
    $sql = "SELECT id, status FROM pedidos 
            WHERE usuario_id = ? 
            AND status NOT IN ('entregue', 'cancelado')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_pendentes = $result->num_rows;
    $pedidos = $result->fetch_all(MYSQLI_ASSOC);
    
    // Verifica se algum status mudou desde a última verificação
    $atualizar = false;
    if (isset($_SESSION['ultimo_status'])) {
        foreach ($pedidos as $pedido) {
            if (!isset($_SESSION['ultimo_status'][$pedido['id']]) || 
                $_SESSION['ultimo_status'][$pedido['id']] !== $pedido['status']) {
                $atualizar = true;
                break;
            }
        }
    }
    
    // Atualiza o último status conhecido
    $_SESSION['ultimo_status'] = [];
    foreach ($pedidos as $pedido) {
        $_SESSION['ultimo_status'][$pedido['id']] = $pedido['status'];
    }
    
    echo json_encode([
        'success' => true,
        'total_pendentes' => $total_pendentes,
        'atualizar' => $atualizar
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}