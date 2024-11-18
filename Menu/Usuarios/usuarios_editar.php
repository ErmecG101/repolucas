<?php
include_once '../toolskit.php';

session_start(); // Inicia a sessão

// Verifica o login antes de continuar
verificaLogin();

// conexao com o banco
$conn = conexao();

$mensagem = "";
$tipo_mensagem = "";

if (isset($_GET['id'])) {
    $id_usuario = $_GET['id'];
    $sql = "SELECT * FROM usuarios WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
    } else {
        die("Usuário não encontrado.");
    }

    $stmt->close();
} else {
    die("ID de usuário não fornecido.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $data_criacao = $_POST['data_criacao'];
    
    if (isset($_POST['tipo_usuario']) && !empty($_POST['tipo_usuario'])) {
        $tipo_usuario = $_POST['tipo_usuario'];
    } else {
        $mensagem = "Por favor, selecione um tipo de usuário válido!";
        $tipo_mensagem = "erro";
    }

    if (empty($mensagem)) {
        // Verifica se o email já existe (excluindo o usuário atual)
        $check_email = "SELECT * FROM usuarios WHERE email = ? AND id_usuario != ?";
        $stmt_check = $conn->prepare($check_email);
        $stmt_check->bind_param("si", $email, $id_usuario);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $mensagem = "Este email já está cadastrado.";
            $tipo_mensagem = "erro";
        } else {
            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nome = ?, email = ?, senha = ?, data_criacao = ?, tipo_usuario = ? WHERE id_usuario = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $nome, $email, $senha_hash, $data_criacao, $tipo_usuario, $id_usuario);
            } else {
                $sql = "UPDATE usuarios SET nome = ?, email = ?, data_criacao = ?, tipo_usuario = ? WHERE id_usuario = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $nome, $email, $data_criacao, $tipo_usuario, $id_usuario);
            }

            if ($stmt->execute()) {
                $mensagem = "Usuário atualizado com sucesso!";
                $tipo_mensagem = "sucesso";
            } else {
                $mensagem = "Erro ao atualizar o usuário: " . $stmt->error;
                $tipo_mensagem = "erro";
            }

            $stmt->close();
        }
        $stmt_check->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilo.css">
    <script src="js/script.js"></script>
    <title>Editar Usuário</title>
    <style>
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: left;
        }
        .erro {
            color: red;
        }
        .sucesso {
            color: green;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <div class="form-list-container">
                <div class="cliente-form-editar">
                    <h1>Editar Usuário</h1>
                    <form action="usuarios_editar.php?id=<?= $id_usuario ?>" method="POST">

                        <label for="nome">Nome:</label>
                        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required><br>

                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>"><br>

                        <label for="senha">Senha (Deixe em branco para não alterar):</label>
                        <input type="password" id="senha" name="senha" placeholder="Digite uma nova senha se quiser alterar"><br>

                        <label for="data_criacao">Data de Cadastro:</label>
                        <input type="datetime" id="data_criacao" name="data_criacao" value="<?= htmlspecialchars($usuario['data_criacao']) ?>"><br>

                        <label for="tipo_usuario">Tipo de Usuário:</label>
                        <select id="tipo_usuario" name="tipo_usuario" required>
                            <option value="" disabled>Selecione...</option>
                            <option value="usuario" <?= ($usuario['tipo_usuario'] == 'usuario') ? 'selected' : '' ?>>usuario</option>
                            <option value="admin" <?= ($usuario['tipo_usuario'] == 'admin') ? 'selected' : '' ?>>admin</option>
                        </select>

                        <div class="button-group">
                            <button type="submit" class="button">Salvar Alterações</button>
                            <a href="usuarios_cadastro.php" class="button">Voltar</a>
                        </div>
                    </form>

                    <?php if ($mensagem): ?>
                        <div class="message <?php echo $tipo_mensagem; ?>">
                            <?php echo htmlspecialchars($mensagem); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>