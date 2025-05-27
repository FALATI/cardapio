<?php
require_once 'config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('E-mail inválido');
    }

    // Verificar se o email existe
    $sql = "SELECT id, nome FROM usuarios WHERE email = ? AND status = 1 LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('E-mail não encontrado');
    }

    $usuario = $result->fetch_assoc();
    
    // Início da transação
    $conn->begin_transaction();
    
    try {
        // Invalidar tokens anteriores
        $sql = "UPDATE recuperacao_senha SET usado = 1 WHERE usuario_id = ? AND usado = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario['id']);
        $stmt->execute();
        
        // Gerar novo token
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Salvar token
        $sql = "INSERT INTO recuperacao_senha (usuario_id, token, expira) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $usuario['id'], $token, $expira);
        
        if (!$stmt->execute()) {
            throw new Exception('Erro ao gerar token');
        }
        
        // Confirmar transação
        $conn->commit();
        
        // Log para debug
        error_log("Token gerado: " . $token);
        error_log("Para usuário ID: " . $usuario['id']);
        
        // Preparar e enviar email
        $resetLink = SITE_URL . "/cardapio/redefinir_senha.php?token=" . $token;

        // Debug para verificar a URL gerada
        error_log("Link de recuperação gerado: " . $resetLink);

        $mensagem = "
        <html>
        <head>
            <title>Recuperação de Senha</title>
        </head>
        <body>
            <h2>Olá {$usuario['nome']},</h2>
            <p>Você solicitou a recuperação de senha. Clique no link abaixo para criar uma nova senha:</p>
            <p><a href='{$resetLink}'>Redefinir minha senha</a></p>
            <p>Se você não solicitou esta recuperação, ignore este e-mail.</p>
            <p>Este link expira em 24 horas.</p>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . SITE_NAME . " <noreply@seudominio.com>\r\n";

        if (!mail($email, "Recuperação de Senha - " . SITE_NAME, $mensagem, $headers)) {
            throw new Exception('Erro ao enviar e-mail');
        }

        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Erro na recuperação de senha: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}