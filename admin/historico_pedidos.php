<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

// Buscar título do site das configurações
$sql_config = "SELECT valor FROM configuracoes WHERE chave = 'site_titulo'";
$result_config = $conn->query($sql_config);
$site_titulo = $result_config->fetch_assoc()['valor'] ?? SITE_TITLE;

// Parâmetros de paginação
$por_pagina = 10;
$pagina = $_GET['pagina'] ?? 1;
$offset = ($pagina - 1) * $por_pagina;

// Processar filtros
$where = "p.status IN ('cancelado', 'entregue')";
$params = [];
$tipos = "";

if (isset($_GET['filtro'])) {
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    $mes = $_GET['mes'] ?? '';
    $dia = $_GET['dia'] ?? '';

    if ($data_inicio && $data_fim) {
        $where .= " AND DATE(p.created_at) BETWEEN ? AND ?";
        $params[] = $data_inicio;
        $params[] = $data_fim;
        $tipos .= "ss";
    } elseif ($mes) {
        $where .= " AND MONTH(p.created_at) = ? AND YEAR(p.created_at) = ?";
        $mes_ano = explode('-', $mes);
        $params[] = $mes_ano[1];
        $params[] = $mes_ano[0];
        $tipos .= "ss";
    } elseif ($dia) {
        $where .= " AND DATE(p.created_at) = ?";
        $params[] = $dia;
        $tipos .= "s";
    }
}

// Contar total de registros para paginação
$sql_count = "SELECT COUNT(*) as total FROM pedidos p WHERE $where";
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($tipos, ...$params);
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $por_pagina);

// Calcular total dos pedidos filtrados
$sql_total = "SELECT SUM(p.total) as total_valor FROM pedidos p WHERE $where";
$stmt_total = $conn->prepare($sql_total);
if (!empty($params)) {
    $stmt_total->bind_param($tipos, ...$params);
}
$stmt_total->execute();
$total_valor = $stmt_total->get_result()->fetch_assoc()['total_valor'] ?? 0;

// Buscar pedidos com filtro e paginação
$sql = "SELECT p.*, u.nome as cliente_nome 
        FROM pedidos p 
        LEFT JOIN usuarios u ON p.usuario_id = u.id 
        WHERE $where
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$params[] = $por_pagina;
$params[] = $offset;
$tipos .= "ii";
$stmt->bind_param($tipos, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_titulo; ?></title>
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

    .sidebar.show {
        width: 250px;
    }

    .main-content {
        flex-grow: 1;
        margin-left: 250px;
        overflow: auto;
        transition: margin-left 0.3s ease;
        padding: 20px;
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
    }

    .card-stats {
        transition: transform 0.2s ease;
    }

    .card-stats:hover {
        transform: translateY(-5px);
    }

    .table {
        margin-bottom: 0;
    }

    .table th {
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
        border-top: none;
    }

    .table td {
        vertical-align: middle;
    }

    .badge {
        padding: 0.5em 0.8em;
        font-weight: 500;
    }

    .btn-primary {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .btn-primary:hover {
        background: var(--bs-primary-hover);
        border-color: var(--bs-primary-hover);
    }

    .pagination .page-link {
        color: var(--bs-primary);
        border-color: #dee2e6;
    }

    .pagination .active .page-link {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .form-control:focus {
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
            padding: 15px;
        }
        
        .sidebar .nav-text {
            display: block; /* Manter textos do menu visíveis */
        }
    }

    /* Animações */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .card {
        animation: fadeIn 0.3s ease-out;
    }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="content">
                <div class="container-fluid">

                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Filtrar por período</label>
                                    <div class="input-group">
                                        <input type="date" name="data_inicio" class="form-control" value="<?php echo $_GET['data_inicio'] ?? ''; ?>">
                                        <span class="input-group-text">até</span>
                                        <input type="date" name="data_fim" class="form-control" value="<?php echo $_GET['data_fim'] ?? ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Filtrar por mês</label>
                                    <input type="month" name="mes" class="form-control" value="<?php echo $_GET['mes'] ?? ''; ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Filtrar por dia</label>
                                    <input type="date" name="dia" class="form-control" value="<?php echo $_GET['dia'] ?? ''; ?>">
                                </div>
                                
                                <div class="col-md-2 d-flex align-items-end">
                                    <div class="d-grid gap-2 w-100">
                                        <button type="submit" name="filtro" class="btn btn-primary">
                                            <i class="bi bi-search"></i> Filtrar
                                        </button>
                                        <a href="historico_pedidos.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-circle"></i> Limpar
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if (isset($_GET['filtro'])): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="card bg-success text-white h-100">
                                            <div class="card-body d-flex flex-column justify-content-between">
                                                <h6 class="card-title">Total em Pedidos</h6>
                                                <div class="mt-2">
                                                    <h4 class="mb-0">R$ <?php echo number_format($total_valor, 2, ',', '.'); ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-info text-white h-100">
                                            <div class="card-body d-flex flex-column justify-content-between">
                                                <h6 class="card-title">Quantidade de Pedidos</h6>
                                                <div class="mt-2">
                                                    <h4 class="mb-0"><?php echo $total_registros; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-primary text-white h-100">
                                            <div class="card-body d-flex flex-column justify-content-between">
                                                <h6 class="card-title">Média por Pedido</h6>
                                                <div class="mt-2">
                                                    <h4 class="mb-0">R$ <?php echo $total_registros > 0 ? number_format($total_valor / $total_registros, 2, ',', '.') : '0,00'; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-warning text-dark h-100">
                                            <div class="card-body d-flex flex-column justify-content-between">
                                                <h6 class="card-title">Período</h6>
                                                <div class="mt-2">
                                                    <span class="small">
                                                        <?php
                                                        if (!empty($_GET['data_inicio']) && !empty($_GET['data_fim'])) {
                                                            echo date('d/m/Y', strtotime($_GET['data_inicio'])) . 
                                                                 ' - ' . date('d/m/Y', strtotime($_GET['data_fim']));
                                                        } elseif (!empty($_GET['mes'])) {
                                                            echo strftime('%B/%Y', strtotime($_GET['mes'] . '-01'));
                                                        } elseif (!empty($_GET['dia'])) {
                                                            echo date('d/m/Y', strtotime($_GET['dia']));
                                                        } else {
                                                            echo 'Todos os períodos';
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Cliente</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($pedido = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $pedido['id']; ?></td>
                                                <td><?php echo $pedido['cliente_nome']; ?></td>
                                                <td>R$ <?php echo number_format($pedido['total'], 2, ',', '.'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo getStatusColor($pedido['status']); ?>">
                                                        <?php echo ucfirst($pedido['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?></td>
                                                <td>
                                                    <a href="pedido.php?id=<?php echo $pedido['id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger ms-1" 
                                                            onclick="confirmarExclusao(<?php echo $pedido['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($total_paginas > 1): ?>
                                <nav aria-label="Navegação de páginas" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($pagina > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?pagina=<?php echo ($pagina-1); ?><?php echo isset($_GET['filtro']) ? '&' . http_build_query(array_filter($_GET)) : ''; ?>">
                                                    Anterior
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                            <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                                <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo isset($_GET['filtro']) ? '&' . http_build_query(array_filter($_GET)) : ''; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($pagina < $total_paginas): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?pagina=<?php echo ($pagina+1); ?><?php echo isset($_GET['filtro']) ? '&' . http_build_query(array_filter($_GET)) : ''; ?>">
                                                    Próxima
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-primary d-md-none position-fixed top-0 start-0 mt-2 ms-2 rounded-circle" 
            onclick="document.querySelector('.sidebar').classList.toggle('show')" 
            style="z-index: 1001; width: 42px; height: 42px;">
        <i class="bi bi-list"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    function confirmarExclusao(id) {
        Swal.fire({
            title: 'Tem certeza?',
            text: "Esta ação não poderá ser revertida!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                excluirPedido(id);
            }
        });
    }

    function excluirPedido(id) {
        Swal.fire({
            title: 'Excluir Pedido',
            text: "Esta ação não poderá ser revertida!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Não'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('excluir_pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id,
                        acao: 'excluir'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: data.message || 'Erro ao excluir pedido'
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao processar a exclusão'
                    });
                });
            }
        });
    }
    </script>
    <script>
    // Garantir que apenas um tipo de filtro seja usado por vez
    document.addEventListener('DOMContentLoaded', function() {
        const dataInicio = document.querySelector('input[name="data_inicio"]');
        const dataFim = document.querySelector('input[name="data_fim"]');
        const mes = document.querySelector('input[name="mes"]');
        const dia = document.querySelector('input[name="dia"]');

        function disableOtherInputs(currentInput) {
            const inputs = [dataInicio, dataFim, mes, dia];
            inputs.forEach(input => {
                if (input !== currentInput && input !== dataInicio && input !== dataFim) {
                    input.value = '';
                    input.disabled = currentInput.value !== '';
                }
            });
        }

        [mes, dia].forEach(input => {
            input.addEventListener('input', function() {
                disableOtherInputs(this);
            });
        });

        [dataInicio, dataFim].forEach(input => {
            input.addEventListener('input', function() {
                mes.disabled = dataInicio.value !== '' || dataFim.value !== '';
                dia.disabled = dataInicio.value !== '' || dataFim.value !== '';
                mes.value = '';
                dia.value = '';
            });
        });
    });
    </script>
    <script>
    // Evento para fechar sidebar ao clicar em links
    document.addEventListener('DOMContentLoaded', function() {
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
    });
    </script>
</body>
</html>