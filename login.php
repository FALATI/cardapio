<?php
require_once 'config/config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = cleanInput($_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuarios WHERE email = ? AND status = 1 LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];

            if ($usuario['tipo'] == 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit;
        }
    }
    $erro = "Email ou senha inválidos";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #ff6b6b, #ff8e53);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background: none;
            border: none;
            padding-bottom: 0;
        }
        .btn-primary {
            background: #ff6b6b;
            border-color: #ff6b6b;
            padding: 12px;
        }
        .btn-primary:hover {
            background: #ff5252;
            border-color: #ff5252;
        }
        .form-control:focus {
            border-color: #ff6b6b;
            box-shadow: 0 0 0 0.25rem rgba(255,107,107,0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header text-center pt-4">
                        <h2 class="fw-bold mb-0">Login</h2>
                        <p class="text-muted">Bem-vindo ao <?php echo SITE_NAME; ?></p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($erro)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $erro; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="senha" name="senha" required>
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="lembrar">
                                <label class="form-check-label" for="lembrar">Lembrar-me</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
                            </button>
                            <div class="text-center">
                                <a href="#" class="text-decoration-none" onclick="abrirModalCadastro(); return false;">
                                    Não tem uma conta? Cadastre-se
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="index.php" class="text-white text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Voltar para o cardápio
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Cadastro -->
    <div class="modal fade" id="cadastroModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Criar Conta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="cadastroForm" action="cadastrar_cliente.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nome Completo*</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-mail*</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefone/WhatsApp*</label>
                            <input type="tel" class="form-control" id="telefone" name="telefone" 
                                   required placeholder="(00) 00000-0000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Endereço Completo*</label>
                            <textarea class="form-control" name="endereco" rows="2" required
                                      placeholder="Rua, número, bairro, complemento..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha*</label>
                            <input type="password" class="form-control" name="senha" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Criar Conta</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    function abrirModalCadastro() {
        const cadastroModal = new bootstrap.Modal(document.getElementById('cadastroModal'));
        cadastroModal.show();
    }

    document.getElementById('cadastroForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('cadastrar_cliente.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirecionar imediatamente após sucesso
                window.location.replace('index.php');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: data.message,
                    confirmButtonColor: '#ff6b00'
                });
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro ao processar cadastro',
                confirmButtonColor: '#ff6b00'
            });
        });
    });

    // Máscara para telefone
    document.getElementById('telefone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) {
            value = value.substr(0, 11);
        }
        if (value.length >= 11) {
            value = `(${value.substr(0, 2)}) ${value.substr(2, 5)}-${value.substr(7)}`;
        } else if (value.length >= 7) {
            value = `(${value.substr(0, 2)}) ${value.substr(2, 5)}-${value.substr(7)}`;
        } else if (value.length >= 2) {
            value = `(${value.substr(0, 2)}) ${value.substr(2)}`;
        }
        e.target.value = value;
    });
    </script>
</body>
</html>