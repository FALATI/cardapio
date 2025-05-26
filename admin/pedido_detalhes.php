<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

if (!isset($_GET['id'])) {
    header('Location: pedidos.php');
    exit;
}

$pedido_id = (int)$_GET['id'];

// Buscar informações do pedido
$sql = "SELECT p.*, u.nome as cliente_nome, u.email as cliente_email 
        FROM pedidos p 
        LEFT JOIN usuarios u ON p.usuario_id = u.id 
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    header('Location: pedidos.php');
    exit;
}

// Buscar itens do pedido
$sql = "SELECT pi.*, p.nome as produto_nome 
        FROM pedidos_itens pi 
        LEFT JOIN produtos p ON pi.produto_id = p.id 
        WHERE pi.pedido_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$itens = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido #<?php echo $pedido_id; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: #343a40;
            padding-top: 1rem;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Pedido #<?php echo $pedido_id; ?></h2>
            <a href="pedidos.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Informações do Cliente</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nome:</strong> <?php echo $pedido['cliente_nome']; ?></p>
                        <p><strong>Email:</strong> <?php echo $pedido['cliente_email']; ?></p>
                        <p><strong>Data do Pedido:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Informações do Pedido</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Status:</strong> <?php echo ucfirst($pedido['status']); ?></p>
                        <p><strong>Forma de Pagamento:</strong> <?php echo $pedido['forma_pagamento']; ?></p>
                        <p><strong>Total:</strong> <?php echo formatPrice($pedido['total']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Itens do Pedido</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Preço Unitário</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = $itens->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $item['produto_nome']; ?></td>
                                    <td><?php echo $item['quantidade']; ?></td>
                                    <td><?php echo formatPrice($item['preco_unitario']); ?></td>
                                    <td><?php echo formatPrice($item['preco_unitario'] * $item['quantidade']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td><strong><?php echo formatPrice($pedido['total']); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <?php if($pedido['observacoes']): ?>
                    <div class="mt-3">
                        <h6>Observações:</h6>
                        <p class="mb-0"><?php echo nl2br($pedido['observacoes']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>