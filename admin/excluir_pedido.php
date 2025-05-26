<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

header('Content-Type: application/json');

try {
    // Receber e validar dados
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Dados inválidos: ' . json_last_error_msg());
    }

    $id = $data['id'] ?? null;
    $acao = $data['acao'] ?? 'cancelar';

    if (empty($id)) {
        throw new Exception('ID do pedido não fornecido');
    }

    // Verificar se o pedido existe
    $sql_check = "SELECT id FROM pedidos WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows === 0) {
        throw new Exception('Pedido não encontrado');
    }

    // Iniciar transação
    $conn->begin_transaction();

    try {
        if ($acao === 'excluir') {
            // Primeiro exclui os itens (corrigido nome da tabela)
            $sql = "DELETE FROM pedido_itens WHERE pedido_id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Erro na preparação da query: ' . $conn->error);
            }
            
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao excluir itens: ' . $stmt->error);
            }

            // Depois exclui o pedido
            $sql = "DELETE FROM pedidos WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Erro na preparação da query: ' . $conn->error);
            }
            
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao excluir pedido: ' . $stmt->error);
            }
            
            $mensagem = 'Pedido excluído com sucesso!';
        } else {
            // Apenas cancela o pedido
            $sql = "UPDATE pedidos SET status = 'cancelado', updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception('Erro na preparação da query: ' . $conn->error);
            }
            
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao cancelar pedido: ' . $stmt->error);
            }
            
            $mensagem = 'Pedido cancelado com sucesso!';
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => $mensagem
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Log do erro para debug
    error_log('Erro em excluir_pedido.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar pedido: ' . $e->getMessage()
    ]);
}