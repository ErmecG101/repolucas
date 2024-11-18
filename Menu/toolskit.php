<?php
//função conexao
function conexao() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "sistema_agropecuario";

    // Criando a conexão
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Verificando a conexão
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

/*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

// Função para verificar se o usuário está logado
function verificaLogin() {
    if (!isset($_SESSION['usuario'])) {
        header("Location: /Login/login.html"); // Ajuste este caminho conforme necessário
        exit();
    }
}

/*------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

// Funcão para gerar botao excluir
function gerarBotaoExcluirAnimal($idAnimal, $tokenCSRF) {
    if (isset($_SESSION['usuario']) && $_SESSION['usuario']['tipo_usuario'] === 'admin') {
        return '<a href="animais_cadastro.php?excluir=' . $idAnimal . '&_token=' . $tokenCSRF . '" class="button" onclick="return confirm(\'Tem certeza que deseja excluir este animal?\');">Excluir</a>';
    }
    return '';
}

/*------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

// função pesquisar e voltar
function gerarFormularioPesquisa($action, $placeholder, $botaoPesquisarTexto, $botaoVoltarTexto) {
    $tokenCSRF = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $tokenCSRF;

    return <<<HTML
        <form method="GET" action="$action">
            <input type="text" name="pesquisa" placeholder="$placeholder" class="search-bar">
            <button class="pesquisar" type="submit" class="button">$botaoPesquisarTexto</button>
            <input class="voltar" name="action" type="submit" value="$botaoVoltarTexto" onclick="window.history.back();"/>
            <input type="hidden" name="_token" value="$tokenCSRF">
        </form>
    HTML;
}

/*------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

//função menu
function gerarMenuNavegacao($tipoUsuario) {
    $menu = '<a class="logo-icon" href="../../index.php"><img src="../imagens/icons/Logo.ico" alt="" class="icon"></a>';

    if ($tipoUsuario === 'admin') {
        $menu .= '
            <li><a href="../Usuarios/usuarios_cadastro.php" target="_blank">Usuários</a></li>
        ';
    }

    $menu .= '
        <li><a href="../Vendas/vendas_cadastro.php" target="_blank">Vendas</a></li>
        <li><a href="../Clientes/clientes_cadastro.php" target="_blank">Clientes</a></li>
        <li><a href="../animais/animais_cadastro.php" target="_blank">Animais</a></li>
        <li><a href="../Produtos/produto_cadastro.php" target="_blank">Produção</a></li>
        <li><a href="../Relatorio/relatorio.php" target="_blank">Relátorios</a></li>
        <li><a href="../logout.php">Sair</a></li>
    ';

    return $menu;
}

/*-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------*/

/*function gerarBotoesAcao($idRegistro, $urlBase, $acoesPermitidas = ['alterar', 'excluir']) {
    $html = '';

    if (isset($_SESSION['usuario']) && $_SESSION['usuario']['tipo_usuario'] === 'admin') {
        foreach ($acoesPermitidas as $acao) {
            switch ($acao) {
                case 'alterar':
                    $html .= '<a href="' . $urlBase . 'alterar.php?id=' . $idRegistro . '" class="button">Alterar</a>';
                    break;
                case 'excluir':
                    $html .= '<a href="' . $urlBase . 'excluir.php?id=' . $idRegistro . '" class="button" onclick="return confirm(\'Tem certeza que deseja excluir este registro?\')">Excluir</a>';
                    break;
                // Adicione mais cases para outras ações, se necessário
            }
        }
    }

    return $html;
}*/