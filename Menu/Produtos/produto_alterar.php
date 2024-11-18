<?php
include_once '../toolskit.php';

session_start(); // Inicia a sessão

// Verifica o login antes de continuar
verificaLogin();

// conexao com o banco
$conn = conexao();

$mensagem = "";
$id_leite = $_GET['id'];

// Consulta para obter os dados do registro de produção para edição
$sql = "SELECT * FROM leite WHERE id_leite = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_leite);
$stmt->execute();
$result = $stmt->get_result();
$dados = $result->fetch_assoc();

// Lógica para atualização de produção
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quantidade = $_POST['quantidade'];
    $data_coleta = $_POST['data_coleta'];
    $id_animal = $_POST['id_animal'];

    $sql_update = "UPDATE leite SET quantidade = ?, data_coleta = ?, id_animal = ? WHERE id_leite = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssii", $quantidade, $data_coleta, $id_animal, $id_leite);

    if ($stmt_update->execute()) {
        $mensagem = "Produção alterada com sucesso!";
        // Comentando o redirecionamento para que a mensagem seja exibida
        // header("Location: produto_cadastro.php");
    } else {
        $mensagem = "Erro ao alterar a Produção: " . $stmt_update->error;
    }
}

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
    <title>Alterar Produção</title>
</head>
<body>
    <div class="container">
        <div class="content">
            <div class="form-list-container">
                <div class="cliente-form-editar">
                    <h1>Alterar Produção</h1>
                    <form action="produto_alterar.php?id=<?= $id_leite ?>" method="POST">
                        <label for="quantidade">Quantidade em Litros:</label>
                        <input type="number" id="quantidade" name="quantidade" value="<?= $dados['quantidade'] ?>" required><br>

                        <label for="data_coleta">Data da Coleta:</label>
                        <input type="date" id="data_coleta" name="data_coleta" value="<?= $dados['data_coleta'] ?>" required><br>

                        <label for="id_animal">Animal:</label>
                        <select id="id_animal" name="id_animal" required>
                            <?php if ($result_animais->num_rows > 0): ?>
                                <?php while($row_animal = $result_animais->fetch_assoc()): ?>
                                    <option value="<?= $row_animal['id_animal'] ?>" <?= $dados['id_animal'] == $row_animal['id_animal'] ? 'selected' : '' ?>>
                                        <?= $row_animal['nome'] ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="">Nenhum animal cadastrado</option>
                            <?php endif; ?>
                        </select><br>

                        <!-- Botões "Salvar" e "voltar" -->
                        <div class="button-group">
                            <button type="submit" class="button">Salvar Alterações</button>
                            <button type="button" onclick="window.location.href='produto_cadastro.php';" class="button">Voltar</button>
                        </div>
                    </form>

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
