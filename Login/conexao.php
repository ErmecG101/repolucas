<?php
$servername = "localhost";  // Ou o endereço do seu servidor
$username = "root";  // Seu nome de usuário do banco de dados
$password = "";    // Sua senha do banco de dados
$dbname = "sistema_agropecuario";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}
?>
