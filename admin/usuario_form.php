<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

$usuario = [
    'id' => '',
    'nome' => '',
    'email' => '',
    'status' => 1
];

// Se for edição, buscar dados do usuário
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM usuarios WHERE id = ? AND tipo = 'cliente'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = cleanInput($_POST['nome']);
    $email = cleanInput($_POST['email']);
    $senha = !empty($_POST['senha']) ? password_hash($_POST['senha'], PASSWORD_DEFAULT) : '';
    $status = isset($_POST['status']) ? 1 : 0;

    if (empty($usuario['id'])) {
        // Inserir
        $sql = "INSERT INTO usuarios (nome, email, senha, tipo, status) VALUES (?, ?, ?, 'cliente', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $nome, $email, $senha, $status);
    } else {
        // Atualizar
        if (!empty($senha)) {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, senha = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssii", $nome, $email, $senha, $status, $usuario['id']);
        } else {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $nome, $email, $status, $usuario['id']);
        }
    }

    if ($stmt->execute()) {
        header('Location: usuarios.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo empty($usuario['id']) ? 'Novo' : 'Editar'; ?> Usuário - <?php echo SITE_NAME; ?></title>
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
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <?php echo empty($usuario['id']) ? 'Novo' : 'Editar'; ?> Usuário
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome</label>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                           value="<?php echo $usuario['nome']; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo $usuario['email']; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="senha" class="form-label">
                                        <?php echo empty($usuario['id']) ? 'Senha' : 'Nova Senha (deixe em branco para manter a atual)'; ?>
                                    </label>
                                    <input type="password" class="form-control" id="senha" name="senha" 
                                           <?php echo empty($usuario['id']) ? 'required' : ''; ?>>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="status" 
                                               name="status" <?php echo $usuario['status'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="status">
                                            Usuário ativo
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="usuarios.php" class="btn btn-secondary">Voltar</a>
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