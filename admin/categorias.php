<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

$mensagem = '';

// Excluir categoria
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    $sql = "UPDATE categorias SET status = 0 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $mensagem = "Categoria excluída com sucesso!";
    }
}

// Buscar categorias
$sql = "SELECT * FROM categorias WHERE status = 1 ORDER BY nome";
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

        .btn-danger {
            transition: all 0.3s ease;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
        }

        /* Formulários */
        .form-control:focus,
        .form-select:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.2rem rgba(52, 145, 208, 0.25);
        }

        /* Alertas */
        .alert {
            border: none;
            border-radius: 10px;
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
    </style>
</head>
<body>
    <!-- Sidebar (igual ao dashboard) -->
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Categorias</h2>
            <button type="button" class="btn btn-primary" onclick="abrirModal()">
                <i class="bi bi-plus-lg"></i> Nova Categoria
            </button>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <!-- Modal de Categoria -->
        <div class="modal fade" id="categoriaModal" tabindex="-1" aria-labelledby="categoriaModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="categoriaModalLabel">Nova Categoria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formCategoria" method="POST">
                            <input type="hidden" id="categoria_id" name="categoria_id">
                            
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>

                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" onclick="salvarCategoria()">Salvar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($categoria = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $categoria['id']; ?></td>
                                    <td><?php echo $categoria['nome']; ?></td>
                                    <td><?php echo $categoria['descricao']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick='editarCategoria(<?php echo json_encode($categoria); ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?excluir=<?php echo $categoria['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Tem certeza que deseja excluir esta categoria?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <button class="btn btn-primary d-md-none position-fixed top-0 start-0 mt-2 ms-2 rounded-circle" 
                onclick="document.querySelector('.sidebar').classList.toggle('show')" 
                style="z-index: 1001; width: 42px; height: 42px;">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function abrirModal(categoria = null) {
        document.getElementById('formCategoria').reset();
        document.getElementById('categoria_id').value = '';
        document.getElementById('categoriaModalLabel').textContent = 'Nova Categoria';
        
        if (categoria) {
            document.getElementById('categoriaModalLabel').textContent = 'Editar Categoria';
            document.getElementById('categoria_id').value = categoria.id;
            document.getElementById('nome').value = categoria.nome;
            document.getElementById('descricao').value = categoria.descricao;
        }

        const modal = new bootstrap.Modal(document.getElementById('categoriaModal'));
        modal.show();
    }

    function editarCategoria(categoria) {
        abrirModal(categoria);
    }

    function salvarCategoria() {
        const form = document.getElementById('formCategoria');
        const formData = new FormData(form);
        const categoria_id = document.getElementById('categoria_id').value;
        
        // Mostra loader
        const btnSalvar = document.querySelector('.modal-footer .btn-primary');
        const btnText = btnSalvar.innerHTML;
        btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';
        btnSalvar.disabled = true;

        // Define a URL baseada se é edição ou nova categoria
        const url = categoria_id ? 'categoria_atualizar.php' : 'categoria_salvar.php';

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

                const modal = bootstrap.Modal.getInstance(document.getElementById('categoriaModal'));
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
            alert('Erro ao salvar categoria');
        })
        .finally(() => {
            btnSalvar.innerHTML = btnText;
            btnSalvar.disabled = false;
        });
    }
    </script>
</body>
</html>