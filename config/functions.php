<?php
// Adicionar estas funções no arquivo functions.php
function logError($message, $data = []) {
    $logFile = __DIR__ . '/../logs/error.log';
    $logDir = dirname($logFile);

    // Criar diretório de logs se não existir
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }

    // Formatar a mensagem
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    
    if (!empty($data)) {
        $logMessage .= "Data: " . print_r($data, true) . "\n";
    }
    
    $logMessage .= "----------------------------------------\n";
    
    // Salvar no arquivo
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

function logToFile($message, $data = []) {
    $logFile = __DIR__ . '/../logs/debug.txt';
    $logDir = dirname($logFile);

    // Criar diretório se não existir
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }

    // Formatar a mensagem
    $log = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    
    // Adicionar dados se houver
    if (!empty($data)) {
        $log .= "Dados: " . print_r($data, true) . "\n";
    }
    
    $log .= "----------------------------------------\n";

    // Salvar no arquivo
    file_put_contents($logFile, $log, FILE_APPEND);
}