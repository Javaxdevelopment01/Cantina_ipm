<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// 1. Mostrar se está logado como vendedor
echo "<h2>Status da Sessão:</h2>";
echo "Vendedor ID: " . ($_SESSION['vendedor_id'] ?? 'Não logado') . "<br>";
echo "Vendedor Nome: " . ($_SESSION['vendedor_nome'] ?? 'Não logado') . "<br><br>";

// 2. Testar queries diretas no banco
echo "<h2>Teste de Queries:</h2>";

try {
    // Total de vendas
    $stmt = $conn->query("SELECT IFNULL(SUM(total),0) AS total_vendas FROM venda");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total_vendas'];
    echo "Total de Vendas: " . number_format($total, 2, ',', '.') . " KZ<br>";

    // Total de clientes
    $stmt = $conn->query("SELECT COUNT(*) AS total FROM cliente");
    echo "Total de Clientes: " . $stmt->fetch(PDO::FETCH_ASSOC)['total'] . "<br>";

    // Produtos em stock
    $stmt = $conn->query("SELECT COUNT(*) AS total FROM produto WHERE quantidade > 0");
    echo "Produtos em Stock: " . $stmt->fetch(PDO::FETCH_ASSOC)['total'] . "<br>";

    // Vendas do mês atual
    $stmt = $conn->prepare("SELECT IFNULL(SUM(total),0) AS total FROM venda WHERE MONTH(data_venda)=MONTH(CURDATE()) AND YEAR(data_venda)=YEAR(CURDATE())");
    $stmt->execute();
    $total_mes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Vendas do Mês Atual: " . number_format($total_mes, 2, ',', '.') . " KZ<br><br>";

    // Lista de vendas recentes
    echo "<h3>Últimas 5 Vendas:</h3>";
    $stmt = $conn->query("SELECT v.id, v.data_venda, v.total, vd.nome as vendedor FROM venda v LEFT JOIN vendedor vd ON v.id_vendedor = vd.id ORDER BY v.data_venda DESC LIMIT 5");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Data</th><th>Total</th><th>Vendedor</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['data_venda'] . "</td>";
        echo "<td>" . number_format($row['total'], 2, ',', '.') . " KZ</td>";
        echo "<td>" . $row['vendedor'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "<div style='color:red'>Erro no banco de dados: " . $e->getMessage() . "</div>";
}