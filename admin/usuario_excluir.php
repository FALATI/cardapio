<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

header('Content-Type: application/json');

try {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id <= 0) {
        throw new Exception('ID inválido');
    }

    // Inicia transação
    $conn->begin_transaction();

    try {
        // Verifica se não é admin
        $stmt = $conn->prepare("SELECT tipo FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();

        if (!$usuario) {
            throw new Exception('Usuário não encontrado');
        }

        if ($usuario['tipo'] === 'admin') {
            throw new Exception('Não é possível excluir um administrador');
        }

        // Remove registros relacionados em ordem
        $queries = [
            "DELETE FROM pedido_itens WHERE pedido_id IN (SELECT id FROM pedidos WHERE usuario_id = ?)",
            "DELETE FROM pedidos WHERE usuario_id = ?",
            "DELETE FROM carrinho WHERE usuario_id = ?",
            "DELETE FROM usuarios WHERE id = ? AND tipo != 'admin'"
        ];

        foreach ($queries as $sql) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar query: " . $stmt->error);
            }
        }

        // Commit da transação
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Usuário excluído com sucesso!'
        ]);

    } catch (Exception $e) {
        // Rollback em caso de erro
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}