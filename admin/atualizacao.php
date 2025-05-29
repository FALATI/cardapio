<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizações - <?php echo $site_titulo; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">
                <!-- Cabeçalho -->
                <div class="page-header mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0">Atualizações do Sistema</h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item">
                                        <a href="dashboard.php" class="text-decoration-none">Home</a>
                                    </li>
                                    <li class="breadcrumb-item active">Atualizações</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Card principal -->
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center mb-4">
                            <div class="col-auto">
                                <div class="update-icon">
                                    <i class="bi bi-arrow-up-circle"></i>
                                </div>
                            </div>
                            <div class="col">
                                <h5 class="card-title mb-1">Status do Sistema</h5>
                                <p class="text-muted mb-0">Versão atual: 
                                    <span class="version-badge bg-primary text-white">
                                        v<?php echo $versao_atual; ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div id="updateStatus">
                            <!-- Aqui será inserido o status da verificação -->
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-primary" onclick="verificarAtualizacoes()">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Verificar Atualizações
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-border text-primary mb-2" role="status"></div>
            <p class="mb-0">Processando atualização...</p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verificarAtualizacoes() {
            const updateStatus = document.getElementById('updateStatus');
            updateStatus.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Verificando atualizações...
                </div>
            `;

            fetch('verificar_atualizacao.php')
                .then(response => response.json())
                .then(data => {
                    // Função para comparar versões
                    const compararVersoes = (versaoAtual, versaoNova) => {
                        const atual = versaoAtual.replace('v', '').split('.').map(Number);
                        const nova = versaoNova.replace('v', '').split('.').map(Number);
                        
                        for (let i = 0; i < Math.max(atual.length, nova.length); i++) {
                            const a = atual[i] || 0;
                            const b = nova[i] || 0;
                            if (a > b) return 1;
                            if (a < b) return -1;
                        }
                        return 0;
                    };

                    // Compara as versões
                    const comparacao = compararVersoes(document.querySelector('.version-badge').textContent, data.nova_versao);

                    if (comparacao >= 0) { // Se versão atual é igual ou maior que a disponível
                        updateStatus.innerHTML = `
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                Seu sistema está atualizado!
                            </div>
                        `;
                    } else { // Se versão atual é menor que a disponível
                        updateStatus.innerHTML = `
                            <div class="alert alert-warning">
                                <h6 class="alert-heading mb-2">Nova versão disponível!</h6>
                                <p class="mb-2">Versão ${data.nova_versao} disponível para instalação.</p>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small>Notas da atualização: ${data.descricao}</small>
                                    <button class="btn btn-warning btn-sm" onclick="iniciarAtualizacao('${data.download_url}')">
                                        Atualizar Agora
                                    </button>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    updateStatus.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Erro ao verificar atualizações: ${error}
                        </div>
                    `;
                });
        }

        function iniciarAtualizacao(downloadUrl) {
            if (!confirm('Deseja realmente atualizar o sistema?')) return;

            document.getElementById('loadingOverlay').style.display = 'flex';

            fetch('atualizar_sistema.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ download_url: downloadUrl })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingOverlay').style.display = 'none';
                if (data.success) {
                    alert('Sistema atualizado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro na atualização: ' + data.message);
                }
            })
            .catch(error => {
                document.getElementById('loadingOverlay').style.display = 'none';
                alert('Erro ao atualizar: ' + error);
            });
        }
    </script>
</body>
</html>