<?php
include_once '../toolskit.php';

session_start(); // Inicia a sessão

// Verifica o login antes de continuar
verificaLogin();

// Conexão com o banco
$conn = conexao();

// Verificando a conexão
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Obtendo os dados do animal para edição
if (isset($_GET['id'])) {
    $id_animal = (int)$_GET['id'];
    $sql = "SELECT * FROM animais WHERE id_animal = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_animal);
    $stmt->execute();
    $result = $stmt->get_result();
    $animal = $result->fetch_assoc();
    $stmt->close();
}

// Lógica para atualização do animal
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $especie = $_POST['especie'];
    $raca = $_POST['raca'];
    $data_nascimento = $_POST['data_nascimento'];

    $sql_update = "UPDATE animais SET nome = ?, especie = ?, raca = ?, data_nascimento = ? WHERE id_animal = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("ssssi", $nome, $especie, $raca, $data_nascimento, $id_animal);

    if ($stmt->execute()) {
        header("Location: animais_editar.php?id=$id_animal&mensagem=Animal atualizado com sucesso");
        exit(); // Adiciona exit() para garantir que o script pare após o redirecionamento
    } else {
        echo "Erro ao atualizar o animal: " . $stmt->error;
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
    <title>Editar Animal</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>

<div class="container">
    <div class="content">
        <div class="form-list-container">
            <div class="animal-form-editar">
                <!-- Formulário de edição de animais -->
                <h1>Editar Animal</h1>

                <!-- Exibe a mensagem de sucesso, se existir -->

                <form action="animais_editar.php?id=<?php echo $id_animal; ?>" method="POST">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo $animal['nome']; ?>" required><br>

                    <label for="especie">Espécie:</label>
                    <input type="text" id="especie" name="especie" value="<?php echo $animal['especie']; ?>" required><br>

                    <label for="raca">Raça:</label>
                    <input type="text" id="raca" name="raca" value="<?php echo $animal['raca']; ?>"><br>

                    <label for="data_nascimento">Data de Nascimento:</label>
                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo $animal['data_nascimento']; ?>"><br>

                    <label for="id_cliente">Cliente:</label>
                    <select id="id_cliente" name="id_cliente">
                        <?php if ($result_animais->num_rows > 0): ?>
                            <?php while($row_animal = $result_animais->fetch_assoc()): ?>
                                <option value="<?= $row_animal['id_cliente'] ?>" <?= $dados['id_cliente'] == $row_animal['id_cliente'] ? 'selected' : '' ?>>
                                    <?= $row_animal['nome'] ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="">Nenhum Cliente Associado</option>
                        <?php endif; ?>
                    </select><br>

                    <!-- Botões "Salvar" e "Voltar" -->
                    <div class="button-group">
                        <button type="submit" class="button">Salvar alterações</button>
                        <button type="button" onclick="window.location.href='animais_cadastro.php';" class="button">Voltar</button>
                    </div>
                </form>
                <?php if (isset($_GET['mensagem'])): ?>
                    <p class="mensagem-sucesso"><?php echo htmlspecialchars($_GET['mensagem']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
