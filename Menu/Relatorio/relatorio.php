<?php
include_once '../toolskit.php';

session_start(); // Inicia a sessão

// Verifica o login antes de continuar
verificaLogin();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Agropecuário</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

    <!-- Cabeçalho para impressão -->
    <div class="print-header">
        MilkFlow - Relatório Agropecuário
    </div>

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
        <div class="form-container">
            <?php
            $host = '127.0.0.1';
            $db = 'sistema_agropecuario';
            $user = 'root';
            $pass = '';

            try {
                $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Erro na conexão: " . $e->getMessage());
            }

            $clientes = $pdo->query("SELECT id_cliente, nome FROM clientes ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
            $animais = $pdo->query("SELECT id_animal, nome FROM animais ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
            $vendas = $pdo->query("SELECT id_venda, CONCAT('Venda #', id_venda, ' - ', DATE_FORMAT(data_venda, '%d/%m/%Y')) AS descricao FROM vendas ORDER BY data_venda DESC")->fetchAll(PDO::FETCH_ASSOC);
            $usuarios = $pdo->query("SELECT id_usuario, nome FROM usuarios ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

            ?>
            <form action="" method="GET">
                <h2>Relatório Agropecuário</h2>

                <label for="data_inicio">Data Início:</label>
                <input type="date" id="data_inicio" name="data_inicio">

                <label for="data_fim">Data Final:</label>
                <input type="date" id="data_fim" name="data_fim">

                <label for="cliente">Cliente:</label>
                <select id="cliente" name="cliente">
                    <option value="">Selecione</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?php echo $cliente['id_cliente']; ?>"><?php echo htmlspecialchars($cliente['nome']); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="animal">Animal:</label>
                <select id="animal" name="animal">
                    <option value="">Selecione</option>
                    <?php foreach ($animais as $animal): ?>
                        <option value="<?php echo $animal['id_animal']; ?>"><?php echo htmlspecialchars($animal['nome']); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="venda">Venda:</label>
                <select id="venda" name="venda">
                    <option value="">Selecione</option>
                    <?php foreach ($vendas as $venda): ?>
                        <option value="<?php echo $venda['id_venda']; ?>"><?php echo htmlspecialchars($venda['descricao']); ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="usuario">Usuário:</label>
                <select id="usuario" name="usuario">
                    <option value="">Selecione</option>
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?php echo $usuario['id_usuario']; ?>"><?php echo htmlspecialchars($usuario['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <div class="btn-container">
                    <button type="submit" class="print-btn">Gerar Relatório</button>
                    <button class="print-btn" onclick="window.print()">Imprimir Relatório</button>
                    <button class="print-btn" onclick="window.location.href='../../index.php';">Voltar</button>
                </div>
                
            </form>
        </div>

        <div class="results-container">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
                $data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : null;
                $data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : null;
                $cliente = isset($_GET['cliente']) ? $_GET['cliente'] : null;
                $animal = isset($_GET['animal']) ? $_GET['animal'] : null;
                $venda = isset($_GET['venda']) ? $_GET['venda'] : null;
                $usuario = isset($_GET['usuario']) ? $_GET['usuario'] : null;

                $sql = "SELECT vendas.id_venda, clientes.nome AS cliente, animais.nome AS animal, 
                               vendas.data_venda, vendas.quantidade, 
                               (vendas.quantidade * vendas.valor_unitario) AS valor_total,
                               usuarios.nome AS usuario
                        FROM vendas
                        LEFT JOIN clientes ON vendas.id_cliente = clientes.id_cliente
                        LEFT JOIN animais ON vendas.id_animal = animais.id_animal
                        LEFT JOIN usuarios ON vendas.id_usuario = usuarios.id_usuario
                        WHERE 1=1";

                if ($data_inicio) $sql .= " AND vendas.data_venda >= :data_inicio";
                if ($data_fim) $sql .= " AND vendas.data_venda <= :data_fim";
                if ($cliente) $sql .= " AND vendas.id_cliente = :cliente";
                if ($animal) $sql .= " AND vendas.id_animal = :animal";
                if ($venda) $sql .= " AND vendas.id_venda = :venda";
                if ($usuario) $sql .= " AND vendas.id_usuario = :usuario";

                $stmt = $pdo->prepare($sql);

                if ($data_inicio) $stmt->bindParam(':data_inicio', $data_inicio);
                if ($data_fim) $stmt->bindParam(':data_fim', $data_fim);
                if ($cliente) $stmt->bindParam(':cliente', $cliente);
                if ($animal) $stmt->bindParam(':animal', $animal);
                if ($venda) $stmt->bindParam(':venda', $venda);
                if ($usuario) $stmt->bindParam(':usuario', $usuario);

                $stmt->execute();
                $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($resultados) > 0) {
                    echo '<table>';
                    echo '<tr><th>ID Venda</th><th>Usuário</th><th>Cliente</th><th>Animal</th><th>Data Venda</th><th>Quantidade</th><th>Valor Total</th></tr>';
                    foreach ($resultados as $linha) {
                        echo '<tr>';
                        echo '<td>' . $linha['id_venda'] . '</td>';
                        echo '<td>' . $linha['usuario'] . '</td>';
                        echo '<td>' . $linha['cliente'] . '</td>';
                        echo '<td>' . $linha['animal'] . '</td>';
                        echo '<td>' . $linha['data_venda'] . '</td>';
                        echo '<td>' . $linha['quantidade'] . '</td>';
                        echo '<td>R$ ' . number_format($linha['valor_total'], 2, ',', '.') . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';

                    $sql_soma = "SELECT SUM(quantidade * valor_unitario) as total_vendas FROM vendas WHERE 1=1";
                    if ($data_inicio) $sql_soma .= " AND data_venda >= :data_inicio";
                    if ($data_fim) $sql_soma .= " AND data_venda <= :data_fim";
                    if ($cliente) $sql_soma .= " AND id_cliente = :cliente";
                    if ($animal) $sql_soma .= " AND id_animal = :animal";
                    if ($venda) $sql_soma .= " AND id_venda = :venda";
                    if ($usuario) $sql_soma .= " AND id_usuario = :usuario";

                    $stmt_soma = $pdo->prepare($sql_soma);

                    if ($data_inicio) $stmt_soma->bindParam(':data_inicio', $data_inicio);
                    if ($data_fim) $stmt_soma->bindParam(':data_fim', $data_fim);
                    if ($cliente) $stmt_soma->bindParam(':cliente', $cliente);
                    if ($animal) $stmt_soma->bindParam(':animal', $animal);
                    if ($venda) $stmt_soma->bindParam(':venda', $venda);
                    if ($usuario) $stmt_soma->bindParam(':usuario', $usuario);

                    $stmt_soma->execute();
                    $total_vendas = $stmt_soma->fetch(PDO::FETCH_ASSOC)['total_vendas'];

                    echo "<h2>Total das Vendas: R$ " . number_format($total_vendas, 2, ',', '.') . "</h2>";
                } else {
                    echo '<p>Nenhum resultado encontrado.</p>';
                }
            } else {
                echo '<p>Selecione os filtros e clique em "Gerar Relatório" para ver os resultados.</p>';
            }
            ?>
        </div>
    </div>
</body>
</html>