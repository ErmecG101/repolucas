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

// Lógica para exclusão
if (isset($_GET['excluir'])) {
    $id_leite = $_GET['excluir'];
    $sql_excluir = "DELETE FROM leite WHERE id_leite = ?";
    $stmt_excluir = $conn->prepare($sql_excluir);
    $stmt_excluir->bind_param("i", $id_leite);

    if ($stmt_excluir->execute()) {
        $mensagem = "Produção excluída com sucesso!";
    } else {
        $mensagem = "Erro ao excluir a produção: " . $stmt_excluir->error;
    }
    $stmt_excluir->close();
}

// Lógica para inserção no banco de dados
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quantidade = $_POST['quantidade'];
    $data_coleta = $_POST['data_coleta'];
    $id_animal = $_POST['id_animal'] ? $_POST['id_animal'] : NULL;

    // SQL para inserir leite
    $sql = "INSERT INTO leite (quantidade, data_coleta, id_animal) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $quantidade, $data_coleta, $id_animal);

    if ($stmt->execute()) {
        $mensagem = "Produção cadastrada com sucesso!";
    } else {
        $mensagem = "Erro ao cadastrar a Produção: " . $stmt->error;
    }

    $stmt->close();
}

// Consulta para obter a lista de animais
$sql_animais = "SELECT id_animal, nome FROM animais";
$result_animais = $conn->query($sql_animais);

// Lógica para paginação
$limite = 10; // Número de produtos por página
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

// Filtro de pesquisa
$pesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';
$pesquisa_sql = $pesquisa ? "WHERE id_leite LIKE '%$pesquisa%' OR quantidade LIKE '%$pesquisa%'" : "";

// Consulta para listar as produções cadastradas com paginação
$sql = "SELECT * FROM leite $pesquisa_sql LIMIT $limite OFFSET $offset";
$result = $conn->query($sql);

// Contagem total de produções para paginação
$sql_total = "SELECT COUNT(*) as total FROM leite $pesquisa_sql";
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
    <title>Cadastro de Produção</title>
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
                <!-- Formulário de cadastro de produção -->
                <div class="cliente-form">
                    <h1>Cadastro de Produção</h1>
                    <form action="produto_cadastro.php" method="POST">

                        <label for="quantidade">Quantidade em Litros:</label>
                        <input type="number" id="quantidade" name="quantidade" required><br>

                        <label for="data_coleta">Data da Coleta:</label>
                        <input type="date" id="data_coleta" name="data_coleta" required><br>

                        <label for="id_animal">Animal:</label>
                        <select id="id_animal" name="id_animal" required>
                            <option value="">Selecione um animal</option>
                            <?php if ($result_animais->num_rows > 0): ?>
                                <?php while($row_animal = $result_animais->fetch_assoc()): ?>
                                    <option value="<?= $row_animal['id_animal'] ?>"><?= $row_animal['nome'] ?></option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="">Nenhum animal cadastrado</option>
                            <?php endif; ?>
                        </select><br>

                        <!-- Botões "Cadastrar" e "Voltar" -->
                        <div class="button-group">
                            <button type="submit" class="button">Cadastrar</button>
                            <button type="button" onclick="window.location.href='../../index.php';" class="button">Voltar</button>
                        </div>
                    </form>
                </div>

                <!-- Lista de produções cadastradas -->
                <div class="cliente-list">
                    <h2>Lista de Produções</h2>

                    <!-- Campo de pesquisa -->

                        <?php echo gerarFormularioPesquisa('produto_cadastro.php', 'Pesquisar por nome ou espécie', 'Pesquisar', 'Voltar');?>

                    <!-- Tabela de produções -->
                    <?php if ($result->num_rows > 0): ?>
                        <table>
                            <tr>
                                <th>Código</th>
                                <th>Quantidade</th>
                                <th>Data da Coleta</th>
                                <th>Animal</th>
                                <th>Ações</th>
                            </tr>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr> 
                                <td><?= $row['id_leite'] ?></td>
                                <td><?= $row['quantidade'] ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['data_coleta'])); ?></td>
                                <td><?= $row['id_animal'] ?></td>
                                <td>
                                    <a href="produto_alterar.php?id=<?= $row['id_leite'] ?>" class="button">Alterar</a>
                                    <?php echo gerarBotaoExcluirAnimal($row['id_leite'], $tokenCSRF);?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </table>

                    <?php else: ?>
                        <p>Nenhuma Produção cadastrada.</p>
                    <?php endif; ?>

                    <!-- Paginação -->
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="produto_cadastro.php?pagina=<?php echo $pagina - 1; ?>&pesquisa=<?php echo $pesquisa; ?>" class="button">Página Anterior</a>
                        <?php endif; ?>
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="produto_cadastro.php?pagina=<?php echo $pagina + 1; ?>&pesquisa=<?php echo $pesquisa; ?>" class="button">Próxima Página</a>
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

