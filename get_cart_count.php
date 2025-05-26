<?php
require_once 'config/config.php';

session_start();

$count = 0;
if (isset($_SESSION['carrinho']) && is_array($_SESSION['carrinho'])) {
    $count = array_sum($_SESSION['carrinho']);
}

header('Content-Type: application/json');
echo json_encode(['count' => $count]);