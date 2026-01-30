<?php
/**
 * Script para converter estados de pedidos para o novo fluxo: Pendente → Finalizado → Cancelado
 */

require_once __DIR__ . '/config/database.php';

echo "=== CONVERTENDO FLUXO DE ESTADOS ===\n\n";

// Verifica estado atual
$stmt = $conn->query("SELECT estado, COUNT(*) as total FROM pedido GROUP BY estado ORDER BY total DESC");
$contagem = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Estados ANTES:\n";
foreach ($contagem as $c) {
    echo "  " . ($c['estado'] ?: '(vazio)') . ": " . $c['total'] . "\n";
}

// Converte 'atendido' para 'pendente' (para recomeçar o fluxo)
echo "\nConvertendo 'atendido' → 'pendente'...\n";
$stmt = $conn->prepare("UPDATE pedido SET estado = 'pendente' WHERE estado = 'atendido'");
$stmt->execute();
echo "✓ Convertidos: " . $stmt->rowCount() . " pedidos\n";

// Converte 'em processamento' para 'pendente'
echo "Convertendo 'em processamento' → 'pendente'...\n";
$stmt = $conn->prepare("UPDATE pedido SET estado = 'pendente' WHERE estado = 'em processamento'");
$stmt->execute();
echo "✓ Convertidos: " . $stmt->rowCount() . " pedidos\n";

// Converte 'ignorado' para 'cancelado'
echo "Convertendo 'ignorado' → 'cancelado'...\n";
$stmt = $conn->prepare("UPDATE pedido SET estado = 'cancelado' WHERE estado = 'ignorado'");
$stmt->execute();
echo "✓ Convertidos: " . $stmt->rowCount() . " pedidos\n";

// Verifica estado final
echo "\nEstados DEPOIS:\n";
$stmt = $conn->query("SELECT estado, COUNT(*) as total FROM pedido GROUP BY estado ORDER BY total DESC");
$contagem = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($contagem as $c) {
    echo "  " . ($c['estado'] ?: '(vazio)') . ": " . $c['total'] . "\n";
}

echo "\n✓ Conversão completa!\n";
echo "Novo fluxo: Pendente → Finalizado → Cancelado\n";
?>
