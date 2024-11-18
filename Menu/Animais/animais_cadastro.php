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

// Lógica para inserção no banco de dados
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $especie = $_POST['especie'];
    $raca = $_POST['raca'];
    $data_nascimento = $_POST['data_nascimento'];
    // Verifica se id_cliente está definido e não é vazio
    $id_cliente = isset($_POST['id_cliente']) && $_POST['id_cliente'] !== '' ? $_POST['id_cliente'] : NULL;

    // SQL para inserir animal
    $sql = "INSERT INTO animais (nome, especie, raca, data_nascimento, id_cliente) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nome, $especie, $raca, $data_nascimento, $id_cliente);

    if ($stmt->execute()) {
        $mensagem = $id_cliente === NULL ? "Animal cadastrado com sucesso sem cliente associado!" : "Animal cadastrado com sucesso!";
    } else {
        $mensagem = "Erro ao cadastrar o animal: " . $stmt->error;
    }

    $stmt->close();
}

// Lógica para excluir um animal
if (isset($_GET['excluir'])) {
    $id_animal = (int)$_GET['excluir'];
    $sql_excluir = "DELETE FROM animais WHERE id_animal = ?";
    $stmt = $conn->prepare($sql_excluir);
    $stmt->bind_param("i", $id_animal);

    if ($stmt->execute()) {
        $mensagem = "Animal excluído com sucesso!";
    } else {
        $mensagem = "Erro ao excluir o animal: " . $stmt->error;
    }

    $stmt->close();
}

// Lógica para preencher o campo select com os clientes
$sql_clientes = "SELECT id_cliente, nome FROM clientes";
$result_clientes = $conn->query($sql_clientes);

// Lógica para paginação
$limite = 10; // Número de animais por página
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

// Filtro de pesquisa
$pesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';
$pesquisa_sql = $pesquisa ? "WHERE nome LIKE '%$pesquisa%' OR especie LIKE '%$pesquisa%'" : "";

// Consulta para listar os animais cadastrados com paginação
$sql = "SELECT * FROM animais $pesquisa_sql LIMIT $limite OFFSET $offset";
$result = $conn->query($sql);

// Contagem total de animais para paginação
$sql_total = "SELECT COUNT(*) as total FROM animais $pesquisa_sql";
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
    <title>Cadastro de Animais</title>
    <link rel="stylesheet" href="estilo.css">
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
                <!-- Formulário de cadastro de animais -->
                <div class="animal-form">
                    <h1>Cadastro de Animais</h1>
                    <form action="animais_cadastro.php" method="POST">
                        <label for="nome">Nome:</label>
                        <input type="text" id="nome" name="nome" required><br>

                        <label for="especie">Espécie:</label>
                        <input type="text" id="especie" name="especie" required><br>

                        <label for="raca">Raça:</label>
                        <input type="text" id="raca" name="raca"><br>

                        <label for="data_nascimento">Data de Nascimento:</label>
                        <input type="date" id="data_nascimento" name="data_nascimento"><br>

                        <!-- Campo de seleção para o cliente associado -->
                        <label for="id_cliente">Cliente Associado (opcional):</label>
                        <select id="id_cliente" name="id_cliente">
                            <option value="">Selecione um cliente</option>
                            <?php while ($row_cliente = $result_clientes->fetch_assoc()): ?>
                                <option value="<?php echo $row_cliente['id_cliente']; ?>">
                                    <?php echo $row_cliente['nome']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select><br>

                        <!-- Botões "Cadastrar" e "Voltar" -->
                        <div class="button-group">
                            <button type="submit" class="button">Cadastrar</button>
                            <a href="../../index.php" class="button">Voltar</a>
                        </div>
                    </form>
                </div>

                <!-- Lista de animais cadastrados -->
                <div class="animal-list">
                    <h2>Lista de Animais</h2>

                    <!-- Campo de pesquisa -->

                    <?php echo gerarFormularioPesquisa('animais_cadastro.php', 'Pesquisar por nome ou espécie', 'Pesquisar', 'Voltar');?>

                    <!-- Tabela de animais -->
                    <?php if ($result->num_rows > 0): ?>
                        <table>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Espécie</th>
                                <th>Raça</th>
                                <th>Data de Nascimento</th>
                                <th>Cliente</th>
                                <th>Ações</th>
                            </tr>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id_animal']; ?></td>
                                    <td><?php echo $row['nome']; ?></td>
                                    <td><?php echo $row['especie']; ?></td>
                                    <td><?php echo $row['raca']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['data_nascimento'])); ?></td>
                                    <td><?php echo $row['id_cliente'] ? $row['id_cliente'] : ''; ?></td>
                                    <td>
                                        <a href="animais_editar.php?id=<?php echo $row['id_animal']; ?>" class="button">Alterar</a>
                                        <?php echo gerarBotaoExcluirAnimal($row['id_animal'], $tokenCSRF);?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php else: ?>
                        <p>Nenhum animal cadastrado.</p>
                    <?php endif; ?>

                    <!-- Paginação -->
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="animais_cadastro.php?pagina=<?php echo $pagina - 1; ?>&pesquisa=<?php echo $pesquisa; ?>" class="button">Página Anterior</a>
                        <?php endif; ?>
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="animais_cadastro.php?pagina=<?php echo $pagina + 1; ?>&pesquisa=<?php echo $pesquisa; ?>" class="button">Próxima Página</a>
                        <?php endif; ?>
                    </div>

                    <!-- Mensagem de sucesso ou erro -->
                    <?php if ($mensagem): ?>
                        <div class="message success"><?php echo $mensagem; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
