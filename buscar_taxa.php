<?php

require_once 'config/config.php';

header('Content-Type: application/json');

try {
    $bairro = $_GET['bairro'] ?? '';
    
    if (empty($bairro)) {
        throw new Exception('Bairro não informado');
    }

    // Buscar taxa do bairro na tabela enderecos_entrega
    $sql = "SELECT valor_entrega FROM enderecos_entrega WHERE bairro = ? AND status = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $bairro);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'valor_entrega' => $row['valor_entrega']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Taxa não encontrada para este bairro'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}