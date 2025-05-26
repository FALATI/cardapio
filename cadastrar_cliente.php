<?php
require_once 'config/config.php';

// Limpar buffer e definir cabeçalho
ob_end_clean();
header('Content-Type: application/json');

try {
    // Validar campos obrigatórios
    if (empty($_POST['nome']) || empty($_POST['email']) || empty($_POST['senha']) || 
        empty($_POST['telefone']) || empty($_POST['endereco']) || empty($_POST['bairro'])) {
        throw new Exception('Todos os campos são obrigatórios');
    }

    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $telefone = trim($_POST['telefone']);
    $endereco = trim($_POST['endereco']);
    $bairro = trim($_POST['bairro']);
    $tipo = 'cliente';
    $status = 1;

    // Verificar email duplicado
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Este email já está cadastrado');
    }

    // Inserir usuário
    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, telefone, endereco, bairro, tipo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssi", $nome, $email, $senha, $telefone, $endereco, $bairro, $tipo, $status);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao cadastrar: ' . $stmt->error);
    }

    // Login automático
    $_SESSION['usuario_id'] = $stmt->insert_id;
    $_SESSION['usuario_nome'] = $nome;
    $_SESSION['usuario_tipo'] = $tipo;

    die(json_encode([
        'success' => true,
        'message' => 'Cadastro realizado com sucesso!'
    ]));

} catch (Exception $e) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]));
}
?>

<div class="mb-3">
    <label class="form-label">Endereço*</label>
    <input type="text" class="form-control" name="endereco" required 
           placeholder="Rua / Avenida, número">
</div>
<div class="mb-3">
    <label class="form-label">Bairro*</label>
    <select class="form-select" name="bairro" required>
        <option value="">Selecione o bairro</option>
        <?php
        $sql_bairros = "SELECT bairro FROM enderecos_entrega WHERE status = 1 ORDER BY bairro";
        $result_bairros = $conn->query($sql_bairros);
        while($bairro = $result_bairros->fetch_assoc()):
        ?>
            <option value="<?php echo htmlspecialchars($bairro['bairro']); ?>">
                <?php echo htmlspecialchars($bairro['bairro']); ?>
            </option>
        <?php endwhile; ?>
    </select>
</div>
<div class="mb-3">
    <label class="form-label">Complemento</label>
    <input type="text" class="form-control" name="complemento" 
           placeholder="Apartamento, bloco, referência...">
</div>