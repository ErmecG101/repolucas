<?php
include_once '../toolskit.php';

session_start(); // Inicia a sessão

// Verifica o login antes de continuar
verificaLogin();

// conexao com o banco
$conn = conexao();

$mensagem = "";

// Função para validar CPF
function validaCPF($cpf) {
    // Remove caracteres especiais
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se o CPF tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }

    // Verifica se todos os números são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Calcula os dígitos verificadores
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

// Obtendo dados do cliente para edição
$id_cliente = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = "SELECT * FROM clientes WHERE id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();

// Lógica para editar o cliente
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $endereco = $_POST['endereco'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];

    // Validação do CPF
    if (!validaCPF($cpf)) {
        $mensagem = "CPF inválido!";
    } else {
        // Verificar se o CPF já está cadastrado em outro cliente
        $sql_cpf = "SELECT * FROM clientes WHERE cpf = ? AND id_cliente != ?";
        $stmt_cpf = $conn->prepare($sql_cpf);
        $stmt_cpf->bind_param("si", $cpf, $id_cliente);
        $stmt_cpf->execute();
        $result_cpf = $stmt_cpf->get_result();

        if ($result_cpf->num_rows > 0) {
            $mensagem = "Já existe um cliente com esse CPF cadastrado!";
        } else {
            // Atualizando o cliente no banco de dados
            $sql_update = "UPDATE clientes SET nome = ?, cpf = ?, endereco = ?, telefone = ?, email = ? WHERE id_cliente = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("sssssi", $nome, $cpf, $endereco, $telefone, $email, $id_cliente);

            if ($stmt_update->execute()) {
                $mensagem = "Cliente atualizado com sucesso!";
            } else {
                $mensagem = "Erro ao atualizar o cliente: " . $stmt_update->error;
            }

            $stmt_update->close();
        }
        $stmt_cpf->close();
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="estilo.css">
    <script src="js/script.js"></script>
    <title>Editar Cliente</title>
</head>
<body>
    <div class="container">
        <div class="content">
            <div class="form-list-container">
                <div class="cliente-form-editar">
                    <h1>Editar Cliente</h1>
                    <form action="clientes_editar.php?id=<?= $id_cliente ?>" method="POST">
                        <label for="nome">Nome:</label>
                        <input type="text" id="nome" name="nome" value="<?= $cliente['nome'] ?>" required><br>

                        <label for="cpf">CPF:</label>
                        <input type="text" id="cpf" name="cpf" value="<?= $cliente['cpf'] ?>" 
                               placeholder="Digite o CPF" required 
                               oninput="validarCPF(this)" maxlength="11"> 
                               <span id="cpf-error" class="error-message">Somente números são permitidos e até 11 dígitos!</span><br>

                        <label for="endereco">Endereço:</label>
                        <input type="text" id="endereco" name="endereco" value="<?= $cliente['endereco'] ?>"><br>

                        <label for="telefone">Telefone:</label>
                        <input type="text" id="telefone" name="telefone" value="<?= $cliente['telefone'] ?>"><br>

                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= $cliente['email'] ?>"><br>

                        <div class="button-group">
                            <button type="submit" class="button">Salvar alterações</button>
                            <button type="button" onclick="window.location.href='clientes_cadastro.php';" class="button">Voltar</button>
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
