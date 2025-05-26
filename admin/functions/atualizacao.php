<?php
function verificarAtualizacao() {
    // Verificar versão atual
    $versao_atual = @file_get_contents(__DIR__ . '/../../version.txt') ?: '1.0.0';
    
    try {
        // Configurações do GitHub
        $owner = 'FALATI';
        $repo = 'cardapio';
        
        // URL correta para listar todas as releases
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
        
        // Debug info
        $debug = [
            'url' => $api_url,
            'versao_atual' => $versao_atual,
            'response' => $response,
            'status_code' => $status_code
        ];
        
        if ($status_code === 404) {
            throw new Exception("Não foi possível acessar as releases. Verifique se existem releases publicadas em:\nhttps://github.com/$owner/$repo/releases");
        }
        
        if (curl_errno($ch)) {
            throw new Exception('Erro cURL: ' . curl_error($ch) . "\nDebug: " . print_r($debug, true));
        }
        
        curl_close($ch);
        
        if ($status_code !== 200) {
            throw new Exception("Erro na API do GitHub (Status $status_code)");
        }
        
        $releases = json_decode($response, true);
        if (!$releases || !is_array($releases)) {
            throw new Exception('Erro ao decodificar resposta do GitHub: ' . json_last_error_msg());
        }
        
        // Pega a última release
        $release = $releases[0];
        
        $ultima_versao = ltrim($release['tag_name'] ?? '', 'v');
        if (empty($ultima_versao)) {
            throw new Exception('Versão não encontrada na release');
        }
        
        return [
            'success' => true,
            'tem_atualizacao' => version_compare($ultima_versao, $versao_atual, '>'),
            'versao_atual' => $versao_atual,
            'nova_versao' => $ultima_versao,
            'descricao' => $release['body'] ?? 'Sem descrição disponível',
            'download_url' => $release['zipball_url'] ?? ''
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