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

// Função para validar existência de chaves estrangeiras
function validarExistencia($conn, $tabela, $coluna, $valor) {
    $sql = "SELECT COUNT(*) AS total FROM $tabela WHERE $coluna = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $valor);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] > 0;
}

// Lógica para excluir vendas
if (isset($_GET['excluir'])) {
    $id_venda = $_GET['excluir'];
    $sql = "DELETE FROM vendas WHERE id_venda = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_venda);

    if ($stmt->execute()) {
        $mensagem = "Venda excluída com sucesso!";
    } else {
        $mensagem = "Erro ao excluir a venda: " . $stmt->error;
    }
    $stmt->close();
}

// Lógica para inserção no banco de dados
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data_venda = isset($_POST['data_venda']) ? $_POST['data_venda'] : null;
    $quantidade = isset($_POST['quantidade']) ? $_POST['quantidade'] : null;
    $valor_unitario = isset($_POST['valor_unitario']) ? floatval($_POST['valor_unitario']) : null;
    $id_cliente = isset($_POST['id_cliente']) ? $_POST['id_cliente'] : null;
    $id_usuario = isset($_POST['id_usuario']) ? $_POST['id_usuario'] : null;
    $id_animal = isset($_POST['id_animal']) ? $_POST['id_animal'] : null;

    // Validar existência das chaves estrangeiras
    $usuario_existe = validarExistencia($conn, 'usuarios', 'id_usuario', $id_usuario);
    $cliente_existe = validarExistencia($conn, 'clientes', 'id_cliente', $id_cliente);
    $animal_existe = validarExistencia($conn, 'animais', 'id_animal', $id_animal);

    if (!$usuario_existe) {
        $mensagem = "Erro: Usuário com ID $id_usuario não existe.";
    } elseif (!$cliente_existe) {
        $mensagem = "Erro: Cliente com ID $id_cliente não existe.";
    } elseif (!$animal_existe) {
        $mensagem = "Erro: Animal com ID $id_animal não existe.";
    } elseif ($data_venda && $quantidade && $valor_unitario && $id_cliente && $id_usuario && $id_animal) {
        // Inserir no banco de dados somente se todas as chaves estrangeiras forem válidas
        $sql = "INSERT INTO vendas (data_venda, quantidade, valor_unitario, id_cliente, id_usuario, id_animal) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sidiii", $data_venda, $quantidade, $valor_unitario, $id_cliente, $id_usuario, $id_animal);

        if ($stmt->execute()) {
            $mensagem = "Venda cadastrada com sucesso!";
        } else {
            $mensagem = "Erro ao cadastrar a venda: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $mensagem = "Erro: Todos os campos devem ser preenchidos.";
    }
}

// Lógica para paginação
$limite = 10; // Número de registros por página
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina - 1) * $limite;

// Filtro de pesquisa
$pesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';
$pesquisa_sql = '';
if ($pesquisa) {
    $pesquisa = $conn->real_escape_string($pesquisa);
    $pesquisa_sql = "WHERE vendas.id_venda LIKE '%$pesquisa%' OR clientes.nome LIKE '%$pesquisa%' OR usuarios.nome LIKE '%$pesquisa%'";
}

// Consulta para listar as vendas cadastradas com nomes e paginação
$sql = "
    SELECT 
        vendas.id_venda,
        vendas.data_venda,
        vendas.quantidade,
        vendas.valor_unitario,
        clientes.nome AS nome_cliente,
        usuarios.nome AS nome_usuario,
        animais.nome AS nome_animal
    FROM 
        vendas
    JOIN 
        clientes ON vendas.id_cliente = clientes.id_cliente
    JOIN 
        usuarios ON vendas.id_usuario = usuarios.id_usuario
    JOIN 
        animais ON vendas.id_animal = animais.id_animal
    $pesquisa_sql
    ORDER BY vendas.id_venda DESC
    LIMIT $limite OFFSET $offset
";
$result = $conn->query($sql);

// Contagem total de vendas para paginação
$sql_total = "SELECT COUNT(*) as total FROM vendas
              JOIN clientes ON vendas.id_cliente = clientes.id_cliente
              JOIN usuarios ON vendas.id_usuario = usuarios.id_usuario
              JOIN animais ON vendas.id_animal = animais.id_animal
              $pesquisa_sql";
$result_total = $conn->query($sql_total);
$total_registros = $result_total->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $limite);

// Consultas para preencher as caixas de seleção
$sql_clientes = "SELECT id_cliente, nome FROM clientes";
$result_clientes = $conn->query($sql_clientes);

$sql_usuarios = "SELECT id_usuario, nome FROM usuarios";
$result_usuarios = $conn->query($sql_usuarios);

$sql_animais = "SELECT id_animal, nome FROM animais";
$result_animais = $conn->query($sql_animais);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilo.css">
    <script src="js/script.js"></script>
    <title>Cadastro de Vendas</title>
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
                <!-- Formulário de cadastro de vendas -->
                <div class="cliente-form">
                    <h1>Cadastro de Vendas</h1>
                    <form action="vendas_cadastro.php" method="POST">
                        <label for="data_venda">Data da venda:</label>
                        <input type="datetime-local" id="data_venda" name="data_venda" required><br>

                        <label for="quantidade">Quantidade (L):</label>
                        <input type="number" id="quantidade" name="quantidade" required><br>

                        <label for="valor_unitario">Valor Unitário:</label>
                        <input type="number" step="0.01" id="valor_unitario" name="valor_unitario" required><br>

                        <label for="id_cliente">Cliente:</label>
                        <select id="id_cliente" name="id_cliente" required>
                            <option value="">Selecione um cliente</option>
                            <?php while ($row = $result_clientes->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['id_cliente']) ?>"><?= htmlspecialchars($row['nome']) ?></option>
                            <?php endwhile; ?>
                        </select><br>

                        <label for="id_usuario">Usuário:</label>
                        <select id="id_usuario" name="id_usuario" required>
                            <option value="">Selecione um usuário</option>
                            <?php while ($row = $result_usuarios->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['id_usuario']) ?>"><?= htmlspecialchars($row['nome']) ?></option>
                            <?php endwhile; ?>
                        </select><br>

                        <label for="id_animal">Animal:</label>
                        <select id="id_animal" name="id_animal" required>
                            <option value="">Selecione um animal</option>
                            <?php while ($row = $result_animais->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['id_animal']) ?>"><?= htmlspecialchars($row['nome']) ?></option>
                            <?php endwhile; ?>
                        </select><br>

                        <!-- Botões "Cadastrar" e "Voltar" -->
                        <div class="button-group">
                            <button type="submit" class="button">Cadastrar</button>
                            <a href="../../index.php" class="button">Voltar</a>
                        </div>
                    </form>
                </div>

                <!-- Lista de vendas cadastradas -->
                <div class="cliente-list">
                    <h2>Lista de Vendas</h2>

                    <!-- Campo de pesquisa -->

                    <?php echo gerarFormularioPesquisa('vendas_cadastro.php', 'Pesquisar por nome ou espécie', 'Pesquisar', 'Voltar');?>

                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Data da Venda</th>
                            <th>Quantidade (L)</th>
                            <th>Valor Unitário</th>
                            <th>Cliente</th>
                            <th>Usuário</th>
                            <th>Animal</th>
                            <th>Ações</th>
                        </tr>

                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id_venda']) ?></td>
                                <td><?= htmlspecialchars($row['data_venda']) ?></td>
                                <td><?= htmlspecialchars($row['quantidade']) ?></td>
                                <td><?= number_format($row['valor_unitario'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($row['nome_cliente']) ?></td>
                                <td><?= htmlspecialchars($row['nome_usuario']) ?></td>
                                <td><?= htmlspecialchars($row['nome_animal']) ?></td>
                                <td>
                                    <a class="button" href="vendas_editar.php?id=<?= htmlspecialchars($row['id_venda']) ?>">Alterar</a>
                                    <?php echo gerarBotaoExcluirAnimal($row['id_venda'], $tokenCSRF);?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>

                    <!-- Paginação -->
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a class="button" href="vendas_cadastro.php?pagina=<?= $pagina - 1 ?>&pesquisa=<?= htmlspecialchars($pesquisa) ?>">Página Anterior</a>
                        <?php endif; ?>

                        <?php if ($pagina < $total_paginas): ?>
                            <a class="button" href="vendas_cadastro.php?pagina=<?= $pagina + 1 ?>&pesquisa=<?= htmlspecialchars($pesquisa) ?>">Próxima Página</a>
                        <?php endif; ?>
                    </div>

                    <!-- Mensagem de sucesso ou erro -->
                    <?php if ($mensagem): ?>
                        <p class="message success"><?= htmlspecialchars($mensagem) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>