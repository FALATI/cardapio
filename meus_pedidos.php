<?php
require_once 'config/config.php';

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Modificar a query principal de busca dos pedidos
$sql = "SELECT p.*, 
        fp.nome as forma_pagamento, 
        fe.nome as forma_entrega,
        CASE 
            WHEN fe.nome = 'Delivery' THEN ee.valor_entrega 
            ELSE 0 
        END as valor_entrega,
        u.bairro,
        CASE 
            WHEN fe.nome = 'Delivery' THEN (p.total + COALESCE(ee.valor_entrega, 0))
            ELSE p.total
        END as total_com_taxa
        FROM pedidos p
        LEFT JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id
        LEFT JOIN formas_entrega fe ON p.forma_entrega_id = fe.id
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN enderecos_entrega ee ON ee.bairro = u.bairro
        WHERE p.usuario_id = ?
        ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bs-primary: #3491D0;
            --bs-primary-rgb: 52, 145, 208;
            --bs-primary-hover: #2C475D;
        }

        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .top-bar {
            background: linear-gradient(135deg, #2C475D 0%, #3491D0 100%);
            padding: 1rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .top-bar h4 {
            color: white;
            margin: 0;
            font-weight: 500;
        }

        .pedido-card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: transform 0.2s ease;
        }

        .pedido-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .pedido-header {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            background: #f8f9fa;
        }

        .pedido-body {
            padding: 1.25rem;
        }

        .item-row {
            padding: 1rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .status-badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .btn-primary {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        .btn-primary:hover,
        .btn-primary:active,
        .btn-primary:focus {
            background-color: var(--bs-primary-hover) !important;
            border-color: var(--bs-primary-hover) !important;
        }

        .btn-outline-primary {
            color: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:active,
        .btn-outline-primary:focus {
            background-color: var(--bs-primary) !important;
            border-color: var(--bs-primary) !important;
            color: white !important;
        }

        .text-primary {
            color: var(--bs-primary) !important;
        }

        .btn-outline-light:hover {
            color: var(--bs-primary-hover);
        }

        h6 {
            color: var(--bs-primary-hover);
            font-weight: 600;
        }

        @media (max-width: 576px) {
            .top-bar {
                padding: 1rem 0;
            }
            
            .top-bar h4 {
                font-size: 1.2rem;
            }
            
            .top-bar .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }
            
            .top-bar .bi {
                font-size: 0.875rem;
            }

            .top-buttons {
                display: flex;
                gap: 0.5rem;
            }

            .top-buttons .btn {
                flex: 1;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="container">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <h4 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>Meus Pedidos
                </h4>
                <div class="top-buttons">
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="bi bi-house me-2"></i>Voltar ao Menu
                    </a>
                    <a href="logout.php" class="btn btn-light">
                        <i class="bi bi-box-arrow-right me-2"></i>Sair
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-4">
        <?php if (empty($pedidos)): ?>
            <div class="text-center">
                <i class="bi bi-bag-x text-muted" style="font-size: 3rem;"></i>
                <h4 class="mt-3">Nenhum pedido encontrado</h4>
                <p class="text-muted">Você ainda não fez nenhum pedido.</p>
                <a href="index.php" class="btn btn-primary mt-3">
                    <i class="bi bi-house me-2"></i>Fazer Primeiro Pedido
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($pedidos as $pedido): ?>
                <div class="pedido-card">
                    <div class="pedido-header">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <strong>Pedido #<?php echo $pedido['id']; ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <?php
                                $status_badges = [
                                    'pendente' => 'secondary',
                                    'confirmado' => 'primary',
                                    'preparando' => 'info',
                                    'saiu_entrega' => 'warning',
                                    'entregue' => 'success',
                                    'cancelado' => 'danger'
                                ];
                                $badge_color = $status_badges[$pedido['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badge_color; ?> status-badge">
                                    <?php echo ucfirst($pedido['status']); ?>
                                </span>
                            </div>
                            <div class="col-md-3">
                                <strong>Subtotal:</strong> <?php echo formatPrice($pedido['total']); ?>
                                <?php if ($pedido['forma_entrega'] === 'Delivery' && $pedido['valor_entrega'] > 0): ?>
                                    <br>
                                    <small>
                                        Taxa de Entrega: <?php echo formatPrice($pedido['valor_entrega']); ?>
                                    </small>
                                    <br>
                                    <strong>Total:</strong> <?php echo formatPrice($pedido['total_com_taxa']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 text-end">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="verDetalhesPedido(<?php echo $pedido['id']; ?>)">
                                    <i class="bi bi-eye me-1"></i>
                                    Ver Detalhes
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="pedido-body" id="detalhesPedido<?php echo $pedido['id']; ?>" style="display:none;">
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Pagamento:</strong> <?php echo $pedido['forma_pagamento']; ?></p>
                                    <p class="mb-1"><strong>Entrega:</strong> <?php echo $pedido['forma_entrega']; ?></p>
                                    <?php if ($pedido['forma_entrega'] === 'Delivery' && $pedido['valor_entrega'] > 0): ?>
                                        <p class="mb-1">
                                            <strong>Taxa de Entrega (<?php echo $pedido['bairro']; ?>):</strong> 
                                            <?php echo formatPrice($pedido['valor_entrega']); ?>
                                        </p>
                                        <p class="mb-1">
                                            <strong>Total com Taxa:</strong> 
                                            <?php echo formatPrice($pedido['total_com_taxa']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Endereço:</strong> <?php echo $pedido['endereco']; ?></p>
                                    <p class="mb-1"><strong>Telefone:</strong> <?php echo $pedido['telefone']; ?></p>
                                    <?php if ($pedido['observacoes']): ?>
                                        <p class="mb-1"><strong>Observações:</strong> <?php echo $pedido['observacoes']; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <h6 class="mb-3">Itens do Pedido</h6>
                        <?php
                        $sql_itens = "SELECT pi.*, p.nome 
                                     FROM pedido_itens pi 
                                     JOIN produtos p ON pi.produto_id = p.id 
                                     WHERE pi.pedido_id = ?";
                        $stmt_itens = $conn->prepare($sql_itens);
                        $stmt_itens->bind_param("i", $pedido['id']);
                        $stmt_itens->execute();
                        $itens = $stmt_itens->get_result()->fetch_all(MYSQLI_ASSOC);
                        ?>
                        
                        <?php foreach ($itens as $item): ?>
                            <div class="item-row">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?php echo $item['nome']; ?></h6>
                                        <small class="text-muted">
                                            <?php echo $item['quantidade']; ?>x 
                                            <?php echo formatPrice($item['preco_unitario']); ?>
                                        </small>
                                    </div>
                                    <strong>
                                        <?php echo formatPrice($item['quantidade'] * $item['preco_unitario']); ?>
                                    </strong>
                                </div>
                                <?php if ($item['observacoes']): ?>
                                    <small class="text-muted d-block">
                                        Obs: <?php echo $item['observacoes']; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
    function verDetalhesPedido(pedidoId) {
        const detalhes = document.getElementById('detalhesPedido' + pedidoId);
        if (detalhes.style.display === 'none') {
            detalhes.style.display = 'block';
        } else {
            detalhes.style.display = 'none';
        }
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>