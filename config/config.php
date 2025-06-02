<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir database.php primeiro para ter acesso ao banco
require_once __DIR__ . '/database.php';

// Buscar todas as configurações do banco de dados
$configs = [];
$sql = "SELECT * FROM configuracoes";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $configs[$row['chave']] = $row['valor'];
}

// Configurações do site usando valor do banco
// Detectar URL do site automaticamente
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);

// Se estiver em localhost, mantém o /cardapio, senão usa apenas o domínio
if ($domain === 'localhost') {
    define('SITE_URL', $protocol . $domain . $path);
} else {
    define('SITE_URL', $protocol . $domain);
}

// Verificar e definir o nome do site a partir das configurações
if (isset($configs['site_nome']) && !empty($configs['site_nome'])) {
    define('SITE_NAME', $configs['site_nome']);
} else {
    // Verificar diretamente no banco de dados
    $sql = "SELECT valor FROM configuracoes WHERE chave = 'site_nome' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        define('SITE_NAME', $row['valor']);
    } else {
        // Se não existir, criar a configuração com um valor padrão
        $nome_padrao = "Delivery";
        $sql = "INSERT INTO configuracoes (chave, valor) VALUES ('site_nome', ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nome_padrao);
        $stmt->execute();
        
        define('SITE_NAME', $nome_padrao);
    }
}

// Verificar e definir o título do site a partir das configurações
if (isset($configs['site_titulo']) && !empty($configs['site_titulo'])) {
    define('SITE_TITLE', $configs['site_titulo']);
} else {
    // Verificar diretamente no banco de dados
    $sql = "SELECT valor FROM configuracoes WHERE chave = 'site_titulo' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        define('SITE_TITLE', $row['valor']);
    } else {
        // Se não existir, usar o nome do site como título
        define('SITE_TITLE', SITE_NAME);
    }
}

// Configurações de upload
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/cardapio/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');

// Disponibilizar todas as configurações como variáveis globais para fácil acesso
// $site_nome = SITE_NAME; // Comentando esta linha para não usar mais
$site_titulo = SITE_TITLE; // Mantendo apenas esta variável

// Garantir que a variável nunca seja vazia
if (empty($site_titulo)) {
    $site_titulo = "Delivery"; // Valor padrão caso esteja vazio
}

// Incluir functions.php por último para evitar conflitos
if (!function_exists('formatPrice')) {
    require_once __DIR__ . '/../includes/functions.php';
}