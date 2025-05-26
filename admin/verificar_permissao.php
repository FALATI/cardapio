<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado e tem permissão de admin
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'admin') {
    // Salvar URL atual para redirecionamento após login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirecionar para página de login
    header('Location: login.php');
    exit;
}

// Buscar informações do usuário logado
require_once '../config/config.php';

$sql = "SELECT nome, email FROM usuarios WHERE id = ? AND tipo = 'admin' AND status = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Usuário não encontrado ou sem permissão
    session_destroy();
    header('Location: login.php');
    exit;
}

$usuario = $result->fetch_assoc();