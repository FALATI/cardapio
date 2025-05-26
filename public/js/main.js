// Função para atualizar a URL sem recarregar a página
function atualizarPagina(pagina, categoriaId) {
    const url = new URL(window.location);
    url.searchParams.set('pagina', pagina);
    window.history.pushState({}, '', url);

    // Scroll suave até a categoria
    const elemento = document.getElementById('categoria-' + categoriaId);
    if (elemento) {
        elemento.scrollIntoView({ behavior: 'smooth' });
    }
}

// Função para atualizar o contador do carrinho
function atualizarContadorCarrinho() {
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        fetch('get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                cartCount.textContent = data.count;
            })
            .catch(error => console.error('Erro:', error));
    }
}

// Atualiza o contador quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    atualizarContadorCarrinho();

    // Interceptar cliques nos links de paginação
    document.querySelectorAll('.pagination .page-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = new URL(this.href);
            const pagina = url.searchParams.get('pagina');
            const categoria = url.searchParams.get('categoria');
            const hash = this.href.split('#')[1];
            
            // Atualizar URL
            if (categoria) {
                window.history.pushState({}, '', `?pagina=${pagina}&categoria=${categoria}#${hash}`);
            } else {
                window.history.pushState({}, '', `?pagina=${pagina}#${hash}`);
            }

            // Scroll suave até a seção
            const elemento = document.getElementById(hash);
            if (elemento) {
                elemento.scrollIntoView({ behavior: 'smooth' });
            }

            // Recarregar a página após um pequeno delay
            setTimeout(() => window.location.reload(), 100);
        });
    });
});

// Atualiza o contador quando um produto é adicionado
function adicionarAoCarrinho(produtoId) {
    // ...código existente de adicionar ao carrinho...
    
    // Atualiza o contador
    atualizarContadorCarrinho();
}