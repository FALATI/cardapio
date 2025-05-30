<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

$mensagem = '';

// Ativar/Desativar usuário
if (isset($_GET['alternar_status'])) {
    $id = (int)$_GET['alternar_status'];
    $sql = "UPDATE usuarios SET status = NOT status WHERE id = ? AND tipo != 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $mensagem = "Status do usuário alterado com sucesso!";
    }
}

// Buscar usuários
$sql = "SELECT * FROM usuarios WHERE tipo = 'cliente' ORDER BY nome";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_TITLE; ?></title>
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

    /* Sidebar */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 250px;
        background: linear-gradient(135deg, #2C475D 0%, #3491D0 100%);
        padding-top: 1rem;
        transition: all 0.3s ease;
        z-index: 1000;
    }

    /* Conteúdo Principal */
    .main-content {
        margin-left: 250px;
        padding: 2rem;
        transition: margin 0.3s ease;
    }

    /* Cards e Tabelas */
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        margin-bottom: 1.5rem;
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

    /* Modal */
    .modal-content {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .modal-header {
        background: #f8f9fa;
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    /* Botões */
    .btn-primary {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .btn-primary:hover,
    .btn-primary:active {
        background: var(--bs-primary-hover) !important;
        border-color: var(--bs-primary-hover) !important;
    }

    .btn-group-sm > .btn,
    .btn-sm {
        padding: 0.4rem 0.8rem;
    }

    /* Badges */
    .badge {
        padding: 0.5em 0.8em;
        font-weight: 500;
    }

    /* Forms */
    .form-control:focus,
    .form-select:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.2rem rgba(52, 145, 208, 0.25);
    }

    /* Alerts */
    .alert {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    /* Responsividade */
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

        .table-responsive {
            border-radius: 12px;
        }
    }

    /* Animações */
    .card {
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .alert {
        animation: fadeIn 0.3s ease;
    }

    .acoes-wrapper {
        display: flex;
        gap: 0.5rem;
        flex-wrap: nowrap;
        justify-content: flex-end;
    }

    @media (max-width: 768px) {
        .acoes-wrapper {
            justify-content: center;
        }
        
        .table td:last-child {
            min-width: 135px; /* Largura mínima para os botões */
        }
    }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Usuários</h2>
            <button type="button" class="btn btn-primary" onclick="abrirModal()">
                <i class="bi bi-person-plus"></i> Novo Usuário
            </button>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <!-- Modal de Usuário -->
        <div class="modal fade" id="usuarioModal" tabindex="-1" aria-labelledby="usuarioModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="usuarioModalLabel">Novo Usuário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formUsuario" method="POST">
                            <input type="hidden" id="usuario_id" name="usuario_id">
                            
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="tel" class="form-control" id="telefone" name="telefone" required 
                                       placeholder="(00) 00000-0000">
                            </div>

                            <div class="mb-3">
                                <label for="endereco" class="form-label">Endereço</label>
                                <textarea class="form-control" id="endereco" name="endereco" rows="2" required
                                          placeholder="Rua, número, complemento..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="bairro" class="form-label">Bairro</label>
                                <input type="text" class="form-control" id="bairro" name="bairro" required>
                            </div>

                            <div class="mb-3">
                                <label for="senha" class="form-label" id="senhaLabel">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha">
                                <div class="form-text" id="senhaHelp"></div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                                    <label class="form-check-label" for="status">
                                        Usuário ativo
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" onclick="salvarUsuario()">Salvar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de usuários -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Endereço</th>
                                <th>Bairro</th>
                                <th>Status</th>
                                <th>Data de Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($usuario = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $usuario['id']; ?></td>
                                    <td><?php echo $usuario['nome']; ?></td>
                                    <td><?php echo $usuario['email']; ?></td>
                                    <td><?php echo $usuario['telefone']; ?></td>
                                    <td><?php echo nl2br($usuario['endereco']); ?></td>
                                    <td><?php echo $usuario['bairro']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $usuario['status'] ? 'success' : 'danger'; ?>">
                                            <?php echo $usuario['status'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                                    <td>
                                        <div class="acoes-wrapper">
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick='editarUsuario(<?php echo json_encode($usuario); ?>)' 
                                                    title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="excluirUsuario(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['nome']); ?>')" 
                                                    title="Excluir">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    onclick="verHistoricoPedidos(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['nome']); ?>')" 
                                                    title="Histórico">
                                                <i class="bi bi-cart"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Histórico de Pedidos -->
    <div class="modal fade" id="historicoPedidosModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Histórico de Pedidos - <span id="nomeClienteHistorico"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Pedido #</th>
                                    <th>Data</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="historicoPedidosBody">
                                <tr>
                                    <td colspan="5" class="text-center">Carregando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmarExclusaoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Tem certeza que deseja excluir o usuário <strong id="nomeUsuarioExcluir"></strong>?</p>
                    <p class="text-danger mb-0"><small>Esta ação não pode ser desfeita!</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarExclusao">
                        <i class="bi bi-trash me-2"></i>Excluir
                    </button>
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
    <script>
    function abrirModal(usuario = null) {
        document.getElementById('formUsuario').reset();
        document.getElementById('usuario_id').value = '';
        document.getElementById('usuarioModalLabel').textContent = 'Novo Usuário';
        document.getElementById('senha').required = true;
        document.getElementById('senhaLabel').textContent = 'Senha';
        document.getElementById('senhaHelp').textContent = '';
        document.getElementById('status').checked = true;
        
        if (usuario) {
            document.getElementById('usuarioModalLabel').textContent = 'Editar Usuário';
            document.getElementById('usuario_id').value = usuario.id;
            document.getElementById('nome').value = usuario.nome;
            document.getElementById('email').value = usuario.email;
            document.getElementById('senha').required = false;
            document.getElementById('senhaLabel').textContent = 'Nova Senha';
            document.getElementById('senhaHelp').textContent = 'Deixe em branco para manter a senha atual';
            document.getElementById('status').checked = usuario.status == '1';
        }

        const modal = new bootstrap.Modal(document.getElementById('usuarioModal'));
        modal.show();
    }

    function editarUsuario(usuario) {
        abrirModal(usuario);
        if (usuario.telefone) document.getElementById('telefone').value = usuario.telefone;
        if (usuario.endereco) document.getElementById('endereco').value = usuario.endereco;
        if (usuario.bairro) document.getElementById('bairro').value = usuario.bairro;
    }

    function salvarUsuario() {
        const form = document.getElementById('formUsuario');
        const formData = new FormData(form);
        const usuario_id = document.getElementById('usuario_id').value;
        
        // Mostra loader
        const btnSalvar = document.querySelector('.modal-footer .btn-primary');
        const btnText = btnSalvar.innerHTML;
        btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';
        btnSalvar.disabled = true;

        // Define a URL baseada se é edição ou novo usuário
        const url = usuario_id ? 'usuario_atualizar.php' : 'usuario_salvar.php';

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.innerHTML = `
                    ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.main-content').insertBefore(
                    alertDiv, 
                    document.querySelector('.main-content').firstChild
                );

                const modal = bootstrap.Modal.getInstance(document.getElementById('usuarioModal'));
                modal.hide();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                    ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.modal-body').insertBefore(
                    alertDiv, 
                    document.querySelector('.modal-body').firstChild
                );
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao salvar usuário');
        })
        .finally(() => {
            btnSalvar.innerHTML = btnText;
            btnSalvar.disabled = false;
        });
    }

    // Máscara para o telefone
    document.getElementById('telefone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 11) {
            value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
            value = value.replace(/(\d)(\d{4})$/, '$1-$2');
            e.target.value = value;
        }
    });

    function verHistoricoPedidos(usuario_id, nome) {
        document.getElementById('nomeClienteHistorico').textContent = nome;
        const tbody = document.getElementById('historicoPedidosBody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Carregando...</td></tr>';

        const modal = new bootstrap.Modal(document.getElementById('historicoPedidosModal'));
        modal.show();

        fetch(`buscar_pedidos_usuario.php?id=${usuario_id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.pedidos.length > 0) {
                    let html = '';
                    data.pedidos.forEach(pedido => {
                        const statusClass = {
                            'pendente': 'primary',
                            'preparando': 'warning',
                            'saiu_entrega': 'info',
                            'entregue': 'success',
                            'cancelado': 'danger'
                        }[pedido.status] || 'primary';

                        html += `
                            <tr>
                                <td>#${pedido.id}</td>
                                <td>${pedido.data_formatada}</td>
                                <td>R$ ${pedido.total_formatado}</td>
                                <td>
                                    <span class="badge bg-${statusClass}">
                                        ${pedido.status.charAt(0).toUpperCase() + pedido.status.slice(1)}
                                    </span>
                                </td>
                                <td>
                                    <a href="pedido.php?id=${pedido.id}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = html;
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Nenhum pedido encontrado</td></tr>';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Erro ao carregar pedidos</td></tr>';
            });
    }

    function excluirUsuario(id, nome) {
        // Preenche o modal de confirmação
        document.getElementById('nomeUsuarioExcluir').textContent = nome;
        
        // Configura o botão de confirmação
        document.getElementById('btnConfirmarExclusao').onclick = function() {
            const formData = new FormData();
            formData.append('id', id);
            
            // Desabilita botão de exclusão
            const btn = this;
            const btnHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            
            fetch('usuario_excluir.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${data.success ? 'success' : 'danger'} alert-dismissible fade show`;
                alertDiv.innerHTML = `
                    <i class="bi bi-${data.success ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                
                document.querySelector('.main-content').insertBefore(
                    alertDiv, 
                    document.querySelector('.main-content').firstChild
                );
                
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('confirmarExclusaoModal')).hide();
                    setTimeout(() => window.location.reload(), 1500);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Erro ao excluir usuário. Por favor, tente novamente.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.main-content').insertBefore(
                    alertDiv, 
                    document.querySelector('.main-content').firstChild
                );
            })
            .finally(() => {
                // Reabilita botão
                btn.disabled = false;
                btn.innerHTML = btnHtml;
            });
        };
        
        // Mostra o modal de confirmação
        const modal = new bootstrap.Modal(document.getElementById('confirmarExclusaoModal'));
        modal.show();
    }
    </script>
</body>
</html>