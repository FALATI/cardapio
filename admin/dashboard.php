<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

// Buscar estatísticas
$sql_total_produtos = "SELECT COUNT(*) as total FROM produtos WHERE status = 1";
$sql_total_pedidos = "SELECT COUNT(*) as total 
                      FROM pedidos 
                      WHERE status NOT IN ('cancelado', 'entregue')";
$sql_total_usuarios = "SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'cliente' AND status = 1";

$result_produtos = $conn->query($sql_total_produtos)->fetch_assoc();
$result_pedidos = $conn->query($sql_total_pedidos)->fetch_assoc();
$result_usuarios = $conn->query($sql_total_usuarios)->fetch_assoc();

// Substituir a query de categorias por ganhos do dia
$sql_ganhos_dia = "SELECT 
    COUNT(*) as total_pedidos,
    COALESCE(SUM(total), 0) as total_valor
    FROM pedidos 
    WHERE DATE(created_at) = CURDATE() 
    AND status NOT IN ('cancelado')";

$result_ganhos = $conn->query($sql_ganhos_dia)->fetch_assoc();

// Buscar últimos pedidos (excluindo cancelados e entregues)
$sql_ultimos_pedidos = "SELECT p.*, u.nome as cliente_nome 
                        FROM pedidos p 
                        LEFT JOIN usuarios u ON p.usuario_id = u.id 
                        WHERE p.status NOT IN ('cancelado', 'entregue')
                        ORDER BY p.created_at DESC 
                        LIMIT 5";
$result_ultimos_pedidos = $conn->query($sql_ultimos_pedidos);

// Buscar título do site das configurações
$sql_config = "SELECT valor FROM configuracoes WHERE chave = 'site_titulo'";
$result_config = $conn->query($sql_config);
$site_titulo = $result_config->fetch_assoc()['valor'] ?? SITE_TITLE;
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
        }

        /* Sidebar responsiva */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(135deg, #2C475D 0%, #3491D0 100%);
            padding-top: 1rem;
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        /* Conteúdo principal responsivo */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: margin 0.3s ease;
        }

        /* Cards do dashboard */
        .card-dashboard {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        /* Cores dos cards */
        .card-dashboard.bg-primary {
            background: linear-gradient(135deg, #4B89DC 0%, #3369B9 100%) !important;
            border-left: 4px solid #2854A1;
        }

        .card-dashboard.bg-success {
            background: linear-gradient(135deg, #37BD8D 0%, #2D9D76 100%) !important;
            border-left: 4px solid #25865F;
        }

        .card-dashboard.bg-warning {
            background: linear-gradient(135deg, #FF9F43 0%, #FF8510 100%) !important;
            border-left: 4px solid #FF6B00;
        }

        .card-dashboard.bg-info {
            background: linear-gradient(135deg, #45ACD6 0%, #3498DB 100%) !important;
            border-left: 4px solid #2980B9;
        }

        /* Estilo interno dos cards */
        .card-dashboard .card-body {
            padding: 1.5rem;
        }

        .card-dashboard .card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            opacity: 1;
            color: rgba(255, 255, 255, 0.95);
        }

        .card-dashboard h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .card-dashboard a {
            font-size: 0.95rem;
            color: white !important;
            opacity: 0.9;
            transition: all 0.3s;
            text-decoration: none;
        }

        .card-dashboard small {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .card-dashboard .bi {
            font-size: 1.1rem;
            margin-right: 5px;
        }

        /* Tabela responsiva */
        .table-responsive {
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .table > :not(caption) > * > * {
            padding: 1rem;
        }

        /* Botões e badges */
        .btn-primary {
            background: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        .btn-primary:hover {
            background: var(--bs-primary-hover);
            border-color: var(--bs-primary-hover);
        }

        /* Media queries para responsividade */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .card-dashboard {
                margin-bottom: 1rem;
            }

            .top-stats {
                flex-direction: column;
            }

            .stats-card {
                margin-bottom: 1rem;
                width: 100%;
            }
        }

        /* Melhorias visuais */
        .toast {
            border-radius: 10px;
        }

        .badge {
            padding: 0.5em 0.8em;
            font-weight: 500;
        }

        .btn-test-sound {
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
        }

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .toast.show {
            animation: fadeIn 0.3s ease;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Botão para abrir/fechar sidebar em telas pequenas -->
    <button class="btn btn-primary d-md-none position-fixed top-0 start-0 mt-2 ms-2 rounded-circle" 
            onclick="document.querySelector('.sidebar').classList.toggle('show')" 
            style="z-index: 1001; width: 42px; height: 42px;">
        <i class="bi bi-list"></i>
    </button>

    <!-- Conteúdo Principal -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Dashboard</h2>
            <span class="text-muted">Bem-vindo, <?php echo $_SESSION['usuario_nome']; ?></span>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card card-dashboard bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total de Produtos</h5>
                        <h2 class="mb-0"><?php echo $result_produtos['total']; ?></h2>
                        <a href="produtos.php" class="text-white text-decoration-none">Ver todos <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Ganhos do Dia</h5>
                        <h2 class="mb-0">R$ <?php echo number_format($result_ganhos['total_valor'], 2, ',', '.'); ?></h2>
                        <small class="text-white-50">
                            <?php echo $result_ganhos['total_pedidos']; ?> pedidos hoje
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pedidos Ativos</h5>
                        <h2 class="mb-0"><?php echo $result_pedidos['total']; ?></h2>
                        <a href="pedidos.php" class="text-white text-decoration-none">Ver todos <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total de Clientes</h5>
                        <h2 class="mb-0"><?php echo $result_usuarios['total']; ?></h2>
                        <a href="usuarios.php" class="text-white text-decoration-none">Ver todos <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimos Pedidos -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Últimos Pedidos</h5>
            </div>
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
                            <?php while($pedido = $result_ultimos_pedidos->fetch_assoc()): ?>
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
                                        <a href="pedido.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Elemento de áudio -->
    <audio id="notificationSound" preload="auto" style="display:none">
        <source src="assets/alerta.mp3" type="audio/mpeg">
        Seu navegador não suporta o elemento de áudio.
    </audio>

    <!-- Botão de teste do som -->
   <div class="position-fixed bottom-0 end-0 m-4">
        <button type="button" class="btn btn-primary" onclick="tocarAlerta()">
            <i class="bi bi-volume-up-fill me-2"></i>
            Testar Som
        </button>
    </div>

    <script>
    let audioInitialized = false;
    let lastOrderId = <?php 
        $last = $conn->query("SELECT MAX(id) as max_id FROM pedidos")->fetch_assoc();
        echo $last['max_id'] ?? 0; 
    ?>;

    const audio = document.getElementById('notificationSound');
    audio.load();

    // Inicializar áudio com qualquer interação na página
    document.addEventListener('mousemove', function initAudio() {
        if (!audioInitialized) {
            audio.play().then(() => {
                audio.pause();
                audio.currentTime = 0;
                audioInitialized = true;
                document.removeEventListener('mousemove', initAudio);
            }).catch(console.error);
        }
    });

    function tocarAlerta() {
        if (!audioInitialized) {
            audio.play().then(() => {
                audioInitialized = true;
                tocarAlerta();
            }).catch(console.error);
            return;
        }

        audio.currentTime = 0;
        audio.volume = 1.0;
        audio.play().catch(error => {
            console.error('Erro ao tocar áudio:', error);
            audioInitialized = false;
        });
    }

    function atualizarDashboard() {
        fetch('get_dashboard_data.php')
            .then(response => response.json())
            .then(data => {
                // Atualizar estatísticas
                document.querySelector('.bg-primary h2').textContent = data.stats.produtos;
                document.querySelector('.bg-success h2').textContent = 
                    'R$ ' + Number(data.stats.ganhos_dia).toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                document.querySelector('.bg-success small').textContent = 
                    `${data.stats.pedidos_dia} pedidos hoje`;
                document.querySelector('.bg-warning h2').textContent = data.stats.pedidos;
                document.querySelector('.bg-info h2').textContent = data.stats.usuarios;

                // Verificar se há pedido novo
                if (data.ultimo_pedido && data.ultimo_pedido.id > lastOrderId) {
                    console.log('Novo pedido detectado:', data.ultimo_pedido.id, 'Último ID:', lastOrderId);
                    
                    // Tocar alerta
                    tocarAlerta();
                    
                    // Mostrar notificação
                    const notification = document.createElement('div');
                    notification.className = 'position-fixed top-0 end-0 p-3';
                    notification.style.zIndex = '9999';
                    notification.innerHTML = `
                        <div class="toast show bg-success text-white" role="alert">
                            <div class="toast-header">
                                <strong class="me-auto">Novo Pedido!</strong>
                                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                            </div>
                            <div class="toast-body">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-bell-fill me-2"></i>
                                    Pedido #${data.ultimo_pedido.id} recebido!
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(notification);
                    
                    // Atualizar último ID
                    lastOrderId = data.ultimo_pedido.id;
                    
                    setTimeout(() => notification.remove(), 3000);
                }

                // Atualizar tabela de pedidos
                const tbody = document.querySelector('tbody');
                tbody.innerHTML = data.pedidos.map(pedido => `
                    <tr>
                        <td>#${pedido.id}</td>
                        <td>${pedido.cliente_nome}</td>
                        <td>R$ ${pedido.total}</td>
                        <td>
                            <span class="badge bg-${pedido.status_color}">
                                ${pedido.status.charAt(0).toUpperCase() + pedido.status.slice(1)}
                            </span>
                        </td>
                        <td>${pedido.data}</td>
                        <td>
                            <a href="pedido.php?id=${pedido.id}" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                `).join('');
            })
            .catch(error => console.error('Erro:', error));
    }

    // Verificar a cada 3 segundos
    setInterval(atualizarDashboard, 3000);

    // Primeira verificação
    atualizarDashboard();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>