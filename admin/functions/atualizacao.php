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
        // Arquivos a ignorar
        $ignorar = [
            'config/config.php',
            'config/database.php',
            '.git',
            '.env'
        ];

        // Criar diretórios temporários
        $temp_dir = rtrim(sys_get_temp_dir(), '/\\') . '/cardapio_update_' . time();
        $backup_dir = rtrim(sys_get_temp_dir(), '/\\') . '/cardapio_backup_' . time();
        
        if (!mkdir($temp_dir, 0777, true) || !mkdir($backup_dir, 0777, true)) {
            throw new Exception('Não foi possível criar diretórios temporários');
        }

        // Download do arquivo
        $zip_file = $temp_dir . '/update.zip';
        $fp = fopen($zip_file, 'w+');
        
        $ch = curl_init($download_url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Cardapio-Update-Agent',
            CURLOPT_TIMEOUT => 300
        ]);
        
        $success = curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        if (!$success) {
            throw new Exception('Erro ao baixar atualização');
        }

        // Extrair arquivo
        $zip = new ZipArchive;
        if ($zip->open($zip_file) !== true) {
            throw new Exception('Erro ao abrir arquivo ZIP');
        }
        
        $zip->extractTo($temp_dir);
        $zip->close();

        // Encontrar pasta raiz dos arquivos extraídos
        $extracted_dirs = glob($temp_dir . '/*', GLOB_ONLYDIR);
        if (empty($extracted_dirs)) {
            throw new Exception('Nenhum diretório encontrado no ZIP');
        }
        $source_dir = $extracted_dirs[0];
        
        // Diretório raiz do sistema
        $root_dir = realpath(__DIR__ . '/../../');

        // Fazer backup dos arquivos existentes
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            // Caminho relativo ao diretório raiz
            $relative_path = str_replace($root_dir . DIRECTORY_SEPARATOR, '', $item->getPathname());
            
            // Pular arquivos ignorados
            if (shouldIgnoreFile($relative_path, $ignorar)) {
                continue;
            }

            $backup_path = $backup_dir . DIRECTORY_SEPARATOR . $relative_path;
            
            if ($item->isDir()) {
                @mkdir($backup_path, 0777, true);
            } else {
                @mkdir(dirname($backup_path), 0777, true);
                if (!copy($item->getPathname(), $backup_path)) {
                    throw new Exception("Erro ao fazer backup do arquivo: {$relative_path}");
                }
            }
        }

        // Copiar arquivos novos
        $updated_files = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative_path = str_replace($source_dir . DIRECTORY_SEPARATOR, '', $item->getPathname());
            
            if (shouldIgnoreFile($relative_path, $ignorar)) {
                continue;
            }

            $target = $root_dir . DIRECTORY_SEPARATOR . $relative_path;
            
            if ($item->isDir()) {
                @mkdir($target, 0777, true);
            } else {
                @mkdir(dirname($target), 0777, true);
                if (!copy($item->getPathname(), $target)) {
                    throw new Exception("Erro ao copiar arquivo: {$relative_path}");
                }
                $updated_files++;
            }
        }

        // Limpar arquivos temporários
        deleteDir($temp_dir);

        return [
            'success' => true,
            'message' => "Atualização concluída! {$updated_files} arquivos atualizados.",
            'files_updated' => $updated_files
        ];

    } catch (Exception $e) {
        // Log do erro
        error_log('Erro na atualização: ' . $e->getMessage());
        
        // Tentar restaurar backup
        if (isset($backup_dir) && is_dir($backup_dir)) {
            try {
                restoreBackup($backup_dir, $root_dir);
            } catch (Exception $restore_error) {
                error_log('Erro ao restaurar backup: ' . $restore_error->getMessage());
            }
        }
        
        // Limpar temporários
        if (isset($temp_dir) && is_dir($temp_dir)) {
            deleteDir($temp_dir);
        }
        
        return [
            'error' => true,
            'message' => 'Erro na atualização: ' . $e->getMessage()
        ];
    }
}

function shouldIgnoreFile($path, $ignorar) {
    $path = str_replace('\\', '/', $path);
    foreach ($ignorar as $pattern) {
        if (strpos($path, $pattern) === 0) {
            return true;
        }
    }
    return false;
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