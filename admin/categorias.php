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