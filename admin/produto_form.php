<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

$produto = [
    'id' => '',
    'nome' => '',
    'descricao' => '',
    'preco' => '',
    'categoria_id' => '',
    'destaque' => 0,
    'imagem' => ''
];

// Se for edição, buscar dados do produto
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM produtos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $produto = $result->fetch_assoc();
    }
}

// Buscar categorias para o select
$sql_categorias = "SELECT * FROM categorias WHERE status = 1 ORDER BY nome";
$result_categorias = $conn->query($sql_categorias);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = cleanInput($_POST['nome']);
    $descricao = cleanInput($_POST['descricao']);
    $preco = str_replace(',', '.', $_POST['preco']);
    $categoria_id = (int)$_POST['categoria_id'];
    $destaque = isset($_POST['destaque']) ? 1 : 0;

    if (empty($produto['id'])) {
        // Inserir
        $sql = "INSERT INTO produtos (nome, descricao, preco, categoria_id, destaque) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdii", $nome, $descricao, $preco, $categoria_id, $destaque);
    } else {
        // Atualizar
        $sql = "UPDATE produtos SET nome = ?, descricao = ?, preco = ?, 
                categoria_id = ?, destaque = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdiis", $nome, $descricao, $preco, $categoria_id, 
                         $destaque, $produto['id']);
    }

    if ($stmt->execute()) {
        header('Location: produtos.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo empty($produto['id']) ? 'Novo' : 'Editar'; ?> Produto - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="main-content">
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <?php echo empty($produto['id']) ? 'Novo' : 'Editar'; ?> Produto
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome</label>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                           value="<?php echo $produto['nome']; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="descricao" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="descricao" name="descricao" 
                                              rows="3"><?php echo $produto['descricao']; ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="preco" class="form-label">Preço</label>
                                    <input type="text" class="form-control" id="preco" name="preco" 
                                           value="<?php echo $produto['preco']; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="categoria_id" class="form-label">Categoria</label>
                                    <select class="form-select" id="categoria_id" name="categoria_id" required>
                                        <option value="">Selecione...</option>
                                        <?php while($categoria = $result_categorias->fetch_assoc()): ?>
                                            <option value="<?php echo $categoria['id']; ?>"
                                                <?php echo $categoria['id'] == $produto['categoria_id'] ? 'selected' : ''; ?>>
                                                <?php echo $categoria['nome']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="destaque" 
                                               name="destaque" <?php echo $produto['destaque'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="destaque">
                                            Produto em destaque
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="imagem" class="form-label">Imagem</label>
                                    <input type="file" class="form-control" id="imagem" name="imagem">
                                    <?php if($produto['imagem']): ?>
                                        <img src="../uploads/produtos/<?php echo $produto['imagem']; ?>" 
                                             class="mt-2" width="100">
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="produtos.php" class="btn btn-secondary">Voltar</a>
                                    <button type="submit" class="btn btn-primary">Salvar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>