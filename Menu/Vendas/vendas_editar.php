<?php
include_once '../toolskit.php';

session_start(); // Inicia a sessão

// Verifica o login antes de continuar
verificaLogin();

// conexao com o banco
$conn = conexao();

$mensagem = "";
$venda = array();

// Buscar clientes, usuários e animais para as caixas de seleção
$sql_clientes = "SELECT id_cliente, nome FROM clientes";
$result_clientes = $conn->query($sql_clientes);

$sql_usuarios = "SELECT id_usuario, nome FROM usuarios";
$result_usuarios = $conn->query($sql_usuarios);

$sql_animais = "SELECT id_animal, nome FROM animais";
$result_animais = $conn->query($sql_animais);

// Verificar se o ID da venda foi passado via GET
if (isset($_GET['id'])) {
    $id_venda = $_GET['id'];

    // Buscar os dados da venda no banco de dados
    $sql = "
        SELECT 
            vendas.data_venda, 
            vendas.quantidade, 
            vendas.valor_unitario, 
            vendas.id_cliente, 
            vendas.id_usuario, 
            vendas.id_animal
        FROM vendas 
        WHERE id_venda = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_venda);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Verificar se a venda existe
    if ($result->num_rows > 0) {
        $venda = $result->fetch_assoc();
    } else {
        die("Venda não encontrada.");
    }
    $stmt->close();
}

// Lógica para atualização dos dados
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $venda['data_venda'] = $_POST['data_venda'];
    $venda['quantidade'] = $_POST['quantidade'];
    $venda['valor_unitario'] = floatval($_POST['valor_unitario']); // Convertendo para float
    $venda['id_cliente'] = $_POST['id_cliente'];
    $venda['id_usuario'] = $_POST['id_usuario'];
    $venda['id_animal'] = $_POST['id_animal'];

    // Atualizar os dados da venda no banco de dados
    $sql = "
        UPDATE vendas 
        SET data_venda = ?, quantidade = ?, valor_unitario = ?, id_cliente = ?, id_usuario = ?, id_animal = ?
        WHERE id_venda = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sidiiii", $venda['data_venda'], $venda['quantidade'], $venda['valor_unitario'], $venda['id_cliente'], $venda['id_usuario'], $venda['id_animal'], $id_venda);

    if ($stmt->execute()) {
        $mensagem = "Venda atualizada com sucesso!";
    } else {
        $mensagem = "Erro ao atualizar a venda: " . $stmt->error;
    }

    $stmt->close();
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
    <title>Editar Venda</title>
</head>
<body>
    <div class="container">
        <div class="content">
            <div class="form-list-container">
                <!-- Formulário de edição de venda -->
                <div class="cliente-form-editar">
                    <h1>Editar Venda</h1>
                    <form action="vendas_editar.php?id=<?= $id_venda ?>" method="POST">

                        <label for="data_venda">Data da venda:</label>
                        <input type="datetime-local" id="data_venda" name="data_venda" value="<?= htmlspecialchars($venda['data_venda']) ?>" required><br>

                        <label for="quantidade">Quantidade:</label>
                        <input type="number" id="quantidade" name="quantidade" value="<?= htmlspecialchars($venda['quantidade']) ?>" required><br>

                        <label for="valor_unitario">Valor Unitário:</label>
                        <input type="number" step="0.01" id="valor_unitario" name="valor_unitario" value="<?= number_format($venda['valor_unitario'], 2, '.', '') ?>" required><br>

                        <label for="id_cliente">Cliente:</label>
                        <select id="id_cliente" name="id_cliente" required>
                            <?php 
                            $result_clientes->data_seek(0);
                            while ($cliente = $result_clientes->fetch_assoc()): 
                            ?>
                                <option value="<?= $cliente['id_cliente'] ?>" <?= $venda['id_cliente'] == $cliente['id_cliente'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cliente['nome']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select><br>

                        <label for="id_usuario">Usuário:</label>
                        <select id="id_usuario" name="id_usuario" required>
                            <?php 
                            $result_usuarios->data_seek(0);
                            while ($usuario = $result_usuarios->fetch_assoc()): 
                            ?>
                                <option value="<?= $usuario['id_usuario'] ?>" <?= $venda['id_usuario'] == $usuario['id_usuario'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($usuario['nome']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select><br>

                        <label for="id_animal">Animal:</label>
                        <select id="id_animal" name="id_animal" required>
                            <?php 
                            $result_animais->data_seek(0);
                            while ($animal = $result_animais->fetch_assoc()): 
                            ?>
                                <option value="<?= $animal['id_animal'] ?>" <?= $venda['id_animal'] == $animal['id_animal'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($animal['nome']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select><br>

                        <div class="button-group">
                            <button type="submit" class="button">Salvar Alterações</button>
                            <a href="vendas_cadastro.php" class="button">Voltar</a>
                        </div>
                    </form>

                    <!-- Mensagem de sucesso ou erro -->
                    <?php if ($mensagem): ?>
                        <div class="message success"><?= htmlspecialchars($mensagem) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>