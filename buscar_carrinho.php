<?php
require_once 'config/config.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['login_required' => true]);
    exit;
}

// Buscar itens do carrinho
$sql = "SELECT c.*, p.nome, p.imagem, p.preco 
        FROM carrinho c 
        JOIN produtos p ON c.produto_id = p.id 
        WHERE c.usuario_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result();

$itens = [];
$total = 0;

while ($item = $result->fetch_assoc()) {
    $subtotal = $item['quantidade'] * $item['preco'];
    $total += $subtotal;
    
    $itens[] = [
        'id' => $item['id'],
        'nome' => $item['nome'],
        'quantidade' => $item['quantidade'],
        'preco' => formatPrice($item['preco']),
        'subtotal' => formatPrice($subtotal),
        'imagem' => $item['imagem'],
        'observacoes' => $item['observacoes']
    ];
}

echo json_encode([
    'itens' => $itens,
    'total' => formatPrice($total)
]);