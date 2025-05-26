<?php

require_once 'config/config.php';

$erro = '';
$sucesso = '';
$token = $_GET['token'] ?? '';

// Verificar token
if (!empty($token)) {
    $sql = "SELECT r.*, u.email 
            FROM recuperacao_senha r 
            JOIN usuarios u ON u.id = r.usuario_id 
            WHERE r.token = ? AND r.usado = 0 AND r.expira > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $erro = "Link inválido ou expirado";
    }
}

// Processar nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($token)) {
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem";
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $dados = $result->fetch_assoc();
        
        if ($dados) {
            // Atualizar senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET senha = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $senha_hash, $dados['usuario_id']);
            
            if ($stmt->execute()) {
                // Marcar token como usado
                $sql = "UPDATE recuperacao_senha SET usado = 1 WHERE token = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                $sucesso = "Senha alterada com sucesso!";
            } else {
                $erro = "Erro ao alterar senha";
            }
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
        body { background: linear-gradient(135deg, #ff6b6b, #ff8e53); min-height: 100vh; display: flex; align-items: center; }
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
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
                            <div class="alert alert-danger"><?php echo $erro; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($sucesso)): ?>
                            <div class="alert alert-success">
                                <?php echo $sucesso; ?>
                                <div class="mt-3">
                                    <a href="login.php" class="btn btn-primary">Fazer Login</a>
                                </div>
                            </div>
                        <?php elseif (empty($erro)): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Nova Senha</label>
                                    <input type="password" class="form-control" name="senha" required minlength="6">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" name="confirmar_senha" required minlength="6">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Alterar Senha</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>