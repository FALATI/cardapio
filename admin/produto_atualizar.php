<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['produto_id'])) {
        throw new Exception("ID do produto não informado");
    }

    $produto_id = (int)$_POST['produto_id'];

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
    $preco = str_replace(['R$', ' ', '.'], '', $_POST['preco']);
    $preco = str_replace(',', '.', $preco);
    $categoria_id = (int)$_POST['categoria_id'];
    $destaque = isset($_POST['destaque']) ? 1 : 0;

    // Validação do preço
    if (!is_numeric($preco)) {
        throw new Exception("Preço inválido");
    }

    // Upload da imagem (se houver)
    $imagem = '';
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        // Validação do tipo de arquivo
        $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!in_array($_FILES['imagem']['type'], $tipos_permitidos)) {
            throw new Exception("Tipo de arquivo não permitido. Use apenas JPG ou PNG");
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
        
        // Atualiza com a nova imagem
        $sql = "UPDATE produtos SET nome = ?, descricao = ?, preco = ?, categoria_id = ?, 
                destaque = ?, imagem = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdiisi", $nome, $descricao, $preco, $categoria_id, $destaque, 
                         $novo_nome, $produto_id);
    } else {
        // Atualiza sem mudar a imagem
        $sql = "UPDATE produtos SET nome = ?, descricao = ?, preco = ?, categoria_id = ?, 
                destaque = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdiii", $nome, $descricao, $preco, $categoria_id, $destaque, 
                         $produto_id);
    }

    if (!$stmt->execute()) {
        throw new Exception("Erro ao atualizar produto: " . $stmt->error);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Produto atualizado com sucesso!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}