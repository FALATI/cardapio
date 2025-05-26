<?php
function verificarAtualizacao() {
    // Verificar versão atual
    $versao_atual = @file_get_contents(__DIR__ . '/../../version.txt') ?: '1.0.0';
    
    try {
        // Configurações do GitHub
        $owner = 'FALATI';
        $repo = 'cardapio';
        
        // URL para listar releases em ordem cronológica reversa
        $api_url = "https://api.github.com/repos/$owner/$repo/releases";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'Cardapio-Update-Agent',
            CURLOPT_HTTPHEADER => [
                'Accept: application/vnd.github.v3+json'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($status_code !== 200) {
            throw new Exception("Erro ao acessar API do GitHub (Status $status_code): " . $response);
        }
        
        $releases = json_decode($response, true);
        if (!$releases || !is_array($releases)) {
            throw new Exception('Nenhuma release encontrada no GitHub');
        }
        
        // Pega a release mais recente
        $ultima_release = null;
        $ultima_versao = '0.0.0';
        
        foreach ($releases as $release) {
            $versao = ltrim($release['tag_name'] ?? '', 'v');
            if (version_compare($versao, $ultima_versao, '>')) {
                $ultima_versao = $versao;
                $ultima_release = $release;
            }
        }
        
        if (!$ultima_release) {
            throw new Exception('Nenhuma release válida encontrada');
        }
        
        // Debug info
        $debug = [
            'url' => $api_url,
            'versao_atual' => $versao_atual,
            'versao_github' => $ultima_versao,
            'releases_count' => count($releases),
            'response_code' => $status_code
        ];
        
        return [
            'success' => true,
            'tem_atualizacao' => version_compare($ultima_versao, $versao_atual, '>'),
            'versao_atual' => $versao_atual,
            'nova_versao' => $ultima_versao,
            'descricao' => $ultima_release['body'] ?? 'Sem descrição disponível',
            'download_url' => $ultima_release['zipball_url'] ?? '',
            'debug' => $debug
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'erro' => true,
            'mensagem' => $e->getMessage(),
            'debug' => isset($debug) ? $debug : null
        ];
    }
}

function atualizarSistema($download_url) {
    try {
        // Criar diretório temporário
        $temp_dir = sys_get_temp_dir() . '/cardapio_update_' . time();
        if (!mkdir($temp_dir, 0777, true)) {
            throw new Exception('Não foi possível criar diretório temporário');
        }

        // Download do arquivo
        $zip_file = $temp_dir . '/update.zip';
        $progress = [
            'status' => 'downloading',
            'message' => 'Baixando arquivos de atualização...',
            'progress' => 0
        ];
        
        $fp = fopen($zip_file, 'w+');
        $ch = curl_init($download_url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Cardapio-Update-Agent',
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => function($resource, $download_size, $downloaded) use (&$progress) {
                if ($download_size > 0) {
                    $progress['progress'] = round(($downloaded / $download_size) * 100);
                }
            }
        ]);
        
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        // Extrair arquivo
        $progress = [
            'status' => 'extracting',
            'message' => 'Extraindo arquivos...',
            'progress' => 0
        ];
        
        $zip = new ZipArchive;
        if ($zip->open($zip_file) !== true) {
            throw new Exception('Erro ao abrir arquivo ZIP');
        }
        
        $zip->extractTo($temp_dir);
        $zip->close();

        // Copiar arquivos
        $progress = [
            'status' => 'copying',
            'message' => 'Copiando arquivos...',
            'progress' => 0
        ];
        
        $source_dir = glob($temp_dir . '/*', GLOB_ONLYDIR)[0];
        $root_dir = realpath(__DIR__ . '/../../');
        
        // Fazer backup dos arquivos existentes
        $backup_dir = sys_get_temp_dir() . '/cardapio_backup_' . time();
        mkdir($backup_dir, 0777, true);
        
        // Copiar arquivos novos
        $total_files = count(new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        ));
        $copied_files = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $path = $iterator->getSubPathName();
            $target = $root_dir . '/' . $path;
            
            // Fazer backup do arquivo existente
            if (file_exists($target)) {
                $backup_path = $backup_dir . '/' . $path;
                @mkdir(dirname($backup_path), 0777, true);
                copy($target, $backup_path);
            }
            
            if ($item->isDir()) {
                @mkdir($target, 0777, true);
            } else {
                @mkdir(dirname($target), 0777, true);
                copy($item->getPathname(), $target);
            }
            
            $copied_files++;
            $progress['progress'] = round(($copied_files / $total_files) * 100);
        }

        // Limpar arquivos temporários
        $progress = [
            'status' => 'cleaning',
            'message' => 'Limpando arquivos temporários...',
            'progress' => 100
        ];
        
        deleteDir($temp_dir);

        return [
            'success' => true,
            'message' => 'Atualização concluída com sucesso!',
            'backup_dir' => $backup_dir
        ];

    } catch (Exception $e) {
        // Em caso de erro, tentar restaurar backup
        if (isset($backup_dir) && is_dir($backup_dir)) {
            restoreBackup($backup_dir, $root_dir);
        }
        
        return [
            'success' => false,
            'error' => true,
            'message' => 'Erro na atualização: ' . $e->getMessage()
        ];
    }
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

function restoreBackup($backup_dir, $root_dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($backup_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $path = $iterator->getSubPathName();
        $target = $root_dir . '/' . $path;
        
        if ($item->isDir()) {
            @mkdir($target, 0777, true);
        } else {
            @mkdir(dirname($target), 0777, true);
            copy($item->getPathname(), $target);
        }
    }
}