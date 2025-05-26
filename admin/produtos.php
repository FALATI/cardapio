<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

$mensagem = '';

// Excluir produto
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    $sql = "UPDATE produtos SET status = 0 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $mensagem = "Produto excluído com sucesso!";
    }
}

// Buscar produtos
$sql = "SELECT p.*, c.nome as categoria_nome 
        FROM produtos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        WHERE p.status = 1 
        ORDER BY p.nome";
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
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Produtos</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#produtoModal">
                <i class="bi bi-plus-lg"></i> Novo Produto
            </button>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert alert-success"><?php echo $mensagem; ?></div>
        <?php endif; ?>

        <!-- Modal de Produto -->
        <div class="modal fade" id="produtoModal" tabindex="-1" aria-labelledby="produtoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="produtoModalLabel">Novo Produto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formProduto" method="POST" enctype="multipart/form-data">
                            <input type="hidden" id="produto_id" name="produto_id">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="nome" class="form-label">Nome</label>
                                        <input type="text" class="form-control" id="nome" name="nome" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="preco" class="form-label">Preço</label>
                                        <input type="text" class="form-control" id="preco" name="preco" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="categoria_id" class="form-label">Categoria</label>
                                        <select class="form-select" id="categoria_id" name="categoria_id" required>
                                            <option value="">Selecione...</option>
                                            <?php 
                                            $sql_cats = "SELECT * FROM categorias WHERE status = 1 ORDER BY nome";
                                            $cats = $conn->query($sql_cats);
                                            while($cat = $cats->fetch_assoc()):
                                            ?>
                                                <option value="<?php echo $cat['id']; ?>">
                                                    <?php echo $cat['nome']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="imagem" class="form-label">Imagem</label>
                                        <input type="file" class="form-control" id="imagem" name="imagem">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="destaque" name="destaque">
                                    <label class="form-check-label" for="destaque">
                                        Produto em destaque
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" onclick="salvarProduto()">Salvar</button>
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
                                <th>Imagem</th>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Preço</th>
                                <th>Destaque</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($produto = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $produto['id']; ?></td>
                                    <td>
                                        <?php if($produto['imagem']): ?>
                                            <img src="../uploads/produtos/<?php echo $produto['imagem']; ?>" 
                                                 width="50" height="50" class="rounded">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $produto['nome']; ?></td>
                                    <td><?php echo $produto['categoria_nome']; ?></td>
                                    <td><?php echo formatPrice($produto['preco']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $produto['destaque'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $produto['destaque'] ? 'Sim' : 'Não'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="editarProduto(<?php echo htmlspecialchars(json_encode($produto)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?excluir=<?php echo $produto['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Tem certeza que deseja excluir este produto?')">
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
    // Função para editar produto
    function editarProduto(produto) {
        // Atualiza o título do modal
        document.getElementById('produtoModalLabel').textContent = 'Editar Produto';
        
        // Preenche os campos do formulário
        document.getElementById('produto_id').value = produto.id;
        document.getElementById('nome').value = produto.nome;
        document.getElementById('preco').value = (produto.preco).replace('.', ',');
        document.getElementById('descricao').value = produto.descricao;
        document.getElementById('categoria_id').value = produto.categoria_id;
        document.getElementById('destaque').checked = produto.destaque == '1';

        // Se houver imagem, mostra preview
        if (produto.imagem) {
            const imgPreview = document.createElement('div');
            imgPreview.className = 'mt-2';
            imgPreview.innerHTML = `
                <img src="../uploads/produtos/${produto.imagem}" 
                     class="rounded" style="max-height: 100px">
                <div class="small text-muted mt-1">Imagem atual</div>
            `;
            document.getElementById('imagem').parentNode.appendChild(imgPreview);
        }

        // Abre o modal
        const modal = new bootstrap.Modal(document.getElementById('produtoModal'));
        modal.show();
    }

    // Atualiza a função salvarProduto para lidar com edição
    function salvarProduto() {
        const form = document.getElementById('formProduto');
        const formData = new FormData(form);
        const produto_id = document.getElementById('produto_id').value;
        
        // Mostra loader
        const btnSalvar = document.querySelector('.modal-footer .btn-primary');
        const btnText = btnSalvar.innerHTML;
        btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';
        btnSalvar.disabled = true;

        // Define a URL baseada se é edição ou novo produto
        const url = produto_id ? 'produto_atualizar.php' : 'produto_salvar.php';

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostra mensagem de sucesso
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

                // Fecha o modal e recarrega a página após 1 segundo
                const modal = bootstrap.Modal.getInstance(document.getElementById('produtoModal'));
                modal.hide();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                // Mostra mensagem de erro
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
            alert('Erro ao salvar produto. Verifique o console para mais detalhes.');
        })
        .finally(() => {
            // Restaura o botão
            btnSalvar.innerHTML = btnText;
            btnSalvar.disabled = false;
        });
    }

    // Limpa o formulário quando o modal é fechado
    document.getElementById('produtoModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('formProduto').reset();
        document.getElementById('produto_id').value = '';
        document.getElementById('produtoModalLabel').textContent = 'Novo Produto';
        
        // Remove preview de imagem se existir
        const imgPreview = document.querySelector('#imagem').parentNode.querySelector('.mt-2');
        if (imgPreview) {
            imgPreview.remove();
        }
    });

    // Formata o campo de preço enquanto digita
    document.getElementById('preco').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = (Number(value) / 100).toFixed(2);
        e.target.value = value;
    });
    </script>
</body>
</html>