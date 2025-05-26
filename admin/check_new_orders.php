<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

// Buscar total de pedidos pendentes
$sql = "SELECT COUNT(*) as total FROM pedidos WHERE status = 'pendente'";
$result = $conn->query($sql);
$current_total = $result->fetch_assoc()['total'];

// Pegar o total anterior da sessão
$previous_total = $_SESSION['total_pedidos_pendentes'] ?? 0;

// Atualizar o total na sessão
$_SESSION['total_pedidos_pendentes'] = $current_total;

// Responder com JSON
echo json_encode([
    'new_order' => $current_total > $previous_total,
    'current_total' => $current_total,
    'previous_total' => $previous_total
]);