<?php
// Função para limpar input
if (!function_exists('cleanInput')) {
    function cleanInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

// Função para formatar preço
if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        return 'R$ ' . number_format($price, 2, ',', '.');
    }
}

// Função para upload de imagens
function uploadImage($file, $target_dir = 'produtos/') {
    $target_file = UPLOAD_DIR . $target_dir . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Verifica se é uma imagem real
    if(isset($_POST["submit"])) {
        $check = getimagesize($file["tmp_name"]);
        if($check !== false) {
            $uploadOk = 1;
        } else {
            return "O arquivo não é uma imagem.";
        }
    }

    // Verifica o tamanho
    if ($file["size"] > MAX_FILE_SIZE) {
        return "Arquivo muito grande.";
    }

    // Permite apenas alguns formatos
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        return "Apenas arquivos JPG, JPEG & PNG são permitidos.";
    }

    if ($uploadOk == 0) {
        return "Erro ao fazer upload.";
    } else {
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return $target_dir . basename($file["name"]);
        } else {
            return "Erro ao fazer upload do arquivo.";
        }
    }
}

// Função para verificar login
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
}

// Função para obter cor do status
if (!function_exists('getStatusColor')) {
    function getStatusColor($status) {
        switch ($status) {
            case 'pendente':
                return 'warning';
            case 'confirmado':
                return 'info';
            case 'preparando':
                return 'primary';
            case 'entregando':
                return 'info';
            case 'concluido':
                return 'success';
            case 'cancelado':
                return 'danger';
            default:
                return 'secondary';
        }
    }
}
?>