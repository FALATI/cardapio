<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações do site
define('SITE_NAME', 'Sonhado Sabor');
define('SITE_URL', 'http://localhost/cardapio');

// Configurações de upload
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/cardapio/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');

// Incluir database.php primeiro
require_once __DIR__ . '/database.php';

// Buscar configurações do banco de dados
$configs = [];
$sql = "SELECT * FROM configuracoes";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $configs[$row['chave']] = $row['valor'];
}

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