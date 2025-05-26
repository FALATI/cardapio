<?php
// Prevenir qualquer saída HTML
ob_start();

require_once '../config/config.php';
require_once 'verificar_permissao.php';
require_once 'functions/atualizacao.php';

// Rota para verificação via AJAX
if (isset($_POST['acao'])) {
    // Limpar qualquer saída anterior
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        if ($_POST['acao'] === 'verificar') {
            echo json_encode(verificarAtualizacao(), JSON_THROW_ON_ERROR);
            exit;
        }
        elseif ($_POST['acao'] === 'atualizar') {
            echo json_encode(atualizarSistema($_POST['download_url']), JSON_THROW_ON_ERROR);
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
    <title>Atualização do Sistema - <?php echo SITE_NAME; ?></title>
    <?php include 'header.php'; ?>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid px-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-arrow-clockwise"></i>
                        Atualização do Sistema
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
                
                if (data.erro) {
                    html = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            ${data.mensagem}
                        </div>
                    `;
                }
                else if (data.tem_atualizacao) {
                    html = `
                        <div class="alert alert-warning">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-auto">
                                    <h5 class="mb-1">
                                        <i class="bi bi-arrow-up-circle me-2"></i>
                                        Nova versão disponível!
                                    </h5>
                                    <div class="text-muted">
                                        Versão atual: ${data.versao_atual}<br>
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
                else {
                    html = `
                        <div class="alert alert-success">
                            <div class="d-flex align-items-center">
                                <div class="me-auto">
                                    <h5 class="mb-1">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Sistema Atualizado
                                    </h5>
                                    <div class="text-muted">
                                        Versão atual: ${data.versao_atual}<br>
                                        Última versão: ${data.nova_versao}
                                    </div>
                                </div>
                            </div>
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
            Swal.fire({
                title: 'Atualizando sistema',
                html: `
                    <div class="text-start">
                        <p class="mb-2">Iniciando atualização...</p>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%">0%</div>
                        </div>
                    </div>
                `,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false
            });

            const formData = new FormData();
            formData.append('acao', 'atualizar');
            formData.append('download_url', download_url);
            
            fetch('verificar_atualizacao.php', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Resposta não-JSON:', text);
                    throw new Error('Resposta inválida do servidor');
                }
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.message);
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: data.message || 'Sistema atualizado com sucesso!',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.reload();
                });
            })
            .catch(error => {
                console.error('Erro:', error);
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