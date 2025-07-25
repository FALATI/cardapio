<?php
require_once '../config/config.php';
require_once 'verificar_permissao.php';

// Buscar nome do site das configurações
$sql = "SELECT valor FROM configuracoes WHERE chave = 'site_nome' LIMIT 1";
$result = $conn->query($sql);
$site_nome = "Sistema";  // valor padrão

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $site_nome = $row['valor'];
}

// Nome do arquivo
$filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

// Headers para download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Tabelas para backup
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

// Início do arquivo SQL
echo "-- Backup do banco de dados\n";
echo "-- Data: " . date('Y-m-d H:i:s') . "\n";
echo "-- Sistema: " . $site_nome . "\n\n";

// Backup de cada tabela
foreach ($tables as $table) {
    // Estrutura da tabela
    $result = $conn->query("SHOW CREATE TABLE $table");
    $row = $result->fetch_row();
    echo "\n\n" . $row[1] . ";\n\n";
    
    // Dados da tabela
    $result = $conn->query("SELECT * FROM $table");
    while ($row = $result->fetch_assoc()) {
        $values = array_map(function($value) use ($conn) {
            if ($value === null) return 'NULL';
            return "'" . $conn->real_escape_string($value) . "'";
        }, $row);
        
        echo "INSERT INTO $table VALUES (" . implode(", ", $values) . ");\n";
    }
}

exit;