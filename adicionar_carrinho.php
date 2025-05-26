<?php
require_once 'config/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER']));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto_id = (int)$_POST['produto_id'];
    $quantidade = (int)$_POST['quantidade'];
    $observacoes = $_POST['observacoes'] ?? '';
    $usuario_id = $_SESSION['usuario_id'];

    // Verificar se já existe no carrinho
    $sql = "SELECT id, quantidade FROM carrinho 
            WHERE usuario_id = ? AND produto_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $usuario_id, $produto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Atualizar quantidade
        $item = $result->fetch_assoc();
        $nova_quantidade = $item['quantidade'] + $quantidade;
        
        $sql = "UPDATE carrinho SET quantidade = ?, observacoes = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $nova_quantidade, $observacoes, $item['id']);
    } else {
        // Inserir novo item
        $sql = "INSERT INTO carrinho (usuario_id, produto_id, quantidade, observacoes) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $usuario_id, $produto_id, $quantidade, $observacoes);
    }

    if ($stmt->execute()) {
        // Após adicionar o item, buscar o total atualizado
        $sql = "SELECT SUM(quantidade) as total FROM carrinho WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $total_itens = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        echo json_encode([
            'success' => true,
            'message' => 'Produto adicionado com sucesso',
            'total_itens' => (int)$total_itens
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}