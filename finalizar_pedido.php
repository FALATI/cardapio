<?php
require_once 'config/config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Buscar dados do usuário logado
$usuario_id = $_SESSION['usuario_id'];
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Erro ao preparar query: ' . $conn->error);
}

$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$dados_usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Debug para verificar os dados
echo "<!-- Dados do usuário: "; 
print_r($dados_usuario);
echo " -->";

// Buscar formas de pagamento
$sql_pagamento = "SELECT * FROM formas_pagamento WHERE status = 1";
$result_pagamento = $conn->query($sql_pagamento);

if (!$result_pagamento) {
    error_log("Erro ao buscar formas de pagamento: " . $conn->error);
    $formas_pagamento = [];
} else {
    $formas_pagamento = $result_pagamento->fetch_all(MYSQLI_ASSOC);
}

// Buscar formas de entrega
$sql_entrega = "SELECT id, nome, status FROM formas_entrega WHERE status = 1";
$result_entrega = $conn->query($sql_entrega);

if (!$result_entrega) {
    error_log("Erro ao buscar formas de entrega: " . $conn->error);
    $formas_entrega = [];
} else {
    $formas_entrega = $result_entrega->fetch_all(MYSQLI_ASSOC);
}

$mensagem = '';
$erro = '';
$items = [];
$total = 0;

// Buscar itens do carrinho antes do POST
$sql = "SELECT c.*, p.nome, p.preco, p.imagem 
        FROM carrinho c 
        JOIN produtos p ON c.produto_id = p.id 
        WHERE c.usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calcular total inicial
foreach ($items as $item) {
    $total += $item['quantidade'] * $item['preco'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validações básicas sem mensagens de erro
        if (empty($_POST['forma_pagamento']) || empty($_POST['forma_entrega']) ||
            empty($_POST['endereco']) || empty($_POST['telefone'])) {
            throw new Exception();
        }

        // Remove validação específica do formato do telefone
        $telefone = trim($_POST['telefone']);
        
        $conn->begin_transaction();

        // Preparar dados do pedido
        $usuario_id = $_SESSION['usuario_id'];
        $total_pedido = $total;
        $observacoes = $_POST['observacoes'] ?? '';
        $endereco = trim($_POST['endereco']);
        $forma_pagamento_id = (int)$_POST['forma_pagamento'];
        $forma_entrega_id = (int)$_POST['forma_entrega'];
        $taxa_entrega = 0;

        // Buscar bairros e taxas
        $sql_bairros = "SELECT valor FROM configuracoes WHERE chave = 'enderecos_entrega'";
        $result_bairros = $conn->query($sql_bairros);
        $bairros_str = '';
        if ($result_bairros && $row = $result_bairros->fetch_assoc()) {
            $bairros_str = $row['valor'];
        }

        // Buscar taxa de entrega do bairro
        $sql_taxas = "SELECT valor FROM configuracoes WHERE chave = 'valor_entrega'";
        $result_taxas = $conn->query($sql_taxas);
        if ($result_taxas && $row = $result_taxas->fetch_assoc()) {
            $taxas = array_map('trim', explode("\n", $row['valor']));
            $bairros = array_map('trim', explode("\n", $bairros_str));
            $bairro_index = array_search($_POST['bairro'], $bairros);
            if ($bairro_index !== false) {
                $taxa_entrega = (float)$taxas[$bairro_index];
            }
        }

        // Atualizar total com taxa
        $total_pedido += $taxa_entrega;

        // Adicionar antes da query
        error_log("Valores para inserção:");
        error_log(print_r([
            'usuario_id' => $usuario_id,
            'total_pedido' => $total_pedido,
            'observacoes' => $observacoes,
            'endereco' => $endereco,
            'telefone' => $telefone,
            'forma_pagamento_id' => $forma_pagamento_id,
            'forma_entrega_id' => $forma_entrega_id,
            'taxa_entrega' => $taxa_entrega
        ], true));

        // Modificar a query de inserção para remover o campo bairro
        try {
            $sql = "INSERT INTO pedidos (
                usuario_id, 
                total, 
                observacoes, 
                endereco, 
                telefone, 
                forma_pagamento_id, 
                forma_entrega_id, 
                taxa_entrega, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendente')";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Erro na preparação: " . $conn->error);
            }

            // Remover bairro do bind_param
            $stmt->bind_param("idsssiii", 
                $usuario_id,
                $total_pedido,
                $observacoes,
                $endereco,
                $telefone,
                $forma_pagamento_id,
                $forma_entrega_id,
                $taxa_entrega
            );

            if (!$stmt->execute()) {
                throw new Exception("Erro na execução: " . $stmt->error);
            }

            $pedido_id = $conn->insert_id;

        } catch (Exception $e) {
            error_log("Erro na inserção: " . $e->getMessage());
            throw $e;
        }

        // Inserir itens do pedido
        $sql = "INSERT INTO pedido_itens (
            pedido_id, 
            produto_id, 
            quantidade, 
            preco_unitario
        ) VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        foreach ($items as $item) {
            $stmt->bind_param("iiid",
                $pedido_id,
                $item['produto_id'],
                $item['quantidade'],
                $item['preco']
            );
            if (!$stmt->execute()) {
                throw new Exception("Erro ao salvar item do pedido: " . $stmt->error);
            }
        }

        // Limpar carrinho
        $sql = "DELETE FROM carrinho WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();

        $conn->commit();
        $_SESSION['last_order_id'] = $pedido_id;
        $mensagem = "Pedido realizado com sucesso!";
        $items = [];
        $total = 0;

    } catch (Exception $e) {
        $conn->rollback();
        // Log do erro para debug, mas não mostra ao usuário
        error_log("Erro ao processar pedido: " . $e->getMessage());
        header('Location: finalizar_pedido.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Pedido - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bs-primary: #3491D0;
            --bs-primary-rgb: 52, 145, 208;
            --bs-primary-hover: #2C475D;
        }

        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .top-bar {
            background: linear-gradient(135deg, #2C475D 0%, #3491D0 100%);
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .top-bar h4 {
            color: white;
            margin: 0;
            font-weight: 500;
        }

        .card, 
        .checkout-card {
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .list-group-item {
            border: none;
            border-bottom: 1px solid #f0f0f0;
            padding: 1rem 0;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .miniatura {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .btn-primary {
            background: var(--bs-primary);
            border-color: var(--bs-primary);
            padding: 0.5rem 1.5rem;
        }

        .btn-primary:hover,
        .btn-primary:active {
            background: var(--bs-primary-hover) !important;
            border-color: var(--bs-primary-hover) !important;
        }

        .btn-outline-primary {
            color: var(--bs-primary);
            border-color: var(--bs-primary);
            padding: 0.5rem 1.5rem;
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:active {
            background: var(--bs-primary) !important;
            border-color: var(--bs-primary) !important;
            color: white !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.2rem rgba(52, 145, 208, 0.15);
        }

        .form-label {
            color: #495057;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .text-primary {
            color: var(--bs-primary) !important;
        }

        h5 {
            color: #2C475D;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .text-muted {
            color: #6c757d !important;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="container">
            <h4 class="mb-0"><i class="bi bi-cart-check me-2"></i>Finalizar Pedido</h4>
        </div>
    </div>

    <div class="container pb-4">
        <?php if ($erro): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo $erro; ?>
            </div>
        <?php endif; ?>

        <?php if ($mensagem): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="checkout-card text-center">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        <h4 class="mt-3"><?php echo $mensagem; ?></h4>
                        <p class="text-muted">Seu pedido foi realizado com sucesso!</p>
                        <p class="text-muted">Em breve você receberá mais informações.</p>
                        <div class="mt-4">
                            <a href="meus_pedidos.php" class="btn btn-primary me-2">
                                <i class="bi bi-list-ul me-2"></i>Meus Pedidos
                            </a>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="bi bi-house me-2"></i>Voltar ao Cardápio
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif (empty($items)): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="checkout-card text-center">
                        <i class="bi bi-cart-x text-muted" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">Carrinho Vazio</h4>
                        <p class="text-muted">Adicione alguns itens ao seu carrinho primeiro.</p>
                        <a href="index.php" class="btn btn-primary mt-3">
                            <i class="bi bi-house me-2"></i>Voltar ao Cardápio
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Resumo do Pedido -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card mb-4 p-4">
                        <h5 class="mb-4">Itens do Pedido</h5>
                        <?php foreach ($items as $item): 
                            $subtotal = $item['quantidade'] * $item['preco'];
                        ?>
                            <div class="list-group-item py-3">
                                <div class="d-flex align-items-center">
                                    <?php if ($item['imagem']): ?>
                                        <img src="uploads/produtos/<?php echo $item['imagem']; ?>" class="miniatura me-3">
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-0"><?php echo $item['nome']; ?></h6>
                                                <small class="text-muted"><?php echo $item['quantidade']; ?>x <?php echo formatPrice($item['preco']); ?></small>
                                            </div>
                                            <strong><?php echo formatPrice($subtotal); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Formulário de Finalização -->
                    <div class="checkout-card">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Forma de Entrega</label>
                                    <select class="form-select" name="forma_entrega" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($formas_entrega as $entrega): ?>
                                            <option value="<?php echo $entrega['id']; ?>">
                                                <?php echo $entrega['nome']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Forma de Pagamento</label>
                                    <select class="form-select" name="forma_pagamento" required>
                                        <option value="">Selecione...</option>
                                        <?php foreach ($formas_pagamento as $pagamento): ?>
                                            <option value="<?php echo $pagamento['id']; ?>">
                                                <?php echo $pagamento['nome']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Endereço *</label>
                                    <input type="text" name="endereco" class="form-control" 
                                           value="<?php echo htmlspecialchars($dados_usuario['endereco']); ?>"
                                           placeholder="Rua, número, complemento..." required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Bairro</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo htmlspecialchars($dados_usuario['bairro']); ?>"
                                           readonly>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Telefone *</label>
                                    <input type="tel" name="telefone" class="form-control" 
                                           value="<?php echo htmlspecialchars($dados_usuario['telefone']); ?>"
                                           placeholder="(00) 00000-0000" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Observações (opcional)</label>
                                    <textarea name="observacoes" class="form-control" rows="2" 
                                              placeholder="Instruções especiais para entrega..."></textarea>
                                </div>
                            </div>

                            <!-- Total e Botões -->
                            <div class="mt-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal</span>
                                    <span><?php echo formatPrice($total); ?></span>
                                </div>
                                <div class="d-none justify-content-between mb-2" id="taxa-row">
                                    <span>Taxa de Entrega</span>
                                    <span id="taxa-entrega"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <h5 class="mb-0">Total</h5>
                                    <h5 class="mb-0 text-primary" id="total-final"><?php echo formatPrice($total); ?></h5>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <a href="index.php" class="btn btn-outline-primary">
                                        <i class="bi bi-house me-2"></i>Voltar ao Cardápio
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check2-circle me-2"></i>Confirmar Pedido
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.querySelector('select[name="forma_entrega"]').addEventListener('change', function() {
            const formaEntrega = this.options[this.selectedIndex].text.toLowerCase();
            const bairroCliente = '<?php echo htmlspecialchars($dados_usuario['bairro']); ?>';
            const subtotal = <?php echo $total; ?>;
            
            if (formaEntrega.includes('delivery')) {
                // Buscar taxa do bairro via AJAX
                fetch('buscar_taxa.php?bairro=' + encodeURIComponent(bairroCliente))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const taxa = parseFloat(data.valor_entrega);
                        atualizarTotal(subtotal, taxa);
                    } else {
                        console.error('Erro:', data.message);
                        alert('Não foi possível calcular a taxa de entrega para seu bairro');
                        atualizarTotal(subtotal, 0);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao calcular taxa de entrega');
                    atualizarTotal(subtotal, 0);
                });
            } else {
                // Se não for delivery, não cobra taxa
                atualizarTotal(subtotal, 0);
            }
        });

        function atualizarTotal(subtotal, taxa) {
            const total = subtotal + taxa;
            
            // Atualizar total
            document.getElementById('total-final').textContent = formatPrice(total);
            
            // Atualizar linha da taxa
            const taxaRow = document.getElementById('taxa-row');
            if (taxa > 0) {
                document.getElementById('taxa-entrega').textContent = formatPrice(taxa);
                taxaRow.classList.remove('d-none');
                taxaRow.classList.add('d-flex');
            } else {
                taxaRow.classList.remove('d-flex');
                taxaRow.classList.add('d-none');
            }
        }

        function formatPrice(value) {
            return 'R$ ' + value.toFixed(2).replace('.', ',');
        }

        // Máscara para o campo de telefone
        document.querySelector('input[name="telefone"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                value = value.replace(/(\d)(\d{4})$/, '$1-$2');
                e.target.value = value;
            }
        });
    </script>
</body>
</html>