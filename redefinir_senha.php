<?php
require_once 'config/config.php';

$erro = '';
$sucesso = '';
$token = $_GET['token'] ?? '';

// Verificar se o token foi fornecido
if (empty($token)) {
    $erro = "Token não fornecido";
} else {
    // Verificar token no banco
    $sql = "SELECT r.*, u.email, u.id as usuario_id 
            FROM recuperacao_senha r 
            INNER JOIN usuarios u ON u.id = r.usuario_id 
            WHERE r.token = ? 
            AND r.usado = 0 
            AND r.expira > NOW()";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Erro ao preparar query: " . $conn->error);
        $erro = "Erro ao processar solicitação";
    } else {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $erro = "Link inválido ou expirado. Solicite um novo link de recuperação.";
        }
    }
}

// Processar alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($erro)) {
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if (strlen($senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem";
    } else {
        // Buscar dados do token novamente
        $stmt->execute();
        $dados = $stmt->get_result()->fetch_assoc();
        
        if ($dados) {
            try {
                // Iniciar transação
                $conn->begin_transaction();

                // Atualizar senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $sql_update = "UPDATE usuarios SET senha = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("si", $senha_hash, $dados['usuario_id']);
                
                if (!$stmt_update->execute()) {
                    throw new Exception("Erro ao atualizar senha");
                }

                // Marcar token como usado
                $sql_token = "UPDATE recuperacao_senha SET usado = 1 WHERE token = ?";
                $stmt_token = $conn->prepare($sql_token);
                $stmt_token->bind_param("s", $token);
                
                if (!$stmt_token->execute()) {
                    throw new Exception("Erro ao atualizar token");
                }

                // Confirmar transação
                $conn->commit();
                
                $sucesso = "Senha alterada com sucesso! Você já pode fazer login com sua nova senha.";
                
            } catch (Exception $e) {
                // Reverter alterações em caso de erro
                $conn->rollback();
                error_log("Erro na redefinição de senha: " . $e->getMessage());
                $erro = "Erro ao alterar senha. Tente novamente.";
            }
        } else {
            $erro = "Link inválido ou expirado. Solicite um novo link de recuperação.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .btn-primary {
            background: #ff6b6b;
            border-color: #ff6b6b;
        }
        .btn-primary:hover {
            background: #ff5252;
            border-color: #ff5252;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body p-4">
                        <h3 class="text-center mb-4">Redefinir Senha</h3>
                        
                        <?php if (!empty($erro)): ?>
                            <div class="alert alert-danger">
                                <?php echo $erro; ?>
                                <div class="mt-3">
                                    <a href="login.php" class="btn btn-primary">Voltar para Login</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($sucesso)): ?>
                            <div class="alert alert-success">
                                <?php echo $sucesso; ?>
                                <div class="mt-3">
                                    <a href="login.php" class="btn btn-primary">Fazer Login</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($erro) && empty($sucesso)): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Nova Senha</label>
                                    <input type="password" class="form-control" name="senha" 
                                           required minlength="6">
                                    <div class="form-text">Mínimo de 6 caracteres</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" name="confirmar_senha" 
                                           required minlength="6">
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    Alterar Senha
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>