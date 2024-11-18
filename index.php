<?php
session_set_cookie_params(0);
session_start(); // Inicia a sessão

// Lógica de logout
if (isset($_GET['logout'])) {
    session_destroy(); // Encerra a sessão
    header("Location: index.php"); // Redireciona para a página inicial
    exit();
}

// Função para verificar se o usuário está logado
function verificaLogin() {
    if (!isset($_SESSION['usuario'])) {
        header("Location: /Login/login.html");
        exit();
    }
}

// Verifica se está acessando uma página protegida
$paginaAtual = $_SERVER['PHP_SELF'];
$paginasProtegidas = [
    '/Menu/Usuarios/usuarios_cadastro.php',
    '/Menu/Vendas/vendas_cadastro.php',
    '/Menu/Animais/animais_cadastro.php',
    '/Menu/Clientes/clientes_cadastro.php',
    '/Menu/Produtos/produto_cadastro.php',
    '/Menu/Relatorio/relatorio.php'
];

if (in_array($paginaAtual, $paginasProtegidas)) {
    verificaLogin();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <title>Menu Responsivo</title>
</head>
<body>
<nav>
    <div class="logo">
        <a href="index.php">
            <img src="Imagens/logo.png" alt="Logo">
        </a>
    </div>
    <ul>
    <?php if (isset($_SESSION['usuario'])): ?>
        <li class="dropdown">
            <a href="#">Cadastros &#9662;</a>
            <ul class="dropdown-content">
                <?php if ($_SESSION['usuario']['tipo_usuario'] === 'admin'): ?>
                    <li><a href="Menu/Usuarios/usuarios_cadastro.php">Cadastro de Usuários</a></li>
                <?php endif; ?>
                <li><a href="Menu/Vendas/vendas_cadastro.php">Cadastro de Vendas</a></li>
                <li><a href="Menu/Animais/animais_cadastro.php">Cadastro de Animais</a></li>
                <li><a href="Menu/Clientes/clientes_cadastro.php">Cadastro de Clientes</a></li>
                <li><a href="Menu/Produtos/produto_cadastro.php">Cadastro de Produção</a></li>
            </ul>
        </li>
        <li><a href="Menu/Relatorio/relatorio.php">Relatórios</a></li>
    <?php endif; ?>
        <li><a href="Sobrenos/sobrenos.html">Sobre nós</a></li>
        <li><a href="Contato/contatenos.php">Contate-nos</a></li>

        <?php if (isset($_SESSION['usuario'])): ?>
            <li><a href="index.php?logout=true">
                <?php 
                echo is_array($_SESSION['usuario']) && isset($_SESSION['usuario']['nome']) 
                    ? htmlspecialchars($_SESSION['usuario']['nome']) 
                    : 'Usuário';
                ?> 
                (Sair)
            </a></li>
        <?php else: ?>
            <li><a href="Login/login.php" id="entrar">Entrar</a></li>
        <?php endif; ?>
    </ul>
</nav>


    <div class="carrossel">
        <div class="carrossel-container">
            <div class="carrossel-slide">
                <img src="Imagens/Carrossel/Imagem1.jpg" alt="Imagem 1">
            </div>
            <div class="carrossel-slide">
                <img src="Imagens/Carrossel/Imagem2.jpg" alt="Imagem 2">
            </div>
            <div class="carrossel-slide">
                <img src="Imagens/Carrossel/Imagem3.jpg" alt="Imagem 3">
            </div>
        </div>
        <button class="prev" onclick="mudarSlide(-1)">❮</button>
        <button class="next" onclick="mudarSlide(1)">❯</button>
        
        <!-- Indicadores -->
        <div class="indicadores">
            <span class="bolinha" onclick="mostrarSlide(0)"></span>
            <span class="bolinha" onclick="mostrarSlide(1)"></span>
            <span class="bolinha" onclick="mostrarSlide(2)"></span>
        </div>
    </div>
    
    <section class="boas-vindas">
        <div class="boas-vindas-content">
            <h1>
                Bem-vindo ao<br>
                <span>Sistema Agropecuário!</span>
            </h1>
            <p class="subtitulo">
                Estamos aqui para fornecer as melhores soluções e<br>
                recursos para o seu negócio rural. Explore nosso sistema e<br>
                descubra como podemos ajudar a transformar suas operações e<br>
                aumentar a produtividade. Junte-se a nós nessa jornada de<br>
                inovação e crescimento!
            </p>
        </div>
    </section>    

    <footer>
        <p>© 2024 Sistema Agropecuário. Todos os direitos reservados.</p>
    </footer>

    <script>
        let slideIndex = 0;
        mostrarSlide(slideIndex);

        function mudarSlide(n) {
            mostrarSlide(slideIndex += n);
        }

        function mostrarSlide(n) {
            const slides = document.querySelectorAll(".carrossel-slide");
            const bolinhas = document.querySelectorAll(".bolinha");
    
            if (n >= slides.length) slideIndex = 0;
            if (n < 0) slideIndex = slides.length - 1;

            slides.forEach((slide, index) => {
                slide.style.display = (index === slideIndex) ? "block" : "none";
            });

            // Atualiza as bolinhas
            bolinhas.forEach((bolinha, index) => {
                bolinha.classList.toggle("active", index === slideIndex);
            });
        }
    </script>
</body>
</html>
