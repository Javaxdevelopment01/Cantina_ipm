<?php
require_once 'config/database.php';

echo "=== Teste de Pedidos ===\n\n";

// Verificar pedidos sem cliente
$stmt = $conn->query('SELECT COUNT(*) as total FROM pedido WHERE id_cliente IS NULL');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Pedidos SEM cliente associado: " . $result['total'] . "\n";

$stmt = $conn->query('SELECT COUNT(*) as total FROM pedido WHERE id_cliente IS NOT NULL');
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Pedidos COM cliente associado: " . $result['total'] . "\n\n";

// Ver exemplos
echo "=== Exemplos de Pedidos ===\n";
$stmt = $conn->query('SELECT p.id, p.id_cliente, c.nome FROM pedido p LEFT JOIN cliente c ON c.id = p.id_cliente LIMIT 10');
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($results as $r) {
  echo "Pedido #" . $r['id'] . 
       " | Cliente ID: " . ($r['id_cliente'] ?? 'NULL') . 
       " | Nome: " . ($r['nome'] ?? '(Cliente não encontrado)') . "\n";
}
