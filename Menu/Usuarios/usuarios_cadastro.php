<?php
include_once '../toolskit.php';

session_start(); // Inicia a sessão

// Verifica o login antes de continuar
verificaLogin();

// conexao com o banco
$conn = conexao();

$tokenCSRF = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $tokenCSRF;

$mensagem = "";
$tipo_mensagem = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $data_criacao = date('Y-m-d'); // Data atual
    
    if (isset($_POST['tipo_usuario']) && !empty($_POST['tipo_usuario'])) {
        $tipo_usuario = $_POST['tipo_usuario'];
    } else {
        $mensagem = "Erro: Tipo de usuário não foi selecionado.";
        $tipo_mensagem = "erro";
    }

    if (empty($mensagem)) {
        $check_email = "SELECT * FROM usuarios WHERE email = ?";
        $stmt_check = $conn->prepare($check_email);
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $mensagem = "Este email já está cadastrado.";
            $tipo_mensagem = "erro";
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios (nome, email, senha, data_criacao, tipo_usuario) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $nome, $email, $senha_hash, $data_criacao, $tipo_usuario);

            if ($stmt->execute()) {
                $mensagem = "Usuário cadastrado com sucesso!";
                $tipo_mensagem = "sucesso";
            } else {
                $mensagem = "Erro ao cadastrar o usuário: " . $stmt->error;
                $tipo_mensagem = "erro";
            }

            $stmt->close();
        }
        $stmt_check->close();
    }
}

if (isset($_GET['excluir'])) {
    $id_usuario = $_GET['excluir'];
    $sql = "DELETE FROM usuarios WHERE id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    
    if ($stmt->execute()) {
        $mensagem = "Usuário excluído com sucesso!";
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Erro ao excluir o usuário: " . $stmt->error;
        $tipo_mensagem = "erro";
    }
    
    $stmt->close();
}

$limite = 8;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

$pesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';
$pesquisa_sql = $pesquisa ? "WHERE id_usuario LIKE '%$pesquisa%' OR nome LIKE '%$pesquisa%'" : "";

$sql = "SELECT * FROM usuarios $pesquisa_sql LIMIT $limite OFFSET $offset";
$result = $conn->query($sql);

$sql_total = "SELECT COUNT(*) as total FROM usuarios $pesquisa_sql";
$result_total = $conn->query($sql_total);
$total_registros = $result_total->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $limite);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilo.css">
    <script src="js/script.js"></script>
    <title>Cadastro de Usuários</title>
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

    <nav class="navbar">
        <ul>
            <?php
                if (isset($_SESSION['usuario'])) {
                    $tipoUsuario = $_SESSION['usuario']['tipo_usuario'];
                    echo gerarMenuNavegacao($tipoUsuario);
                }
            ?>
        </ul>
    </nav>
    
    <div class="container">
        <div class="content">
            <div class="form-list-container">
                <div class="cliente-form">
                    <h1>Cadastro de Usuários</h1>
                    <form action="usuarios_cadastro.php" method="POST">

                        <label for="nome">Nome:</label>
                        <input type="text" id="nome" name="nome" required><br>

                        <label for="email">Email:</label>
                        <input type="text" id="email" name="email"><br>

                        <label for="senha">Senha:</label>
                        <input type="password" id="senha" name="senha"><br>

                        <label for="data_criacao">Data de Cadastro:</label>
                        <input type="date" id="data_criacao" name="data_criacao" value="<?php echo date('Y-m-d'); ?>" readonly><br>

                        <label for="tipo_usuario">Tipo de Usuário:</label>
                        <select id="tipo_usuario" name="tipo_usuario" required>
                            <option value="" disabled selected>Selecione...</option>
                            <option value="usuario">usuario</option>
                            <option value="admin">admin</option>
                        </select>

                        <div class="button-group">
                            <button type="submit" class="button">Cadastrar</button>
                            <a href="../../index.php" class="button">Voltar</a>
                        </div>
                    </form>
                </div>

                <div class="cliente-list">
                    <h2>Lista de Usuários</h2>

                    <!-- função gerar botoes de pesquisda-->
                    <?php echo gerarFormularioPesquisa('usuarios_cadastro.php', 'Pesquisar por nome ou espécie', 'Pesquisar', 'Voltar');?>

                    <?php if ($result->num_rows > 0): ?>
                        <table>
                            <tr>
                                <th>Código</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Data de Criação</th>
                                <th>Tipo de Usuário</th>
                                <th>Ações</th>
                            </tr>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id_usuario'] ?></td>
                                <td><?= $row['nome'] ?></td>
                                <td><?= $row['email'] ?></td>
                                <td><?= date('d/m/Y', strtotime($row['data_criacao'])) ?></td>
                                <td><?= $row['tipo_usuario'] ?></td>
                                <td>
                                    <a href="usuarios_editar.php?id=<?= $row['id_usuario'] ?>" class="button">Alterar</a>
                                    <?php echo gerarBotaoExcluirAnimal($row['id_usuario'], $tokenCSRF);?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p>Nenhum usuário cadastrado.</p>
                    <?php endif; ?>

                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="usuarios_cadastro.php?pagina=<?php echo $pagina - 1; ?>&pesquisa=<?php echo $pesquisa; ?>" class="button">Página Anterior</a>
                        <?php endif; ?>
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="usuarios_cadastro.php?pagina=<?php echo $pagina + 1; ?>&pesquisa=<?php echo $pesquisa; ?>" class="button">Próxima Página</a>
                        <?php endif; ?>
                    </div>

                    <?php if ($mensagem): ?>
                        <div class="message <?php echo $tipo_mensagem; ?>">
                            <?php echo $mensagem; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>