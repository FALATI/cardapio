<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

// Buscar estatísticas
$sql_total_produtos = "SELECT COUNT(*) as total FROM produtos WHERE status = 1";
$sql_total_categorias = "SELECT COUNT(*) as total FROM categorias WHERE status = 1";
$sql_total_pedidos = "SELECT COUNT(*) as total 
                      FROM pedidos 
                      WHERE status NOT IN ('cancelado', 'entregue')";
$sql_total_usuarios = "SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'cliente' AND status = 1";

$result_produtos = $conn->query($sql_total_produtos)->fetch_assoc();
$result_categorias = $conn->query($sql_total_categorias)->fetch_assoc();
$result_pedidos = $conn->query($sql_total_pedidos)->fetch_assoc();
$result_usuarios = $conn->query($sql_total_usuarios)->fetch_assoc();

// Adicionar query para ganhos do dia
$sql_ganhos_dia = "SELECT 
    COUNT(*) as total_pedidos,
    COALESCE(SUM(total), 0) as total_valor
    FROM pedidos 
    WHERE DATE(created_at) = CURDATE() 
    AND status NOT IN ('cancelado')";

$result_ganhos = $conn->query($sql_ganhos_dia)->fetch_assoc();

// Atualizar a query de pedidos:

// Buscar últimos pedidos ativos
$sql = "SELECT p.*, u.nome as cliente_nome 
        FROM pedidos p 
        LEFT JOIN usuarios u ON p.usuario_id = u.id 
        WHERE p.status NOT IN ('cancelado', 'entregue')
        ORDER BY p.created_at DESC 
        LIMIT 5";
$result = $conn->query($sql);

$pedidos = [];
while ($pedido = $result->fetch_assoc()) {
    $pedidos[] = [
        'id' => $pedido['id'],
        'cliente_nome' => $pedido['cliente_nome'],
        'total' => number_format($pedido['total'], 2, ',', '.'),
        'status' => $pedido['status'],
        'status_color' => getStatusColor($pedido['status']),
        'data' => date('d/m/Y H:i', strtotime($pedido['created_at']))
    ];
}

// Buscar último pedido pendente
$sql_ultimo_pedido = "SELECT id, created_at 
                      FROM pedidos 
                      WHERE status = 'pendente' 
                      AND created_at >= NOW() - INTERVAL 30 SECOND
                      ORDER BY id DESC 
                      LIMIT 1";
$ultimo_pedido = $conn->query($sql_ultimo_pedido)->fetch_assoc();

// Adicionar flag para novo pedido
$tem_novo_pedido = false;
if ($ultimo_pedido && $ultimo_pedido['id'] > ($_SESSION['last_order_id'] ?? 0)) {
    $tem_novo_pedido = true;
    $_SESSION['last_order_id'] = $ultimo_pedido['id'];
}

$stats = [
    'produtos' => $result_produtos['total'],
    'ganhos_dia' => $result_ganhos['total_valor'],
    'pedidos_dia' => $result_ganhos['total_pedidos'],
    'pedidos' => $result_pedidos['total'],
    'usuarios' => $result_usuarios['total']
];

echo json_encode([
    'stats' => $stats,
    'pedidos' => $pedidos,
    'ultimo_pedido' => $ultimo_pedido,
    'tem_novo_pedido' => $tem_novo_pedido
]);