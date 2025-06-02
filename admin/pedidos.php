<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

$mensagem = '';

// Buscar título do site das configurações
$sql_config = "SELECT valor FROM configuracoes WHERE chave = 'site_titulo'";
$result_config = $conn->query($sql_config);
$site_titulo = $result_config->fetch_assoc()['valor'] ?? SITE_TITLE;

// Atualizar status do pedido
if (isset($_POST['atualizar_status'])) {
    $pedido_id = (int)$_POST['pedido_id'];
    $novo_status = $_POST['novo_status'];
    
    $sql = "UPDATE pedidos SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $novo_status, $pedido_id);
    
    if ($stmt->execute()) {
        $mensagem = "Status atualizado com sucesso!";
    }
}

// Buscar pedidos
$sql = "SELECT p.*, u.nome as cliente_nome 
        FROM pedidos p 
        LEFT JOIN usuarios u ON p.usuario_id = u.id 
        WHERE p.status NOT IN ('entregue', 'cancelado')
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_titulo; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    :root {
        --bs-primary: #3491D0;
        --bs-primary-rgb: 52, 145, 208;
        --bs-primary-hover: #2C475D;
    }

    body {
        background-color: #f8f9fa;
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

    .main-content {
        flex-grow: 1;
        margin-left: 250px;
        overflow: auto;
        background-color: #f8f9fa;
        transition: margin-left 0.3s ease;
    }

    .content {
        padding: 2rem;
    }

    /* Cards e Tabelas */
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }

    .table {
        margin-bottom: 0;
    }

    .table th {
        border-top: none;
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
    }

    .table td {
        vertical-align: middle;
    }

    /* Status Badges */
    .badge {
        padding: 0.5em 0.8em;
        font-weight: 500;
    }

    /* Botões */
    .btn-info {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
        color: white;
    }

    .btn-info:hover {
        background-color: var(--bs-primary-hover);
        border-color: var(--bs-primary-hover);
        color: white;
    }

    /* Notificações */
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: #28a745;
        color: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        z-index: 1050;
        display: flex;
        align-items: center;
        gap: 0.5rem;
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

        .table-responsive {
            border-radius: 12px;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .notification {
            left: 20px;
            right: 20px;
            text-align: center;
            justify-content: center;
        }
    }

    /* Animações */
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .notification {
        animation: slideIn 0.3s ease-out;
    }

    /* Botão do menu mobile */
    .btn-toggle-sidebar {
        position: fixed;
        top: 10px;
        left: 10px;
        z-index: 1002;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: none;
    }

    @media (max-width: 768px) {
        .btn-toggle-sidebar {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }
    </style>
</head>
<body>
    <button class="btn btn-primary btn-toggle-sidebar d-md-none" 
            onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="content">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1>Pedidos</h1>
                    </div>

                    <?php if ($mensagem): ?>
                        <div class="alert alert-success"><?php echo $mensagem; ?></div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Cliente</th>
                                            <th>Status</th>
                                            <th>Total</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($pedido = $result->fetch_assoc()): ?>
                                            <tr data-pedido-id="<?php echo $pedido['id']; ?>">
                                                <td>#<?php echo $pedido['id']; ?></td>
                                                <td><?php echo $pedido['cliente_nome']; ?></td>
                                                <td>
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
                                                    <span class="badge bg-<?php echo $badge_color; ?>">
                                                        <?php echo ucfirst($pedido['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatPrice($pedido['total']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?></td>
                                                <td>
                                                    <a href="pedido.php?id=<?php echo $pedido['id']; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i>
                                                        Ver Detalhes
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
            </div>
        </div>
    </div>

    <audio id="notificationSound">
        <source src="../assets/alerta.mp3" type="audio/mpeg">
    </audio>

    <script>
    let lastOrderId = <?php 
        $lastOrder = $conn->query("SELECT MAX(id) as max_id FROM pedidos WHERE status = 'pendente'")->fetch_assoc();
        echo $lastOrder['max_id'] ?? 0; 
    ?>;

    function playNotification() {
        const audio = document.getElementById('notificationSound');
        
        // Garantir que o áudio volte ao início
        audio.currentTime = 0;
        
        // Promessa para tocar o áudio
        const playPromise = audio.play();

        if (playPromise !== undefined) {
            playPromise.catch(error => {
                console.log("Erro ao tocar áudio:", error);
            });
        }
    }

    function checkNewOrders() {
        fetch('check_new_orders.php')
            .then(response => response.json())
            .then data => {
                console.log("Verificando pedidos:", data); // Debug
                
                if (data.new_order && data.last_order_id > lastOrderId) {
                    // Tocar som
                    playNotification();
                    
                    // Atualizar último ID
                    lastOrderId = data.last_order_id;
                    
                    // Mostrar notificação
                    if ("Notification" in window && Notification.permission === "granted") {
                        new Notification("Novo Pedido!", {
                            body: "Você tem um novo pedido para revisar",
                            icon: "/favicon.ico"
                        });
                    }
                    
                    // Criar notificação visual
                    const notification = document.createElement('div');
                    notification.className = 'notification';
                    notification.textContent = 'Novo pedido recebido!';
                    document.body.appendChild(notification);
                    
                    // Remover notificação após 5 segundos
                    setTimeout(() => {
                        notification.remove();
                        window.location.reload();
                    }, 2000);
                }
            })
            .catch(error => {
                console.error("Erro na verificação:", error);
            });
    }

    // Verificar permissão de notificação
    if ("Notification" in window) {
        if (Notification.permission !== 'denied') {
            Notification.requestPermission();
        }
    }

    // Primeira verificação imediata
    checkNewOrders();

    // Verificar a cada 10 segundos
    setInterval(checkNewOrders, 10000);

    // Modificar a verificação do DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        const audio = document.getElementById('notificationSound');
        if (audio) {
            // código do audio...
        }
    });

    // Função para verificar novos pedidos
    function verificarNovosPedidos() {
        fetch('check_new_orders.php')
            .then(response => response.json())
            .then(data => {
                console.log('Resposta:', data); // Debug
                
                if (data.new_order) {
                    const audio = document.getElementById('notificationSound');
                    audio.play()
                        .then(() => {
                            console.log('Áudio tocado com sucesso');
                            
                            // Mostrar notificação visual
                            const notification = document.createElement('div');
                            notification.className = 'notification';
                            notification.innerHTML = `
                                <i class="bi bi-bell-fill me-2"></i>
                                Novo pedido recebido!
                            `;
                            document.body.appendChild(notification);
                            
                            // Recarregar página após 2 segundos
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        })
                        .catch(error => {
                            console.error('Erro ao tocar áudio:', error);
                        });
                }
            })
            .catch(error => {
                console.error('Erro na verificação:', error);
            });
    }

    // Verificar a cada 5 segundos
    setInterval(verificarNovosPedidos, 5000);

    // Verificar imediatamente ao carregar a página
    document.addEventListener('DOMContentLoaded', verificarNovosPedidos);

    function excluirPedido(id) {
        Swal.fire({
            title: 'Cancelar Pedido',
            text: "Deseja realmente cancelar este pedido?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, cancelar!',
            cancelButtonText: 'Não'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('excluir_pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        id: parseInt(id),
                        acao: 'cancelar'
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
                        Swal.fire('Erro', data.message, 'error');
                    }
                });
            }
        });
    }

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
        const toggleButton = document.querySelector('.btn-toggle-sidebar');
        
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