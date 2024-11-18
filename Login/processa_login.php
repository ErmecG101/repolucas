<?php
session_start();
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Conexão com o banco de dados
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Conexão falhou: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();

        // Verifica a senha utilizando password_verify
        if (password_verify($senha, $usuario['senha'])) {
            // Armazena os dados do usuário na sessão
            $_SESSION['usuario'] = [
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'tipo_usuario' => $usuario['tipo_usuario'] // Adiciona o tipo de usuário à sessão
            ];
            header("Location: ../index.php");
        } else {
            $_SESSION['login_erro'] = "Usuário ou senha inválidos";
            header("Location: login.php");
        }
    } else {
        $_SESSION['login_erro'] = "Usuário ou senha inválidos";
        header("Location: login.php");
    }

    $stmt->close();
    $conn->close();
}
?>
