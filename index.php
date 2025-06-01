<?php
require_once 'config/config.php';

// Buscar configurações com valores padrão
$configs = [
    'site_titulo' => SITE_NAME,
    'site_subtitulo' => 'Pedidos online com entrega rápida e segura!',
    'horarios' => '{}',
    'logo' => '',
    'enderecos_entrega' => '',
    'valor_entrega' => '',
    'facebook_url' => '',
    'instagram_url' => '',
    'whatsapp_url' => '',
    'favicon' => ''
];

$sql = "SELECT chave, valor FROM configuracoes";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['valor'])) {
            $configs[$row['chave']] = $row['valor'];
        }
    }
}

// Buscar total de itens no carrinho
$total_carrinho = 0;
if (isset($_SESSION['usuario_id'])) {
    $sql = "SELECT SUM(quantidade) as total FROM carrinho WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    $total_carrinho = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

// Verificar se está aberto baseado no dia e horário atual
$hoje = date('w') + 1; // 1 (Dom) a 7 (Sáb)
$agora = date('H:i:s');

$horarios = json_decode($configs['horarios'] ?? '{}', true);
$horario_hoje = $horarios[$hoje] ?? null;

$loja_aberta = false;
if ($horario_hoje && isset($horario_hoje['status']) && $horario_hoje['status'] == 1) {
    $loja_aberta = $agora >= $horario_hoje['abertura'] && 
                   $agora <= $horario_hoje['fechamento'];
}

// Configurações de paginação
$produtos_por_pagina = 3;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$categoria_atual = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;

// Buscar categorias ativas que possuem produtos
$sql_categorias = "SELECT c.* 
                  FROM categorias c
                  INNER JOIN produtos p ON c.id = p.categoria_id 
                  WHERE c.status = 1 AND p.status = 1
                  GROUP BY c.id 
                  ORDER BY c.nome";
$result_categorias = $conn->query($sql_categorias);

// Paginação dos destaques
$total_destaques = $conn->query("SELECT COUNT(*) as total FROM produtos WHERE destaque = 1 AND status = 1")->fetch_assoc()['total'];
$total_paginas_destaques = ceil($total_destaques / $produtos_por_pagina);
$offset_destaques = ($pagina_atual - 1) * $produtos_por_pagina;

// Buscar produtos em destaque paginados
$sql_destaques = "SELECT p.*, c.nome as categoria_nome 
                  FROM produtos p 
                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                  WHERE p.destaque = 1 AND p.status = 1 
                  ORDER BY p.nome 
                  LIMIT $produtos_por_pagina OFFSET $offset_destaques";
$result_destaques = $conn->query($sql_destaques);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $configs['site_titulo'] ?? SITE_NAME; ?></title>
    
    <!-- Favicon -->
    <?php if (!empty($configs['favicon'])): ?>
        <link rel="icon" type="image/<?php echo pathinfo($configs['favicon'], PATHINFO_EXTENSION); ?>" 
              href="uploads/favicon/<?php echo $configs['favicon']; ?>">
    <?php endif; ?>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Tema Bootstrap (opcional) -->
    <link href="https://bootswatch.com/5/lumen/bootstrap.min.css" rel="stylesheet">
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="public/css/style.css?v=<?php echo time(); ?>">
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
</head>
<body>
    <!-- Barra de Status -->
    <div class="status-bar">
        <div class="container">
            <!-- Logo -->
            <div class="status-bar-brand mb-3">
                <?php if (!empty($configs['logo'])): ?>
                    <img src="uploads/logo/<?php echo $configs['logo']; ?>" 
                         alt="<?php echo $configs['site_titulo'] ?? SITE_NAME; ?>" 
                         class="logo-img">
                <?php else: ?>
                    <h4 class="text-white mb-0"><?php echo $configs['site_titulo'] ?? SITE_NAME; ?></h4>
                <?php endif; ?>
            </div>
            
            <!-- Status e Botões -->
            <div class="status-bar-items">
                <!-- Status da Loja -->
                <div class="status-indicator <?php echo $loja_aberta ? 'status-open' : 'status-closed'; ?>">
                    <i class="bi <?php echo $loja_aberta ? 'bi-shop' : 'bi-shop-window'; ?> me-2"></i>
                    <?php 
                    if ($loja_aberta) {
                        echo 'Aberto até ' . substr($horario_hoje['fechamento'], 0, 5);
                    } else {
                        if ($horario_hoje && $horario_hoje['status'] == 1) {
                            if ($agora < $horario_hoje['abertura']) {
                                echo 'Abrimos às ' . substr($horario_hoje['abertura'], 0, 5);
                            } else {
                                echo 'Fechado';
                            }
                        } else {
                            echo 'Fechado Hoje';
                        }
                    }
                    ?>
                </div>

                <!-- Botões de informação -->
                <div class="nav-buttons">
                    <button type="button" class="info-button" data-bs-toggle="modal" data-bs-target="#horariosModal">
                        <i class="bi bi-clock me-2"></i>
                        Horários
                    </button>

                    <button type="button" class="info-button" data-bs-toggle="modal" data-bs-target="#enderecosModal">
                        <i class="bi bi-geo-alt me-2"></i>
                        Onde Entregamos
                    </button>
                </div>

                <!-- Botões de login e carrinho -->
                <div class="nav-buttons">
                    <?php if (!isset($_SESSION['usuario_id'])): ?>
                        <a href="login.php" class="info-button">
                            <i class="bi bi-person me-2"></i>
                            Login
                        </a>
                    <?php else: ?>
                        <a href="meus_pedidos.php" class="info-button">
                            <i class="bi bi-person me-2"></i>
                            Meus Pedidos
                        </a>
                    <?php endif; ?>

                    <button type="button" class="info-button" data-bs-toggle="modal" data-bs-target="#cartModal">
                        <i class="bi bi-cart3 me-2"></i>
                        Carrinho
                        <?php if ($total_carrinho > 0): ?>
                            <span class="cart-count"><?php echo $total_carrinho; ?></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Horários -->
    <div class="modal fade" id="horariosModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="horariosModalLabel">
                        <i class="bi bi-clock text-primary me-2"></i>
                        Horário de Funcionamento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <tbody>
                                <?php
                                $dias = [
                                    1 => 'Domingo',
                                    2 => 'Segunda-feira',
                                    3 => 'Terça-feira',
                                    4 => 'Quarta-feira',
                                    5 => 'Quinta-feira',
                                    6 => 'Sexta-feira',
                                    7 => 'Sábado'
                                ];
                                $horarios = json_decode($configs['horarios'] ?? '{}', true);
                                
                                foreach ($dias as $num => $nome): 
                                    $horario = $horarios[$num] ?? null;
                                    $is_hoje = $num == $hoje;
                                ?>
                                    <tr <?php echo $is_hoje ? 'class="table-active"' : ''; ?>>
                                        <td>
                                            <strong>
                                                <?php echo $nome; ?>
                                                <?php if ($is_hoje): ?>
                                                    <span class="badge bg-primary">Hoje</span>
                                                <?php endif; ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php if ($horario && isset($horario['status']) && $horario['status'] == 1): ?>
                                                <?php 
                                                echo substr($horario['abertura'], 0, 5) . ' às ' . 
                                                     substr($horario['fechamento'], 0, 5); 
                                                ?>
                                            <?php else: ?>
                                                <span class="text-danger">Fechado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Endereços -->
    <div class="modal fade" id="enderecosModal" tabindex="-1" aria-labelledby="enderecosModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="enderecosModalLabel">
                        <i class="bi bi-geo-alt text-primary me-2"></i>
                        Endereços e Valores de Entrega
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Bairro</th>
                                    <th class="text-end">Valor da Entrega</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Buscar endereços e valores do banco de dados
                                $sql_enderecos = "SELECT bairro, valor_entrega, status FROM enderecos_entrega WHERE status = 1 ORDER BY bairro";
                                $result_enderecos = $conn->query($sql_enderecos);

                                if ($result_enderecos && $result_enderecos->num_rows > 0):
                                    while($endereco = $result_enderecos->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($endereco['bairro']); ?></td>
                                        <td class="text-end">
                                            R$ <?php echo number_format($endereco['valor_entrega'], 2, ',', '.'); ?>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="2" class="text-center">Nenhum endereço cadastrado</td>
                                    </tr>
                                <?php
                                endif;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar ao Carrinho -->
    <div class="modal fade" id="addToCartModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addToCartForm" action="adicionar_carrinho.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Adicionar ao Carrinho</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="produto_id" id="produto_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Quantidade</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" onclick="alterarQuantidade(-1)">-</button>
                                <input type="number" class="form-control text-center" name="quantidade" id="quantidade" value="1" min="1">
                                <button type="button" class="btn btn-outline-secondary" onclick="alterarQuantidade(1)">+</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="observacoes" rows="2" 
                                    placeholder="Ex: Sem cebola, bem passado, etc"></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total:</span>
                            <h4 class="mb-0" id="totalPrice"></h4>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Adicionar ao Carrinho</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal do Carrinho -->
    <div class="modal fade" id="cartModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-cart3 me-2"></i>
                        Meu Carrinho
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cartItems" class="table-responsive">
                        <!-- Os itens do carrinho serão carregados aqui -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continuar Comprando</button>
                    <a href="finalizar_pedido.php" class="btn btn-primary">Finalizar Pedido</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu de Categorias -->
    <nav class="bg-light py-3 shadow-sm"> <!-- Removida a classe sticky-top -->
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="categorias-scroll">
                        <?php 
                        $result_categorias->data_seek(0);
                        while($categoria = $result_categorias->fetch_assoc()): 
                            // Verifica se a categoria tem produtos
                            $sql_count = "SELECT COUNT(*) as total FROM produtos 
                                        WHERE categoria_id = {$categoria['id']} 
                                        AND status = 1";
                            $count = $conn->query($sql_count)->fetch_assoc()['total'];
                            if($count > 0):
                        ?>
                            <a href="#categoria-<?php echo $categoria['id']; ?>" 
                               class="btn btn-outline-primary me-2">
                                <?php echo $categoria['nome']; ?>
                            </a>
                        <?php 
                            endif;
                        endwhile; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <main class="container my-5">
        <!-- Produtos em Destaque -->
        <?php if($total_destaques > 0): ?>
            <section class="mb-5" id="destaques">
                <h2 class="display-6 mb-4">Destaques</h2>
                <div class="row g-4">
                    <?php while($produto = $result_destaques->fetch_assoc()): ?>
                        <div class="col-md-4">
                            <div class="card h-100 shadow-sm">
                                <?php if(!empty($produto['imagem'])): ?>
                                    <img src="uploads/produtos/<?php echo $produto['imagem']; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo $produto['nome']; ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $produto['nome']; ?></h5>
                                    <p class="card-text text-muted"><?php echo $produto['descricao']; ?></p>
                                    <p class="card-text">
                                        <small class="text-muted"><?php echo $produto['categoria_nome']; ?></small>
                                    </p>
                                </div>
                                <div class="card-footer bg-white border-0 pt-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><?php echo formatPrice($produto['preco']); ?></h5>
                                        <button type="button" 
                                                class="btn btn-primary add-to-cart" 
                                                data-id="<?php echo $produto['id']; ?>"
                                                data-nome="<?php echo $produto['nome']; ?>"
                                                data-preco="<?php echo $produto['preco']; ?>">
                                            <i class="bi bi-cart-plus"></i> Adicionar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Paginação dos Destaques -->
                <?php if($total_paginas_destaques > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <nav aria-label="Navegação dos destaques">
                            <ul class="pagination">
                                <?php for($i = 1; $i <= $total_paginas_destaques; $i++): ?>
                                    <li class="page-item <?php echo ($i === $pagina_atual && $categoria_atual === 0) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?pagina=<?php echo $i; ?>#destaques">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- Lista de Categorias e Produtos -->
        <?php 
        $result_categorias->data_seek(0);
        while($categoria = $result_categorias->fetch_assoc()): 
            // Contar total de produtos da categoria
            $sql_total = "SELECT COUNT(*) as total FROM produtos 
                          WHERE categoria_id = {$categoria['id']} AND status = 1";
            $total_produtos = $conn->query($sql_total)->fetch_assoc()['total'];
            
            // Calcular total de páginas da categoria
            $total_paginas = ceil($total_produtos / $produtos_por_pagina);
            
            // Usar paginação específica da categoria se ela estiver selecionada
            $pagina_categoria = ($categoria_atual === (int)$categoria['id']) ? $pagina_atual : 1;
            $offset = ($pagina_categoria - 1) * $produtos_por_pagina;
            
            // Buscar produtos paginados da categoria
            $sql_produtos = "SELECT * FROM produtos 
                            WHERE categoria_id = {$categoria['id']} AND status = 1 
                            ORDER BY nome 
                            LIMIT $produtos_por_pagina OFFSET $offset";
            $result_produtos = $conn->query($sql_produtos);
        ?>
            <section id="categoria-<?php echo $categoria['id']; ?>" class="mb-5">
                <h2 class="display-6 mb-4"><?php echo $categoria['nome']; ?></h2>
                
                <div class="row g-4">
                    <?php while($produto = $result_produtos->fetch_assoc()): ?>
                        <div class="col-md-4">
                            <div class="card h-100 shadow-sm">
                                <?php if(!empty($produto['imagem'])): ?>
                                    <img src="uploads/produtos/<?php echo $produto['imagem']; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo $produto['nome']; ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $produto['nome']; ?></h5>
                                    <p class="card-text text-muted"><?php echo $produto['descricao']; ?></p>
                                </div>
                                <div class="card-footer bg-white border-0 pt-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><?php echo formatPrice($produto['preco']); ?></h5>
                                        <button type="button" 
                                                class="btn btn-primary add-to-cart" 
                                                data-id="<?php echo $produto['id']; ?>"
                                                data-nome="<?php echo $produto['nome']; ?>"
                                                data-preco="<?php echo $produto['preco']; ?>">
                                            <i class="bi bi-cart-plus"></i> Adicionar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Paginação da Categoria -->
                <?php if($total_paginas > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <nav aria-label="Navegação da categoria">
                            <ul class="pagination">
                                <?php for($i = 1; $i <= $total_paginas; $i++): ?>
                                    <li class="page-item <?php echo ($i === $pagina_atual && $categoria_atual === (int)$categoria['id']) ? 'active' : ''; ?>">
                                        <a class="page-link" 
                                           href="?pagina=<?php echo $i; ?>&categoria=<?php echo $categoria['id']; ?>#categoria-<?php echo $categoria['id']; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </section>
        <?php endwhile; ?>
    </main>

    <!-- Rodapé -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>
                        <?php 
                        echo isset($configs['site_titulo']) && !empty($configs['site_titulo']) 
                            ? htmlspecialchars($configs['site_titulo']) 
                            : SITE_NAME; 
                        ?>
                    </h5>
                    <p>
                        <?php 
                        echo isset($configs['site_subtitulo']) && !empty($configs['site_subtitulo'])
                            ? htmlspecialchars($configs['site_subtitulo'])
                            : 'Pedidos online com entrega rápida e segura!'; 
                        ?>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Siga-nos nas redes sociais:</p>
                    <?php if (!empty($configs['facebook_url'])): ?>
                        <a href="<?php echo htmlspecialchars($configs['facebook_url']); ?>" target="_blank" class="text-white me-3">
                            <i class="bi bi-facebook"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($configs['instagram_url'])): ?>
                        <a href="<?php echo htmlspecialchars($configs['instagram_url']); ?>" target="_blank" class="text-white me-3">
                            <i class="bi bi-instagram"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($configs['whatsapp_url'])): ?>
                        <a href="<?php echo htmlspecialchars($configs['whatsapp_url']); ?>" target="_blank" class="text-white">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Variável global para armazenar informações do produto atual
        let produtoAtual = null;

        // Função para alterar quantidade
        function alterarQuantidade(delta) {
            const input = document.getElementById('quantidade');
            const novoValor = Math.max(1, parseInt(input.value) + delta);
            input.value = novoValor;
            atualizarTotal();
        }

        // Função para atualizar o total
        function atualizarTotal() {
            if (!produtoAtual) return;
            
            const quantidade = parseInt(document.getElementById('quantidade').value);
            const total = produtoAtual.preco * quantidade;
            
            document.getElementById('totalPrice').textContent = 
                new Intl.NumberFormat('pt-BR', { 
                    style: 'currency', 
                    currency: 'BRL' 
                }).format(total);
        }

        // Função para atualizar o contador do carrinho em todos os lugares
        function atualizarContadorCarrinho(totalItens) {
            const cartCountElements = document.querySelectorAll('.cart-count');
            cartCountElements.forEach(element => {
                if (totalItens > 0) {
                    element.textContent = totalItens;
                    element.style.display = 'flex';
                } else {
                    element.style.display = 'none';
                }
            });
        }

        // Evento para os botões "Adicionar" nos produtos
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', function() {
                    // Verificar primeiro se a loja está aberta
                    const lojaAberta = <?php echo $loja_aberta ? 'true' : 'false'; ?>;
                    const horarioHoje = <?php echo json_encode($horario_hoje); ?>;
                    
                    if (!lojaAberta) {
                        let mensagem = '';
                        if (horarioHoje && horarioHoje.status == 1) {
                            if ('<?php echo $agora; ?>' < horarioHoje.abertura) {
                                mensagem = `Abriremos hoje às ${horarioHoje.abertura.substr(0, 5)}`;
                            } else {
                                mensagem = `Estamos fechados.<br>Horário de funcionamento: ${horarioHoje.abertura.substr(0, 5)} às ${horarioHoje.fechamento.substr(0, 5)}`;
                            }
                        } else {
                            mensagem = 'Não funcionamos neste dia.';
                        }

                        Swal.fire({
                            icon: 'warning',
                            title: 'Estabelecimento Fechado',
                            html: mensagem,
                            confirmButtonColor: '#ff6b00'
                        });
                        return;
                    }

                    // Se a loja estiver aberta, continua com o fluxo normal existente
                    const id = this.dataset.id;
                    const nome = this.dataset.nome;
                    const preco = parseFloat(this.dataset.preco);

                    produtoAtual = {
                        id: id,
                        nome: nome,
                        preco: preco
                    };

                    document.getElementById('produto_id').value = id;
                    document.getElementById('quantidade').value = 1;
                    document.querySelector('[name="observacoes"]').value = '';
                    
                    atualizarTotal();

                    // Usar o bootstrap para mostrar o modal
                    const modal = new bootstrap.Modal(document.getElementById('addToCartModal'));
                    modal.show();
                });
            });
        });

        // Evento submit do formulário de adicionar ao carrinho
        document.getElementById('addToCartForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Verificar se está logado
            const isLoggedIn = <?php echo isset($_SESSION['usuario_id']) ? 'true' : 'false'; ?>;
            
            if (!isLoggedIn) {
                // Fechar modal de carrinho
                bootstrap.Modal.getInstance(document.getElementById('addToCartModal')).hide();
                
                // Abrir modal de login
                const loginModal = new bootstrap.Modal(document.getElementById('loginCadastroModal'));
                loginModal.show();
                return;
            }
            
            const formData = new FormData(this);
            
            fetch('adicionar_carrinho.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar contador do carrinho
                    atualizarContadorCarrinho(data.total_itens);

                    // Fechar modal de adicionar
                    bootstrap.Modal.getInstance(document.getElementById('addToCartModal')).hide();

                    // Mostrar mensagem de sucesso
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Produto adicionado ao carrinho!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: data.message || 'Erro ao adicionar ao carrinho'
                    });
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: 'Erro ao adicionar ao carrinho'
                });
            });
        });

        // Atualizar evento do modal do carrinho
        document.getElementById('cartModal').addEventListener('show.bs.modal', function() {
    const cartItems = document.getElementById('cartItems');
    cartItems.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';

    fetch('buscar_carrinho.php')
        .then(response => response.json())
        .then(data => {
            let html = '';
            if (data.itens && data.itens.length > 0) {
                html = `
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th class="text-center">Qtd</th>
                                <th class="text-end">Preço</th>
                                <th class="text-end">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.itens.forEach(item => {
                    // Garante que os valores sejam números
                    const preco = typeof item.preco === 'string' ? 
                        parseFloat(item.preco.replace('R$', '').replace('.', '').replace(',', '.')) : 
                        parseFloat(item.preco);
                    
                    const subtotal = typeof item.subtotal === 'string' ? 
                        parseFloat(item.subtotal.replace('R$', '').replace('.', '').replace(',', '.')) : 
                        parseFloat(item.subtotal);

                    html += `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <strong>${item.nome}</strong>
                                        ${item.observacoes ? `<br><small class="text-muted">${item.observacoes}</small>` : ''}
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">${item.quantidade}</td>
                            <td class="text-end">R$ ${preco.toFixed(2).replace('.', ',')}</td>
                            <td class="text-end">R$ ${subtotal.toFixed(2).replace('.', ',')}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-danger" onclick="removerItem(${item.id})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                // Garante que o total seja número
                const total = typeof data.total === 'string' ? 
                    parseFloat(data.total.replace('R$', '').replace('.', '').replace(',', '.')) : 
                    parseFloat(data.total);

                html += `
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td class="text-end"><strong>R$ ${total.toFixed(2).replace('.', ',')}</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                `;
            } else {
                html = '<div class="text-center py-4"><p>Seu carrinho está vazio</p></div>';
            }

            cartItems.innerHTML = html;
        })
        .catch(error => {
            console.error('Erro:', error);
            cartItems.innerHTML = '<div class="alert alert-danger">Erro ao carregar os itens do carrinho</div>';
        });
});

        // Função para remover item do carrinho
        function removerItem(id) {
            if (!confirm('Tem certeza que deseja remover este item?')) {
                return;
            }

            fetch('remover_carrinho.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar contador do carrinho
                    atualizarContadorCarrinho(data.total_itens);
                    
                    // Recarregar itens do carrinho
                    document.getElementById('cartModal').dispatchEvent(new Event('show.bs.modal'));
                } else {
                    alert(data.error || 'Erro ao remover item');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao remover item');
            });
        }

        // Função para verificar pedidos pendentes
function verificarPedidosPendentes() {
    fetch('verificar_pedidos.php')
        .then(response => response.json())
        .then(data => {
            const floatingButton = document.querySelector('.floating-button');
            const badge = document.querySelector('.floating-button .badge');
            
            if (data.total_pendentes > 0) {
                if (badge) badge.textContent = data.total_pendentes;
                if (floatingButton) floatingButton.style.display = 'flex';
            } else {
                if (floatingButton) floatingButton.style.display = 'none';
            }
        })
        .catch(error => console.error('Erro ao verificar pedidos:', error));
}

// Iniciar verificação periódica se o usuário estiver logado
<?php if (isset($_SESSION['usuario_id'])): ?>
    // Primeira verificação
    verificarPedidosPendentes();
    
    // Verificar a cada 30 segundos
    setInterval(verificarPedidosPendentes, 30000);
<?php endif; ?>
    </script>

    <!-- Modal de Login ou Cadastro -->
    <div class="modal fade" id="loginCadastroModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fazer Login ou Criar Conta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#loginTab" type="button">
                                Login
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#cadastroTab" type="button">
                                Criar Conta
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">
                        <!-- Tab Login -->
                        <div class="tab-pane fade show active" id="loginTab">
                            <form id="loginForm" action="login.php" method="POST">
                                <div class="mb-3">
                                    <label class="form-label">E-mail</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Senha</label>
                                    <input type="password" class="form-control" name="senha" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Entrar</button>
                            </form>
                        </div>

                        <!-- Tab Cadastro -->
                        <div class="tab-pane fade" id="cadastroTab">
                            <form id="cadastroForm" method="POST">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome*</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email*</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>

                                <div class="mb-3">
                                    <label for="telefone" class="form-label">Telefone*</label>
                                    <input type="tel" class="form-control" id="telefone" name="telefone" required 
                                           placeholder="(00) 00000-0000">
                                </div>

                                <div class="mb-3">
                                    <label for="endereco" class="form-label">Endereço*</label>
                                    <textarea class="form-control" id="endereco" name="endereco" rows="2" required
                                              placeholder="Rua, número, complemento..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="bairro" class="form-label">Bairro*</label>
                                    <input type="text" class="form-control" id="bairro" name="bairro" required>
                                </div>

                                <div class="mb-3">
                                    <label for="senha" class="form-label">Senha*</label>
                                    <input type="password" class="form-control" id="senha" name="senha" required>
                                </div>

                                <input type="hidden" name="tipo" value="cliente">
                                <input type="hidden" name="status" value="1">

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-person-plus"></i> Cadastrar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    if (isset($_SESSION['usuario_id'])) {
        // Buscar nome do usuário com verificação de nulo
        $sql_usuario = "SELECT nome FROM usuarios WHERE id = ?";
        $stmt_usuario = $conn->prepare($sql_usuario);
        $stmt_usuario->bind_param("i", $_SESSION['usuario_id']);
        $stmt_usuario->execute();
        $result_usuario = $stmt_usuario->get_result();
        
        if ($result_usuario && $result_usuario->num_rows > 0) {
            $usuario = $result_usuario->fetch_assoc();
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Atualizar nome do usuário no cabeçalho
                const nomeUsuario = "<?php echo addslashes($usuario['nome']); ?>";
                const boasVindas = document.getElementById('boasVindas');
                
                if (boasVindas) {
                    boasVindas.innerHTML = 'Bem-vindo, ' + nomeUsuario + '!';
                }

                // Verificar pedidos pendentes ao abrir a página
                verificarPedidosPendentes();
            });
            </script>
            <?php
        }
    }
    ?>

    <!-- Script para o formulário de cadastro -->
    <script>
    // Adicionar o evento de submit para o formulário de cadastro
    document.addEventListener('DOMContentLoaded', function() {
        const cadastroForm = document.getElementById('cadastroForm');
        
        if (cadastroForm) {
            cadastroForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                // Mostrar loading
                Swal.fire({
                    title: 'Aguarde...',
                    text: 'Processando cadastro',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                fetch('cadastrar_cliente.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fechar modal de cadastro
                        bootstrap.Modal.getInstance(document.getElementById('loginCadastroModal')).hide();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: 'Cadastro realizado com sucesso!',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            // Recarregar a página para atualizar os dados do usuário
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: data.message || 'Erro ao realizar cadastro. Tente novamente.'
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: 'Erro ao processar o cadastro. Verifique sua conexão e tente novamente.'
                    });
                });
            });
        }
    });
    </script>
</body>
</html>