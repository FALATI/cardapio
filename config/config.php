<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir database.php primeiro para ter acesso ao banco
require_once __DIR__ . '/database.php';

// Buscar configurações do banco de dados
$configs = [];
$sql = "SELECT * FROM configuracoes";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $configs[$row['chave']] = $row['valor'];
}

// Configurações do site usando valor do banco
define('SITE_URL', 'http://localhost/cardapio');

// Verificar se existe configuração do nome do site
$sql = "SELECT valor FROM configuracoes WHERE chave = 'site_nome' LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    define('SITE_NAME', $row['valor']);
} else {
    // Se não existir, criar a configuração com um valor padrão
    $nome_padrao = "Nome do Site";
    $sql = "INSERT INTO configuracoes (chave, valor) VALUES ('site_nome', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nome_padrao);
    $stmt->execute();
    
    define('SITE_NAME', $nome_padrao);
}

// Configurações de upload
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/cardapio/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');

// Definir constantes dinâmicas baseadas nas configurações
if (!empty($configs['site_titulo'])) {
    define('SITE_TITLE', $configs['site_titulo']);
} else {
    define('SITE_TITLE', SITE_NAME);
}

// Incluir functions.php por último para evitar conflitos
if (!function_exists('formatPrice')) {
    require_once __DIR__ . '/../includes/functions.php';
}