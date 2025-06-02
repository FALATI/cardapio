<?php
// Prevenir qualquer saída
ob_start();

require_once '../config/config.php';
require_once 'verificar_permissao.php';
require_once 'functions/atualizacao.php';

// Rota para verificação via AJAX
if (isset($_POST['acao'])) {
    // Limpar qualquer saída anterior
    ob_clean();
    
    // Definir headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        if ($_POST['acao'] === 'verificar') {
            echo json_encode(verificarAtualizacao(), JSON_THROW_ON_ERROR);
            exit;
        }
        elseif ($_POST['acao'] === 'atualizar') {
            if (empty($_POST['download_url'])) {
                throw new Exception('URL de download não fornecida');
            }
            
            $resultado = atualizarSistema($_POST['download_url']);
            echo json_encode($resultado, JSON_THROW_ON_ERROR);
            exit;
        }
    } catch (Throwable $e) {
        error_log('Erro na atualização: ' . $e->getMessage());
        echo json_encode([
            'error' => true,
            'message' => 'Erro ao processar requisição: ' . $e->getMessage()
        ], JSON_THROW_ON_ERROR);
        exit;
    }
}

// Se não for uma requisição AJAX, continua com o HTML
define('BASEPATH', true);
include 'header.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_titulo; ?></title>
    <?php include 'header.php'; ?>
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

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(135deg, #2C475D 0%, #3491D0 100%);
            padding-top: 1rem;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        /* Conteúdo Principal */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            transition: margin 0.3s ease;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1.25rem;
        }

        /* Tabs */
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            padding: 1rem 1.5rem;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link:hover {
            border: none;
            color: var(--bs-primary);
        }

        .nav-tabs .nav-link.active {
            border: none;
            color: var(--bs-primary);
            position: relative;
        }

        .nav-tabs .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--bs-primary);
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        /* Progress Bar */
        .progress {
            height: 0.8rem;
            border-radius: 1rem;
            background-color: #e9ecef;
        }

        .progress-bar {
            background-color: var(--bs-primary);
        }

        /* Botões */
        .btn-primary {
            background: var(--bs-primary);
            border-color: var(--bs-primary);
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background: var(--bs-primary-hover);
            border-color: var(--bs-primary-hover);
        }

        /* Versão Info */
        .version-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .nav-tabs .nav-link {
                padding: 0.75rem 1rem;
            }
        }

        /* Animações */
        .card {
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert {
            animation: fadeIn 0.3s ease;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid px-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-arrow-clockwise"></i>
                        Atualização do Sistema - <?php echo $site_nome; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="me-auto">
                            <h6 class="text-muted mb-1">Versão Atual</h6>
                            <h4 class="mb-0"><?php echo @file_get_contents('../version.txt') ?: '1.0.0'; ?></h4>
                        </div>
                    </div>

                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-atualizacao" type="button">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Atualização
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-backup" type="button">
                                <i class="bi bi-database-check me-2"></i>
                                Backup
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Tab Atualização -->
                        <div class="tab-pane fade show active" id="tab-atualizacao">
                            <div class="text-center mb-4">
                                <button type="button" class="btn btn-primary" id="btn-verificar" onclick="verificarAtualizacao()">
                                    <i class="bi bi-arrow-repeat me-2"></i>
                                    Verificar Atualização
                                </button>
                            </div>
                            
                            <div id="resultado_verificacao"></div>
                        </div>

                        <!-- Tab Backup -->
                        <div class="tab-pane fade" id="tab-backup">
                            <div class="alert alert-info mb-4">
                                <i class="bi bi-info-circle me-2"></i>
                                Faça backup do banco de dados antes de atualizar o sistema.
                            </div>

                            <div class="text-center">
                                <form action="backup.php" method="POST">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-download me-2"></i>
                                        Fazer Backup
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-primary d-md-none position-fixed top-0 start-0 mt-2 ms-2 rounded-circle" 
            onclick="document.querySelector('.sidebar').classList.toggle('show')" 
            style="z-index: 1001; width: 42px; height: 42px;">
        <i class="bi bi-list"></i>
    </button>

    <script>
        function verificarAtualizacao() {
            const btnVerificar = document.querySelector('#btn-verificar');
            btnVerificar.disabled = true;
            btnVerificar.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Verificando...';

            const formData = new FormData();
            formData.append('acao', 'verificar');
            
            fetch('verificar_atualizacao.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                let html = '';
                const versaoAtual = document.querySelector('h4.mb-0').textContent.trim(); // Pega a versão do version.txt
                
                if (data.erro) {
                    html = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            ${data.mensagem}
                        </div>
                    `;
                }
                // Compara exatamente as versões do version.txt
                else if (versaoAtual === data.nova_versao) {
                    html = `
                        <div class="alert alert-success">
                            <div class="d-flex align-items-center">
                                <div class="me-auto">
                                    <h5 class="mb-1">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Sistema Atualizado
                                    </h5>
                                </div>
                            </div>
                        </div>
                    `;
                }
                else {
                    html = `
                        <div class="alert alert-warning">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-auto">
                                    <h5 class="mb-1">
                                        <i class="bi bi-arrow-up-circle me-2"></i>
                                        Nova versão disponível!
                                    </h5>
                                    <div class="text-muted">
                                        Versão atual: ${versaoAtual}<br>
                                        Versão disponível: ${data.nova_versao}
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <strong>Novidades:</strong>
                                <div class="mt-2 p-3 bg-light rounded">
                                    ${data.descricao ? data.descricao.replace(/\n/g, '<br>') : 'Nenhuma descrição disponível'}
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Recomendamos fazer backup antes de atualizar.
                            </div>
                            <button class="btn btn-primary" onclick="atualizarSistema('${data.download_url}')">
                                <i class="bi bi-cloud-download me-2"></i>
                                Atualizar Agora
                            </button>
                        </div>
                    `;
                }
                
                document.getElementById('resultado_verificacao').innerHTML = html;
            })
            .catch(error => {
                console.error('Erro:', error);
                document.getElementById('resultado_verificacao').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Erro ao verificar atualizações: ${error.message}
                    </div>
                `;
            })
            .finally(() => {
                btnVerificar.disabled = false;
                btnVerificar.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Verificar Atualização';
            });
        }

        function atualizarSistema(download_url) {
            // Mostrar modal de progresso com efeito aprimorado
            Swal.fire({
                title: 'Atualizando sistema',
                html: `
                    <div class="text-center mb-3">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <h5 class="text-primary mb-3">Carregando...</h5>
                    </div>
                    <div class="text-start">
                        <p class="mb-2">Iniciando atualização do sistema</p>
                        <div class="progress" style="height: 15px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                 role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                        <p class="text-muted mt-2 small">Por favor, aguarde. Não feche esta janela.</p>
                    </div>
                `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                backdrop: `
                    rgba(0,0,123,0.4)
                    url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cstyle%3E.spinner_P7sC%7Banimation:spinner_svv2 1.5s linear infinite;stroke:%233498db%7D@keyframes spinner_svv2%7B100%25%7Btransform:rotate(360deg)%7D%7D%3C/style%3E%3Cg class='spinner_P7sC'%3E%3Ccircle cx='12' cy='12' r='9.5' fill='none' stroke-width='2'/%3E%3C/g%3E%3C/svg%3E")
                    center 10% no-repeat
                `
            });

            // Simular progresso (puramente visual)
            let progress = 0;
            const interval = setInterval(() => {
                progress += 5;
                if (progress <= 90) { // Para em 90% para aguardar a resposta real
                    const progressBar = document.querySelector('.progress-bar');
                    if (progressBar) {
                        progressBar.style.width = progress + '%';
                        progressBar.textContent = progress + '%';
                        progressBar.setAttribute('aria-valuenow', progress);
                    }
                } else {
                    clearInterval(interval);
                }
            }, 800);

            // Preparar dados
            const formData = new FormData();
            formData.append('acao', 'atualizar');
            formData.append('download_url', download_url);
            
            // Fazer requisição
            fetch('verificar_atualizacao.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                // Limpar o intervalo quando a resposta chegar
                clearInterval(interval);
                
                // Primeiro pegar o texto da resposta
                const text = await response.text();
                
                // Log para debug
                console.log('Resposta do servidor:', text);
                
                try {
                    // Tentar converter para JSON
                    const data = JSON.parse(text);
                    
                    if (data.error) {
                        throw new Error(data.message);
                    }
                    
                    // Mostrar 100% antes de fechar
                    const progressBar = document.querySelector('.progress-bar');
                    if (progressBar) {
                        progressBar.style.width = '100%';
                        progressBar.textContent = '100%';
                        progressBar.setAttribute('aria-valuenow', 100);
                    }
                    
                    // Pequeno delay para mostrar o 100% antes de exibir o sucesso
                    setTimeout(() => {
                        // Sucesso
                        Swal.fire({
                            icon: 'success',
                            title: 'Sucesso!',
                            text: data.message || 'Sistema atualizado com sucesso!',
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.reload();
                        });
                    }, 800);
                    
                } catch (e) {
                    console.error('Erro ao parsear JSON:', e);
                    throw new Error('Resposta inválida do servidor: ' + text.substring(0, 100));
                }
            })
            .catch(error => {
                // Limpar o intervalo em caso de erro
                clearInterval(interval);
                
                console.error('Erro completo:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro na Atualização',
                    text: error.message || 'Ocorreu um erro ao atualizar o sistema.',
                    confirmButtonText: 'Fechar'
                });
            });
        }
    </script>
</body>
</html>