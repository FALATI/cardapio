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
        <li class="nav-item mt-4">
            <a class="nav-link text-danger" href="../logout.php">
                <i class="bi bi-box-arrow-right me-2"></i> Sair
            </a>
        </li>
    </ul>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 280px;
    background: #343a40;
    padding-top: 1rem;
    z-index: 1000;
}

.sidebar .nav-link {
    color: rgba(255,255,255,.75) !important;
    padding: 0.8rem 1.5rem;
    display: flex;
    align-items: center;
    transition: all 0.3s;
}

.sidebar .nav-link:hover {
    color: #fff !important;
    background: rgba(255,255,255,.1);
}

.sidebar .nav-link.active {
    color: #fff !important;
    background: rgba(255,255,255,.1);
    position: relative;
}

.sidebar .nav-link.active:before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 4px;
    background: #ff6b00;
}

.sidebar .nav-link i {
    width: 24px;
}

.nav-item {
    margin: 4px 0;
}

.main-content {
    margin-left: 280px;
    padding: 2rem;
}

/* Remover quaisquer margens ou paddings personalizados */
.sidebar .nav {
    padding: 0;
}

.sidebar h4 {
    margin-bottom: 0.5rem;
}

/* Ajuste para o último item */
.nav-item:last-child {
    margin-bottom: 1rem;
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