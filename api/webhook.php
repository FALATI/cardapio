<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Token');

// Tratar requisições OPTIONS (Pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Carregar configurações
require_once '../config/config.php';

// Função para enviar resposta JSON
function sendResponse($success, $data = [], $message = '', $code = 200)
{
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Verificar Token de Autenticação
$headers = getallheaders();
$token = '';

// Tenta pegar do header Authorization: Bearer <token>
if (isset($headers['Authorization'])) {
    if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        $token = $matches[1];
    }
}
// Ou header customizado
elseif (isset($headers['X-API-Token'])) {
    $token = $headers['X-API-Token'];
}
// Ou via GET/POST (menos seguro, mas útil para testes rápidos)
elseif (isset($_REQUEST['token'])) {
    $token = $_REQUEST['token'];
}

// Verificar se o token existe no banco
if (empty($token)) {
    sendResponse(false, [], 'Token de autenticação não fornecido', 401);
}

// Buscar token configurado
$sql = "SELECT valor FROM configuracoes WHERE chave = 'api_token' LIMIT 1";
$result = $conn->query($sql);
$config_token = ($result && $row = $result->fetch_assoc()) ? $row['valor'] : '';

if (empty($config_token) || $token !== $config_token) {
    sendResponse(false, [], 'Token inválido ou não configurado', 403);
}

// Determinar a ação
$action = $_REQUEST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Ler corpo da requisição JSON se houver
$input = json_decode(file_get_contents('php://input'), true) ?? [];
// Mesclar com $_REQUEST
$params = array_merge($_REQUEST, $input);

try {
    switch ($action) {
        case 'test':
            sendResponse(true, ['version' => '1.0.0'], 'Conexão estabelecida com sucesso');
            break;

        case 'get_orders':
            // Filtros
            $status = $params['status'] ?? null;
            $limit = isset($params['limit']) ? (int) $params['limit'] : 20;
            $limit = min($limit, 100); // Max 100 por vez

            $where = [];
            $types = "";
            $binds = [];

            if ($status) {
                $where[] = "status = ?";
                $types .= "s";
                $binds[] = $status;
            }

            $sql = "SELECT id, data_pedido, nome_cliente, total, status, pagamento_tipo 
                    FROM pedidos ";

            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }

            $sql .= " ORDER BY id DESC LIMIT ?";
            $types .= "i";
            $binds[] = $limit;

            $stmt = $conn->prepare($sql);
            if (!empty($binds)) {
                $stmt->bind_param($types, ...$binds);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }

            sendResponse(true, $orders, count($orders) . ' pedidos encontrados');
            break;

        case 'get_order_details':
            $id = $params['id'] ?? null;
            if (!$id) {
                sendResponse(false, [], 'ID do pedido obrigatório', 400);
            }

            // Buscar pedido
            $stmt = $conn->prepare("SELECT * FROM pedidos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $pedido = $stmt->get_result()->fetch_assoc();

            if (!$pedido) {
                sendResponse(false, [], 'Pedido não encontrado', 404);
            }

            // Buscar itens
            $stmt = $conn->prepare("SELECT * FROM itens_pedido WHERE pedido_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $itens_result = $stmt->get_result();

            $itens = [];
            while ($item = $itens_result->fetch_assoc()) {
                $itens[] = $item;
            }

            $pedido['itens'] = $itens;

            sendResponse(true, $pedido, 'Detalhes do pedido recuperados');
            break;

        case 'update_order_status':
            if ($method !== 'POST') {
                sendResponse(false, [], 'Método deve ser POST', 405);
            }

            $id = $params['id'] ?? null;
            $new_status = $params['new_status'] ?? null;

            if (!$id || !$new_status) {
                sendResponse(false, [], 'ID e novo status são obrigatórios', 400);
            }

            $allowed_statuses = ['pendente', 'em_preparo', 'saiu_entrega', 'entregue', 'cancelado']; // Ajuste conforme seu sistema
            // Se não soubermos os status, podemos permitir qualquer string não vazia, 
            // mas idealmente verificamos no código existente. 
            // Vou deixar flexível por enquanto mas alertar no comentário

            $stmt = $conn->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    sendResponse(true, ['id' => $id, 'status' => $new_status], 'Status atualizado com sucesso');
                } else {
                    sendResponse(false, [], 'Pedido não encontrado ou status já era este', 404);
                }
            } else {
                throw new Exception($stmt->error);
            }
            break;

        case 'get_products':
            $limit = isset($params['limit']) ? (int) $params['limit'] : 50;
            $category = $params['categoria_id'] ?? null;

            $sql = "SELECT * FROM produtos WHERE 1=1";
            $binds = [];
            $types = "";

            if ($category) {
                $sql .= " AND categoria_id = ?";
                $types .= "i";
                $binds[] = $category;
            }

            $sql .= " ORDER BY nome ASC LIMIT ?";
            $types .= "i";
            $binds[] = $limit;

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$binds);
            $stmt->execute();
            $result = $stmt->get_result();

            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }

            sendResponse(true, $products, count($products) . ' produtos listados');
            break;

        default:
            sendResponse(false, [], 'Ação inválida ou não especificada (user action=...)', 400);
    }

} catch (Exception $e) {
    sendResponse(false, [], 'Erro interno: ' . $e->getMessage(), 500);
}
?>