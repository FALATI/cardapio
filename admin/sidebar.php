<div class="sidebar">
    <div class="px-3 mb-4">
        <h4 class="text-white">
            <i class="bi bi-shop"></i>
            <?php echo SITE_TITLE; ?>
        </h4>
        <small class="text-white-50">Painel Administrativo</small>
    </div>

    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
               href="dashboard.php">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pedidos.php' ? 'active' : ''; ?>" 
               href="pedidos.php">
                <i class="bi bi-cart me-2"></i> Pedidos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'historico_pedidos.php' ? 'active' : ''; ?>" 
               href="historico_pedidos.php">
                <i class="bi bi-clock-history me-2"></i> Histórico de Pedidos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'produtos.php' ? 'active' : ''; ?>" 
               href="produtos.php">
                <i class="bi bi-box me-2"></i> Produtos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : ''; ?>" 
               href="categorias.php">
                <i class="bi bi-tags me-2"></i> Categorias
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>" 
               href="usuarios.php">
                <i class="bi bi-people me-2"></i> Usuários
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'configuracoes.php' ? 'active' : ''; ?>" 
               href="configuracoes.php">
                <i class="bi bi-gear me-2"></i> Configurações
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'verificar_atualizacao.php' ? 'active' : ''; ?>" 
               href="verificar_atualizacao.php">
                <i class="bi bi-arrow-clockwise me-2"></i> Atualização
            </a>
        </li>
        <li class="nav-item mt-4">
            <a class="nav-link text-danger" href="../logout.php">
                <i class="bi bi-box-arrow-right me-2"></i> Sair
            </a>
        </li>
    </ul>
</div>

<style>
    /* Sidebar */
    .sidebar {
        position: fixed; /* Adicionar position fixed */
        top: 0;
        left: 0;
        min-height: 100vh;
        width: 250px; /* reduzido de 280px */
        background: linear-gradient(135deg, #2C475D 0%, #3491D0 100%);
        padding-top: 0.8rem; /* reduzido de 1rem */
        box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        z-index: 9999; /* Aumentar z-index para ficar na frente */
        overflow-y: auto; /* Adicionar scroll vertical se necessário */
    }

    /* Header da Sidebar */
    .sidebar h4 {
        margin-bottom: 0.3rem; /* reduzido de 0.5rem */
        font-weight: 600;
        letter-spacing: 0.5px;
        font-size: 1.1rem; /* adicionado para reduzir tamanho */
    }

    .sidebar h4 i {
        margin-right: 10px;
    }

    /* Links de Navegação */
    .sidebar .nav-link {
        color: rgba(255,255,255,.85) !important;
        padding: 0.5rem 1rem; /* reduzido de 0.8rem 1.5rem */
        display: flex;
        align-items: center;
        border-radius: 8px;
        margin: 1px 8px; /* reduzido de 2px 12px */
        transition: all 0.3s ease;
    }

    .sidebar .nav-link:hover {
        color: #fff !important;
        background: rgba(255,255,255,.1);
        transform: translateX(5px);
    }

    .sidebar .nav-link.active {
        color: #fff !important;
        background: rgba(255,255,255,.15);
        position: relative;
    }

    .sidebar .nav-link.active:before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background: #FF9F43;
        border-radius: 0 4px 4px 0;
    }

    /* Ícones */
    .sidebar .nav-link i {
        width: 24px;
        font-size: 1.1rem;
        margin-right: 10px;
    }

    /* Item de Menu */
    .nav-item {
        margin: 2px 0; /* reduzido de 4px 0 */
    }

    /* Botão de Sair */
    .sidebar .nav-link.text-danger {
        color: rgba(255, 255, 255, 0.9) !important; /* Alterado de #ff6b6b para branco */
    }

    .sidebar .nav-link.text-danger:hover {
        background: rgba(255, 255, 255, 0.15); /* Alterado para fundo branco transparente */
        color: #ffffff !important; /* Branco sólido no hover */
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .sidebar {
            position: fixed; /* manter fixed apenas no mobile */
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0;
            padding: 1rem;
        }
    }

    /* Animações */
    .sidebar .nav-link {
        transition: all 0.3s ease;
    }

    /* Ajustes de Espaçamento */
    .sidebar .nav {
        padding: 0;
    }

    .nav-item:last-child {
        margin-bottom: 0.5rem; /* reduzido de 1rem */
    }
</style>

<script>
document.getElementById('statusLoja').addEventListener('change', function() {
    const status = this.checked ? '1' : '0';
    const label = document.getElementById('statusLabel');
    
    // Atualiza o texto
    label.textContent = this.checked ? 'Aberta' : 'Fechada';
    
    // Envia a atualização para o servidor
    fetch('atualizar_status_loja.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Erro ao atualizar status da loja');
            this.checked = !this.checked;
            label.textContent = this.checked ? 'Aberta' : 'Fechada';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar status da loja');
        this.checked = !this.checked;
        label.textContent = this.checked ? 'Aberta' : 'Fechada';
    });
});
</script>