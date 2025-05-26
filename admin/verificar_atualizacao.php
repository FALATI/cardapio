<?php

require_once '../config/config.php';
require_once 'verificar_permissao.php';

// Definir constante para controle de acesso
define('BASEPATH', true);

// Incluir header
include 'header.php';

// Configurações do repositório
$owner = 'FALATI';
$repo = 'cardapio';
$branch = 'main';
$github_token = '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';

function verificarAtualizacao() {
    global $owner, $repo, $branch, $github_token;
    
    try {
        // Verificar versão atual
        $versao_atual = @file_get_contents('../version.txt') ?: '1.0.0';
        
        // Buscar última versão no GitHub
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/$owner/$repo/releases/latest");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: PHP',
            "Authorization: token $github_token"
        ]);
        
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($status_code !== 200) {
            throw new Exception('Erro ao conectar com GitHub API');
        }
        
        $release = json_decode($response, true);
        $ultima_versao = ltrim($release['tag_name'], 'v');
        
        // Comparar versões
        return [
            'tem_atualizacao' => version_compare($ultima_versao, $versao_atual, '>'),
            'versao_atual' => $versao_atual,
            'nova_versao' => $ultima_versao,
            'descricao' => $release['body'] ?? '',
            'download_url' => $release['zipball_url']
        ];
        
    } catch (Exception $e) {
        return [
            'erro' => true,
            'mensagem' => $e->getMessage()
        ];
    }
}

function atualizarSistema($download_url) {
    try {
        // Criar pasta temporária
        $temp_dir = sys_get_temp_dir() . '/cardapio_update_' . time();
        if (!mkdir($temp_dir)) {
            throw new Exception('Erro ao criar diretório temporário');
        }
        
        // Download do arquivo
        $zip_file = $temp_dir . '/update.zip';
        $fp = fopen($zip_file, 'w+');
        
        $ch = curl_init($download_url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        
        // Extrair arquivo
        $zip = new ZipArchive;
        if ($zip->open($zip_file) !== TRUE) {
            throw new Exception('Erro ao abrir arquivo ZIP');
        }
        $zip->extractTo($temp_dir);
        $zip->close();
        
        // Copiar arquivos
        $source_dir = glob($temp_dir . '/*', GLOB_ONLYDIR)[0];
        copyDir($source_dir, realpath(__DIR__ . '/..'));
        
        // Limpar arquivos temporários
        deleteDir($temp_dir);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return [
            'erro' => true,
            'mensagem' => $e->getMessage()
        ];
    }
}

function copyDir($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while($file = readdir($dir)) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copyDir($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

function deleteDir($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!deleteDir($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    return rmdir($dir);
}

// Rota para verificação via AJAX
if (isset($_POST['acao'])) {
    header('Content-Type: application/json');
    
    if ($_POST['acao'] === 'verificar') {
        echo json_encode(verificarAtualizacao());
    }
    elseif ($_POST['acao'] === 'atualizar') {
        echo json_encode(atualizarSistema($_POST['download_url']));
    }
    exit;
}
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
                                <button type="button" class="btn btn-primary" onclick="verificarAtualizacao()">
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
            document.getElementById('resultado_verificacao').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Verificando...</span>
                    </div>
                    <p class="mt-2">Verificando atualizações...</p>
                </div>
            `;
            
            fetch('verificar_atualizacao.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'acao=verificar'
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
                            <h5>
                                <i class="bi bi-arrow-up-circle me-2"></i>
                                Nova versão disponível!
                            </h5>
                            <p class="mb-2">
                                Nova versão: ${data.nova_versao}
                            </p>
                            <div class="mb-3">
                                <strong>Novidades:</strong><br>
                                ${data.descricao.replace(/\n/g, '<br>')}
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
                            <i class="bi bi-check-circle me-2"></i>
                            Seu sistema está atualizado!
                        </div>
                    `;
                }
                
                document.getElementById('resultado_verificacao').innerHTML = html;
            });
        }

        function atualizarSistema(download_url) {
            if (!confirm('Tem certeza que deseja atualizar o sistema?')) return;
            
            document.getElementById('resultado_verificacao').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Atualizando...</span>
                    </div>
                    <p class="mt-2">Atualizando sistema...</p>
                </div>
            `;
            
            fetch('verificar_atualizacao.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `acao=atualizar&download_url=${encodeURIComponent(download_url)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.erro) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro',
                        text: data.mensagem
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Sistema atualizado com sucesso! A página será recarregada.',
                        allowOutsideClick: false
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        }
    </script>
</body>
</html>