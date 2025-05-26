<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['status'])) {
        throw new Exception("Status não informado");
    }

    $status = $_POST['status'];
    if ($status !== '0' && $status !== '1') {
        throw new Exception("Status inválido");
    }

    $sql = "UPDATE configuracoes SET valor = ? WHERE chave = 'loja_aberta'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao atualizar status");
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}