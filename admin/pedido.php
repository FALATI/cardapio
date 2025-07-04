<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

$mensagem = '';
$erro = '';

if (!isset($_GET['id'])) {
    header('Location: pedidos.php');
    exit;
}

$pedido_id = (int)$_GET['id'];

// Atualizar status do pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['novo_status'])) {
    $novo_status = $_POST['novo_status'];
    
    $sql = "UPDATE pedidos SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        $erro = "Erro na preparação da consulta: " . $conn->error;
    } else {
        $stmt->bind_param("si", $novo_status, $pedido_id);
        
        if ($stmt->execute()) {
            $mensagem = "Status atualizado com sucesso!";
            
            // Redirecionar se o pedido foi finalizado ou cancelado
            if ($novo_status == 'entregue') {
                header('Location: pedidos.php');
                exit;
            } else if ($novo_status == 'cancelado') {
                header('Location: historico_pedidos.php');
                exit;
            }
        } else {
            $erro = "Erro ao atualizar status: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Buscar dados do pedido com verificação de erro
$sql = "SELECT p.*, 
        u.nome as cliente_nome, 
        u.email, 
        u.bairro,
        fe.nome as forma_entrega,
        fp.nome as forma_pagamento,
        p.endereco, 
        p.telefone,
        CASE 
            WHEN fe.nome = 'Delivery' THEN ee.valor_entrega 
            ELSE 0 
        END as valor_entrega,
        CASE 
            WHEN fe.nome = 'Delivery' THEN (p.total + COALESCE(ee.valor_entrega, 0))
            ELSE p.total
        END as total_com_taxa
        FROM pedidos p 
        LEFT JOIN usuarios u ON p.usuario_id = u.id 
        LEFT JOIN formas_entrega fe ON p.forma_entrega_id = fe.id
        LEFT JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id
        LEFT JOIN enderecos_entrega ee ON ee.bairro = u.bairro
        WHERE p.id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Erro na preparação da consulta: " . $conn->error);
}

$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$result = $stmt->get_result();
$pedido = $result->fetch_assoc();
$stmt->close();

if (!$pedido) {
    header('Location: pedidos.php');
    exit;
}

// Buscar itens do pedido
$sql = "SELECT pi.*, p.nome as produto_nome, p.imagem 
        FROM pedido_itens pi 
        JOIN produtos p ON pi.produto_id = p.id 
        WHERE pi.pedido_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$itens = $stmt->get_result();

// Buscar configuração do site
$sql_config = "SELECT * FROM configuracoes WHERE chave = 'site_titulo'";
$result_config = $conn->query($sql_config);
$config = $result_config->fetch_assoc();
$site_titulo = $config['valor'] ?? SITE_NAME;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?php echo $pedido_id; ?> - <?php echo SITE_NAME; ?></title>
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

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2C475D 0%, #3491D0 100%);
            color: white;
            flex-shrink: 0;
            transition: all 0.3s ease;
            position: fixed;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
        }

        /* Conteúdo Principal */
        .main-content {
            flex-grow: 1;
            margin-left: 250px;
            overflow: auto;
            background-color: #f8f9fa;
            transition: margin-left 0.3s ease;
        }

        .content {
            padding: 1.5rem;
        }

        /* Header do Pedido */
        .order-header {
            background: linear-gradient(135deg, #2C475D 0%, #3491D0 100%);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1rem 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Tabelas */
        .table {
            margin-bottom: 0;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            border-top: none;
        }

        .table td {
            vertical-align: middle;
        }

        /* Status Badge */
        .status-badge {
            padding: 0.5rem 1rem;
            font-weight: 500;
            font-size: 0.875rem;
        }

        /* Product Image */
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Modal de Impressão */
        #areaImpressao {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        /* Botões */
        .btn-primary {
            background: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        .btn-primary:hover {
            background: var(--bs-primary-hover);
            border-color: var(--bs-primary-hover);
        }

        /* Form Controls */
        .form-select:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.2rem rgba(52, 145, 208, 0.25);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .content {
                padding: 1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .status-badge {
                padding: 0.4rem 0.8rem;
            }

            .product-image {
                width: 60px;
                height: 60px;
            }
        }

        /* Animações */
        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .alert {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="content">
                <?php if ($mensagem): ?>
                    <div class="alert alert-success"><?php echo $mensagem; ?></div>
                <?php endif; ?>
                
                <?php if ($erro): ?>
                    <div class="alert alert-danger"><?php echo $erro; ?></div>
                <?php endif; ?>

                <div class="order-header">
                    <div class="container">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h1 class="h3 mb-0">Pedido #<?php echo $pedido_id; ?></h1>
                                <p class="mb-0">
                                    <?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?>
                                </p>
                            </div>
                            <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#modalImpressao">
                                <i class="bi bi-printer-fill me-2"></i>Imprimir
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Itens do Pedido</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Produto</th>
                                                <th>Quantidade</th>
                                                <th>Preço</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($item = $itens->fetch_assoc()): 
                                                $subtotal = $item['quantidade'] * $item['preco_unitario'];
                                            ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($item['imagem']): ?>
                                                                <img src="../uploads/produtos/<?php echo $item['imagem']; ?>" 
                                                                     class="product-image me-3">
                                                            <?php endif; ?>
                                                            <div>
                                                                <strong><?php echo $item['produto_nome']; ?></strong>
                                                                <?php if ($item['observacoes']): ?>
                                                                    <br>
                                                                    <small class="text-muted">
                                                                        <?php echo $item['observacoes']; ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $item['quantidade']; ?></td>
                                                    <td><?php echo formatPrice($item['preco_unitario']); ?></td>
                                                    <td><?php echo formatPrice($subtotal); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end">Subtotal:</td>
                                                <td><?php echo formatPrice($pedido['total']); ?></td>
                                            </tr>
                                            <?php if ($pedido['forma_entrega'] === 'Delivery' && $pedido['valor_entrega'] > 0): ?>
                                            <tr>
                                                <td colspan="3" class="text-end">Taxa de Entrega (<?php echo $pedido['bairro']; ?>):</td>
                                                <td><?php echo formatPrice($pedido['valor_entrega']); ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <td colspan="3" class="text-end">
                                                    <strong>Total:</strong>
                                                </td>
                                                <td>
                                                    <strong><?php echo formatPrice($pedido['total_com_taxa']); ?></strong>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Dados do Pedido</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Status:</strong>
                                    <?php if (!in_array($pedido['status'], ['entregue', 'cancelado'])): ?>
                                        <form method="POST" class="mt-2">
                                            <select name="novo_status" class="form-select" onchange="this.form.submit()">
                                                <?php
                                                $status_list = [
                                                    'pendente' => 'Pendente',
                                                    'preparando' => 'Preparando',
                                                    'saiu_entrega' => 'Saiu para Entrega',
                                                    'entregue' => 'Entregue',
                                                    'cancelado' => 'Cancelado'
                                                ];

                                                foreach ($status_list as $value => $label) {
                                                    $selected = ($value === $pedido['status']) ? 'selected' : '';
                                                    echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
                                                }
                                                ?>
                                            </select>
                                        </form>
                                    <?php else: ?>
                                        <div class="mt-2">
                                            <span class="badge bg-<?php 
                                                echo match($pedido['status']) {
                                                    'entregue' => 'success',
                                                    'preparando' => 'warning',
                                                    'saiu_entrega' => 'info',
                                                    'cancelado' => 'danger',
                                                    default => 'primary'
                                                }; 
                                            ?> status-badge">
                                                <?php 
                                                $status_labels = [
                                                    'pendente' => 'Pendente',
                                                    'preparando' => 'Preparando',
                                                    'saiu_entrega' => 'Saiu para Entrega',
                                                    'entregue' => 'Entregue',
                                                    'cancelado' => 'Cancelado'
                                                ];
                                                echo $status_labels[$pedido['status']] ?? ucfirst($pedido['status']); 
                                                ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <h6 class="mb-2">Dados do Cliente</h6>
                                    <p class="mb-1"><strong>Nome:</strong> <?php echo $pedido['cliente_nome']; ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?php echo $pedido['email']; ?></p>
                                    <p class="mb-1"><strong>Telefone:</strong> <?php echo $pedido['telefone']; ?></p>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <h6 class="mb-2">Endereço de Entrega</h6>
                                    <p class="mb-1"><?php echo nl2br($pedido['endereco']); ?></p>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <h6 class="mb-2">Informações do Pagamento</h6>
                                    <p class="mb-1">
                                        <strong>Forma de Entrega:</strong> 
                                        <?php echo $pedido['forma_entrega']; ?>
                                        <?php if ($pedido['taxa_entrega'] > 0): ?>
                                            <br>
                                            <small class="text-muted">
                                                Taxa de entrega: <?php echo formatPrice($pedido['taxa_entrega']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </p>
                                    <p class="mb-1"><strong>Forma de Pagamento:</strong> <?php echo $pedido['forma_pagamento']; ?></p>
                                </div>
                            </div>
                        </div>

                        <?php if ($pedido['observacoes']): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Observações do Pedido</h5>
                                </div>
                                <div class="card-body">
                                    <?php echo nl2br($pedido['observacoes']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Impressão -->
    <div class="modal fade" id="modalImpressao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Visualização da Impressão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <!-- Área de impressão -->
                    <div id="areaImpressao" style="width: 65mm; margin: 0 auto; padding: 2mm;">
                        <!-- Cabeçalho do Cupom -->
                        <div class="text-center mb-1" style="border-bottom: 1px dashed #000; padding-bottom: 2mm;">
                            <h5 class="mb-1" style="font-size: 14px;"><?php echo $site_titulo; ?></h5>
                            <p class="mb-1" style="font-size: 12px;">Pedido #<?php echo $pedido_id; ?></p>
                            <p class="mb-1" style="font-size: 10px;"><?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?></p>
                        </div>

                        <!-- Dados do Cliente -->
                        <div class="mb-1" style="font-size: 8px;">
                            <p class="mb-0"><strong>Cliente:</strong> <?php echo $pedido['cliente_nome']; ?></p>
                            <p class="mb-0"><strong>Tel:</strong> <?php echo $pedido['telefone']; ?></p>
                            <p class="mb-0"><strong>End:</strong> <?php echo $pedido['endereco']; ?></p>
                        </div>

                        <!-- Itens do Pedido -->
                        <div style="font-size: 10px; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 5px 0;">
                            <table style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th style="text-align: left;">Item</th>
                                        <th style="text-align: center; width: 30px;">Qtd</th>
                                        <th style="text-align: right; width: 60px;">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $itens->data_seek(0);
                                    while ($item = $itens->fetch_assoc()): 
                                        $subtotal = $item['quantidade'] * $item['preco_unitario'];
                                    ?>
                                    <tr>
                                        <td style="text-align: left; font-size: 10px;">
                                            <?php echo $item['produto_nome']; ?>
                                            <?php if ($item['observacoes']): ?>
                                                <br><small style="font-size: 8px;"><?php echo $item['observacoes']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;"><?php echo $item['quantidade']; ?></td>
                                        <td style="text-align: right;"><?php echo formatPrice($subtotal); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Totais -->
                        <div style="font-size: 10px; padding-top: 5px;">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="text-align: right;">Subtotal:</td>
                                    <td style="text-align: right; width: 60px;"><?php echo formatPrice($pedido['total']); ?></td>
                                </tr>
                                <?php if ($pedido['forma_entrega'] === 'Delivery' && $pedido['valor_entrega'] > 0): ?>
                                <tr>
                                    <td style="text-align: right;">Taxa (<?php echo $pedido['bairro']; ?>):</td>
                                    <td style="text-align: right;"><?php echo formatPrice($pedido['valor_entrega']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td style="text-align: right;"><strong>Total:</strong></td>
                                    <td style="text-align: right;"><strong><?php echo formatPrice($pedido['total_com_taxa']); ?></strong></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Forma de Pagamento -->
                        <div style="text-align: center; margin-top: 5px; font-size: 10px;">
                            <p class="mb-0"><strong>Pgto:</strong> <?php echo $pedido['forma_pagamento']; ?></p>
                            <p class="mb-0"><strong>Entrega:</strong> <?php echo $pedido['forma_entrega']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" onclick="imprimirPedido()">Imprimir</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Substituir o botão toggle da sidebar -->
    <button class="btn btn-primary d-md-none position-fixed top-0 start-0 mt-2 ms-2 rounded-circle" 
            onclick="toggleSidebar()" 
            style="z-index: 1001; width: 42px; height: 42px;">
        <i class="bi bi-list"></i>
    </button>

    <script>
    // Função para alternar a sidebar
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('show');
    }

    // Fechar sidebar quando clicar em algum link dentro dela (em dispositivos móveis)
    document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                document.querySelector('.sidebar').classList.remove('show');
            }
        });
    });

    // Fechar sidebar quando clicar fora dela (em dispositivos móveis)
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const toggleButton = document.querySelector('.btn-primary.d-md-none.rounded-circle');
        
        if (window.innerWidth <= 768 && 
            !sidebar.contains(event.target) && 
            !toggleButton.contains(event.target) && 
            sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>