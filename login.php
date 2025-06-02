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
    <!-- Mudar no título da página -->
    <title>Login - <?php echo $site_titulo; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2c3e50, #3498db);
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
            background: #3498db;
            border-color: #3498db;
            padding: 12px;
        }
        .btn-primary:hover {
            background: #2980b9;
            border-color: #2980b9;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.25rem rgba(52,152,219,0.25);
        }
        
        /* Atualizar apenas a cor dos links */
        .text-center a {
            color: #3498db; /* Nova cor para links dentro do card */
            transition: color 0.3s ease;
        }
        .text-center a:hover {
            color: #2980b9; /* Nova cor para hover */
        }
        
        /* Link de voltar para o cardápio */
        .text-white {
            color: #ecf0f1 !important; /* Manter branco para o link de voltar ao cardápio */
        }
        .text-white:hover {
            color: #bdc3c7 !important;
        }
        
        /* Manter o estilo do modal header */
        .modal-header {
            background-color: #3498db;
            color: white;
        }
        .modal-header .btn-close {
            color: white;
        }
        
        /* Ajustar as cores dos botões nas modais SweetAlert */
        .swal2-confirm {
            background-color: #3498db !important;
        }
        .swal2-confirm:hover {
            background-color: #2980b9 !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <!-- Mudar no cabeçalho do card de login -->
                    <div class="card-header text-center pt-4">
                        <h2 class="fw-bold mb-0">Login</h2>
                        <p class="text-muted">Bem-vindo ao <?php echo $site_titulo; ?></p>
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
                                <a href="#" class="text-decoration-none mb-2 d-block" onclick="abrirModalCadastro(); return false;">
                                    Não tem uma conta? Cadastre-se
                                </a>
                                <a href="#" class="text-decoration-none" onclick="abrirModalRecuperacao(); return false;">
                                    Esqueceu sua senha?
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

    <!-- Modal de Recuperação de Senha -->
    <div class="modal fade" id="recuperacaoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Recuperar Senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="recuperacaoForm" action="recuperar_senha.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">E-mail cadastrado</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Enviar Link de Recuperação</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Cadastro -->
    <div class="modal fade" id="cadastroModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Criar Conta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="cadastroForm" action="cadastrar_cliente.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome Completo*</label>
                                <input type="text" class="form-control" name="nome" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">E-mail*</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefone/WhatsApp*</label>
                                <input type="tel" class="form-control" id="telefone" name="telefone" 
                                       required placeholder="(00) 00000-0000">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bairro*</label>
                                <input type="text" class="form-control" name="bairro" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Endereço Completo*</label>
                            <textarea class="form-control" name="endereco" rows="2" required
                                      placeholder="Rua, número, complemento..."></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Senha*</label>
                                <input type="password" class="form-control" name="senha" required minlength="6">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirmar Senha*</label>
                                <input type="password" class="form-control" name="confirmar_senha" required minlength="6">
                            </div>
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

    function abrirModalRecuperacao() {
        const recuperacaoModal = new bootstrap.Modal(document.getElementById('recuperacaoModal'));
        recuperacaoModal.show();
    }

    document.getElementById('cadastroForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const senha = this.querySelector('[name="senha"]').value;
        const confirmarSenha = this.querySelector('[name="confirmar_senha"]').value;
        
        if (senha !== confirmarSenha) {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'As senhas não coincidem',
                confirmButtonColor: '#3498db' // Cor atualizada
            });
            return;
        }
        
        const formData = new FormData(this);
        
        fetch('cadastrar_cliente.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: 'Conta criada com sucesso!',
                    confirmButtonColor: '#3498db' // Cor atualizada
                }).then(() => {
                    window.location.replace('index.php');
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: data.message,
                    confirmButtonColor: '#3498db' // Cor atualizada
                });
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro ao processar cadastro',
                confirmButtonColor: '#3498db' // Cor atualizada
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

    document.getElementById('recuperacaoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('recuperar_senha.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'E-mail Enviado',
                    text: 'Verifique seu e-mail para recuperar sua senha',
                    confirmButtonColor: '#3498db' // Cor atualizada
                });
                bootstrap.Modal.getInstance(document.getElementById('recuperacaoModal')).hide();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: data.message,
                    confirmButtonColor: '#3498db' // Cor atualizada
                });
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro ao processar solicitação',
                confirmButtonColor: '#3498db' // Cor atualizada
            });
        });
    });
    </script>
</body>
</html>