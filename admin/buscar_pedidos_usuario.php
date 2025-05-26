<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID do usuário não fornecido']);
    exit;
}

$usuario_id = (int)$_GET['id'];

$sql = "SELECT p.*, u.nome as cliente_nome 
        FROM pedidos p 
        LEFT JOIN usuarios u ON p.usuario_id = u.id 
        WHERE p.usuario_id = ? 
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$pedidos = [];
while ($pedido = $result->fetch_assoc()) {
    $pedido['data_formatada'] = date('d/m/Y H:i', strtotime($pedido['created_at']));
    $pedido['total_formatado'] = number_format($pedido['total'], 2, ',', '.');
    $pedidos[] = $pedido;
}

echo json_encode(['success' => true, 'pedidos' => $pedidos]);