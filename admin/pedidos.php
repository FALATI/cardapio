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
    <title>Pedidos - <?php echo $site_titulo; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #333;
            color: white;
            flex-shrink: 0;
        }

        .main-content {
            flex-grow: 1;
            overflow: auto;
        }

        .content {
            padding: 1.5rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100px;
            }
            
            .sidebar .nav-text {
                display: none;
            }
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            background: #28a745;
            color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: slideIn 0.5s ease-out;
        }

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
    </style>
</head>
<body>
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
            .then(data => {
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
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>