<?php
require_once 'config/config.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = cleanInput($_POST['nome']);
    $email = cleanInput($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nome, $email, $senha);

    if ($stmt->execute()) {
        $sucesso = "Cadastro realizado com sucesso!";
    } else {
        $erro = "Erro ao realizar cadastro.";
    }
}
?>

<!DOCTYPE html>
<!-- ... HTML do formulário de cadastro ... -->