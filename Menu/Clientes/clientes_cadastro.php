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

// Função para validar o CPF
function validarCPF($cpf) {
    // Remove caracteres não numéricos
    $cpf = preg_replace('/[^0-9]/is', '', $cpf);

    // Verifica se foi informado todos os dígitos
    if (strlen($cpf) != 11) {
        return false;
    }

    // Evita CPFs conhecidos como inválidos
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Calcula os dígitos verificadores para verificar se o CPF é válido
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

$nome = $cpf = $endereco = $telefone = $email = ""; // Inicializando variáveis

// Lógica para inserção no banco de dados com validação de CPF
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $endereco = $_POST['endereco'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];

    // Validação do CPF
    if (!validarCPF($cpf)) {
        $mensagem = "CPF inválido.";
    } else {
        // Verificar se o CPF já está cadastrado
        $sql_cpf = "SELECT * FROM clientes WHERE cpf = ?";
        $stmt_cpf = $conn->prepare($sql_cpf);
        $stmt_cpf->bind_param("s", $cpf);
        $stmt_cpf->execute();
        $result_cpf = $stmt_cpf->get_result();

        if ($result_cpf->num_rows > 0) {
            $mensagem = "CPF já cadastrado.";
        } else {
            // SQL para inserir cliente
            $sql = "INSERT INTO clientes (nome, cpf, endereco, telefone, email) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $nome, $cpf, $endereco, $telefone, $email);

            if ($stmt->execute()) {
                $mensagem = "Cliente cadastrado com sucesso!";
                // Limpar os campos
                $nome = $cpf = $endereco = $telefone = $email = "";
            } else {
                $mensagem = "Erro ao cadastrar o cliente: " . $stmt->error;
            }

            $stmt->close();
        }
        $stmt_cpf->close();
    }
}

// Lógica para excluir cliente
if (isset($_GET['excluir'])) {
    $id_cliente = $_GET['excluir'];
    $sql_delete = "DELETE FROM clientes WHERE id_cliente = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_cliente);

    if ($stmt_delete->execute()) {
        $mensagem = "Cliente excluído com sucesso.";
    } else {
        $mensagem = "Erro ao excluir o cliente.";
    }
    $stmt_delete->close();
}

// Lógica para paginação
$limite = 8; // Número de clientes por página
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

// Filtro de pesquisa
$pesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';
$pesquisa_sql = $pesquisa ? "WHERE nome LIKE '%$pesquisa%' OR cpf LIKE '%$pesquisa%'" : "";

// Consulta para listar os clientes cadastrados com paginação
$sql = "SELECT * FROM clientes $pesquisa_sql LIMIT $limite OFFSET $offset";
$result = $conn->query($sql);

// Contagem total de clientes para paginação
$sql_total = "SELECT COUNT(*) as total FROM clientes $pesquisa_sql";
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
    <title>Cadastro de Clientes</title>
</head>
<body>
    
     <!-- Menu de navegação no topo -->
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
                <!-- Formulário de cadastro de clientes -->
                <div class="cliente-form">
                    <h1>Cadastro de Clientes</h1>
                    <form action="clientes_cadastro.php" method="POST">
                        <label for="nome">Nome:</label>
                        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome); ?>" required><br>

                        <label for="cpf">CPF:</label>
                        <input type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($cpf); ?>" required
                               oninput="validarCPF(this)" maxlength="11" placeholder="Somente números">
                               <span id="cpf-error" class="error-message"></span><br>
                        
                        <label for="endereco">Endereço:</label>
                        <input type="text" id="endereco" name="endereco" value="<?= htmlspecialchars($endereco); ?>"><br>

                        <label for="telefone">Telefone:</label>
                        <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($telefone); ?>"><br>

                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($email); ?>"><br>

                        <div class="button-group">
                            <button type="submit" class="button">Cadastrar</button>
                            <button type="button" onclick="window.location.href='../../index.php';" class="button">Voltar</button>
                        </div>
                    </form>
                </div>

                <!-- Lista de clientes cadastrados -->
                <div class="cliente-list">
                    <h2>Lista de Clientes</h2>

                    <!-- Campo de pesquisa -->

                    <?php echo gerarFormularioPesquisa('clientes_cadastro.php', 'Pesquisar por nome ou espécie', 'Pesquisar', 'Voltar');?>

                    <!-- Tabela de clientes -->
                    <?php if ($result->num_rows > 0): ?>
                        <table>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Endereço</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Ações</th>
                            </tr>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id_cliente'] ?></td>
                                <td><?= $row['nome'] ?></td>
                                <td><?= $row['cpf'] ?></td>
                                <td><?= $row['endereco'] ?></td>
                                <td><?= $row['telefone'] ?></td>
                                <td><?= $row['email'] ?></td>
                                <td>
                                    <a class="button" href="clientes_editar.php?id=<?= $row['id_cliente'] ?>">Alterar</a>
                                    <?php echo gerarBotaoExcluirAnimal($row['id_cliente'], $tokenCSRF);?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p>Nenhum cliente cadastrado.</p>
                    <?php endif; ?>

                    <!-- Paginação -->
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="clientes_cadastro.php?pagina=<?php echo $pagina - 1; ?>&pesquisa=<?php echo $pesquisa; ?>" class="button">Página Anterior</a>
                        <?php endif; ?>
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="clientes_cadastro.php?pagina=<?php echo $pagina + 1; ?>&pesquisa=<?php echo $pesquisa; ?>" class="button">Próxima Página</a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Mensagem de status -->
                    <?php if (!empty($mensagem)): ?>
                        <p class="mensagem"><?= $mensagem ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
