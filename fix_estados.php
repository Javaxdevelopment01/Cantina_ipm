<?php
require_once 'config/database.php';

echo "Atualizando 27 pedidos com estado vazio para 'pendente'...\n";

$stmt = $conn->prepare('UPDATE pedido SET estado = ? WHERE estado = "" OR estado IS NULL');
$result = $stmt->execute(['pendente']);

if($result) {
  echo "✓ Sucesso! Os pedidos foram atualizados para 'pendente'\n\n";
  
  // Verificar contagem
  $stmt = $conn->query('SELECT estado, COUNT(*) as total FROM pedido GROUP BY estado ORDER BY total DESC');
  $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  echo "Nova contagem por estado:\n";
  foreach($counts as $c) {
    echo "  " . ($c['estado'] ?: '(vazio)') . ": " . $c['total'] . "\n";
  }
} else {
  echo "✗ Erro na atualização\n";
}
?>
