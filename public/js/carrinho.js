let produtoAtual = null;

document.querySelectorAll('.add-to-cart').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.dataset.id;
        const nome = this.dataset.nome;
        const preco = parseFloat(this.dataset.preco);
        
        produtoAtual = { id, nome, preco };
        
        document.getElementById('produto_id').value = id;
        document.getElementById('quantidade').value = 1;
        atualizarTotal();
        
        new bootstrap.Modal(document.getElementById('addToCartModal')).show();
    });
});

function alterarQuantidade(delta) {
    const input = document.getElementById('quantidade');
    const novoValor = Math.max(1, parseInt(input.value) + delta);
    input.value = novoValor;
    atualizarTotal();
}

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