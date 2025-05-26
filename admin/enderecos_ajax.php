<?php
require_once '../config/config.php';
require_once 'verificar_admin.php';

header('Content-Type: application/json');

// Função para log
function logError($message) {
    error_log("[Enderecos Ajax] " . $message);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add':
                // Validar dados
                $bairro = trim($_POST['bairro'] ?? '');
                $valor = str_replace(',', '.', $_POST['valor'] ?? '0');
                
                if (empty($bairro)) {
                    throw new Exception("Bairro é obrigatório");
                }
                
                if (!is_numeric($valor)) {
                    throw new Exception("Valor inválido");
                }
                
                // Log dos dados recebidos
                logError("Dados recebidos - Bairro: $bairro, Valor: $valor");
                
                // Inserir no banco
                $stmt = $conn->prepare("INSERT INTO enderecos_entrega (bairro, valor_entrega) VALUES (?, ?)");
                if (!$stmt) {
                    throw new Exception("Erro ao preparar query: " . $conn->error);
                }
                
                $stmt->bind_param("sd", $bairro, $valor);
                
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao salvar: " . $stmt->error);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Endereço adicionado com sucesso',
                    'id' => $conn->insert_id
                ]);
                break;
                
            case 'toggle_status':
                $id = (int)$_POST['id'];
                $status = (int)$_POST['status'];
                
                $stmt = $conn->prepare("UPDATE enderecos_entrega SET status = ? WHERE id = ?");
                if (!$stmt) {
                    throw new Exception("Erro ao preparar query de status");
                }
                
                $stmt->bind_param("ii", $status, $id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao atualizar status");
                }
                
                echo json_encode(['success' => true]);
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                $stmt = $conn->prepare("DELETE FROM enderecos_entrega WHERE id = ?");
                if (!$stmt) {
                    throw new Exception("Erro ao preparar query de delete");
                }
                
                $stmt->bind_param("i", $id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao excluir endereço");
                }
                
                echo json_encode(['success' => true]);
                break;
                
            default:
                throw new Exception("Ação inválida");
        }
        
    } catch (Exception $e) {
        logError($e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}