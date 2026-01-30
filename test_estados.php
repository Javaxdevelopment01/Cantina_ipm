<?php
require_once 'config/database.php';

echo "=== Estados dos Pedidos ===\n\n";

$stmt = $conn->query('SELECT DISTINCT estado FROM pedido ORDER BY estado');
$states = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Estados únicos encontrados:\n";
foreach($states as $s) {
  echo "- '" . $s['estado'] . "'\n";
}

echo "\n=== Contagem por Estado ===\n";
$stmt = $conn->query('SELECT estado, COUNT(*) as total FROM pedido GROUP BY estado ORDER BY total DESC');
$counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($counts as $c) {
  echo $c['estado'] . ": " . $c['total'] . "\n";
}
