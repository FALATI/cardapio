<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

header('Content-Type: application/json');

try {
    // Validação dos campos obrigatórios
    if (empty($_POST['nome'])) {
        throw new Exception("O nome do produto é obrigatório");
    }

    if (empty($_POST['preco'])) {
        throw new Exception("O preço do produto é obrigatório");
    }

    if (empty($_POST['categoria_id'])) {
        throw new Exception("A categoria é obrigatória");
    }

    // Limpeza e formatação dos dados
    $nome = cleanInput($_POST['nome']);
    $descricao = cleanInput($_POST['descricao']);
    $preco = str_replace(',', '.', $_POST['preco']); // Converte vírgula para ponto
    $categoria_id = (int)$_POST['categoria_id'];
    $destaque = isset($_POST['destaque']) ? 1 : 0;

    // Validação do preço
    $preco = str_replace(['R$', ' '], '', $preco); // Remove R$ e espaços
    if (!is_numeric($preco)) {
        throw new Exception("Preço inválido");
    }

    // Garante 2 casas decimais
    $preco = number_format((float)$preco, 2, '.', '');

    // Upload da imagem
    $imagem = '';
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        // Validação do tipo de arquivo
        $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($_FILES['imagem']['type'], $tipos_permitidos)) {
            throw new Exception("Tipo de arquivo não permitido. Use apenas JPG ou PNG");
        }

        // Validação do tamanho
        if ($_FILES['imagem']['size'] > 5 * 1024 * 1024) { // 5MB
            throw new Exception("Arquivo muito grande. Máximo permitido: 5MB");
        }

        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $novo_nome = uniqid() . '.' . $ext;
        $diretorio = "../uploads/produtos/";
        
        if (!file_exists($diretorio)) {
            if (!mkdir($diretorio, 0777, true)) {
                throw new Exception("Erro ao criar diretório de upload");
            }
        }
        
        if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $diretorio . $novo_nome)) {
            throw new Exception("Erro ao fazer upload da imagem");
        }
        
        $imagem = $novo_nome;
    }

    // Inserção no banco
    $sql = "INSERT INTO produtos (nome, descricao, preco, categoria_id, destaque, imagem, status) 
            VALUES (?, ?, ?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Erro ao preparar query: " . $conn->error);
    }

    $stmt->bind_param("ssdiis", $nome, $descricao, $preco, $categoria_id, $destaque, $imagem);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar produto: " . $stmt->error);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Produto salvo com sucesso!',
        'produto_id' => $conn->insert_id
    ]);

} catch (Exception $e) {
    // Log do erro para debug
    error_log("Erro ao salvar produto: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}