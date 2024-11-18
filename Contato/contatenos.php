<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="style.css">
    <title>Contate-nos - Sistema Agropecuário</title>
</head>
<body>

<nav>
    <ul>
        <li><a class="logo-icon" href="../../index.php"><img src="../imagens/icons/Logo.ico" alt="" class="icon"></a></li>
        <li><a href="../index.php" target="_blank">Home</a></li>
        <li><a href="../Sobrenos/sobrenos.html" target="_blank">Fale Conosco</a></li>
    </ul>
</nav>

<div class="contact-container">
    <div class="contact-info">
        <div class="text-container">
            <h1>Vamos conversar.<br>Conte-nos sobre como podemos ajudar.</h1>
            <p>Se você tiver alguma dúvida, sugestão ou precisa de ajuda com nossos serviços, não hesite em entrar em contato. Preencha o formulário e retornaremos o mais rápido possível.</p>
            <p>Envie um email para <a href="mailto:milkflow@milkflow.com">milkflow@milkflow.com</a></p>
        </div>
    </div>

    <div class="contact-form">
        <h2>Envie-nos uma mensagem🚀</h2>

        <?php
        include_once 'toolskit.php';
        $mensagemStatus = "";

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nome = $_POST['nome'];
            $email = $_POST['email'];
            $mensagem = $_POST['mensagem'];

            $conn = conexao();
            $sql = "INSERT INTO contatos (nome, email, mensagem) VALUES (?, ?, ?)";

            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("sss", $nome, $email, $mensagem);
                if ($stmt->execute()) {
                    $mensagemStatus = "Mensagem enviada com sucesso!";
                } else {
                    $mensagemStatus = "Erro ao enviar mensagem: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $mensagemStatus = "Erro na preparação da consulta: " . $conn->error;
            }
            $conn->close();
        }
        ?>

        <!-- Exibe a mensagem de sucesso ou erro -->
        <?php if (!empty($mensagemStatus)): ?>
            <p style="color: green; font-weight: bold; text-align: center;"><?php echo $mensagemStatus; ?></p>
        <?php endif; ?>

        <form action="" method="POST">
            <label for="nome">Nome Completo*</label>
            <input type="text" id="nome" name="nome" required>

            <label for="email">E-mail*</label>
            <input type="email" id="email" name="email" required>

            <label for="mensagem">Sua Mensagem*</label>
            <textarea id="mensagem" name="mensagem" required></textarea>

            <button type="submit" class="primary-btn">Enviar Mensagem</button>
        </form>
    </div>
</div>

<footer>
    <p>© 2024 Sistema Agropecuário. Todos os direitos reservados.</p>
</footer>

</body>
</html>
