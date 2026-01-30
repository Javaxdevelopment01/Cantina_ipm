<?php
/**
 * Script para debugar o problema do estado "Desconhecido"
 */

require_once __DIR__ . '/config/database.php';

echo "=== DEBUG: ESTADOS DE PEDIDOS ===\n\n";

// Busca últimos 10 pedidos com seus estados
$stmt = $conn->query("
    SELECT 
        id, 
        data_pedido,
        estado,
        (SELECT COUNT(*) FROM venda WHERE venda.id_pedido = pedido.id) as tem_venda
    FROM pedido 
    ORDER BY data_pedido DESC 
    LIMIT 10
");

$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Últimos 10 pedidos:\n";
echo str_repeat("-", 80) . "\n";

foreach ($pedidos as $p) {
    $estado = $p['estado'];
    $estadoExibido = strtolower($estado ?? '');
    
    // Verifica o que seria exibido
    if ($estadoExibido === 'pendente') {
        $label = 'Pendente (🟡)';
    } elseif ($estadoExibido === 'atendido') {
        $label = 'Atendido (🔵)';
    } elseif ($estadoExibido === 'finalizado') {
        $label = 'Finalizado (🟢)';
    } elseif ($estadoExibido === 'cancelado') {
        $label = 'Cancelado (🔴)';
    } else {
        $label = 'DESCONHECIDO ⚠️ ("' . ($estado ?: 'vazio') . '")';
    }
    
    echo sprintf(
        "ID: %d | Data: %s | Estado BD: %-15s | Exibição: %-30s | Venda: %s\n",
        $p['id'],
        date('d/m/Y H:i', strtotime($p['data_pedido'])),
        '"' . ($estado ?: 'VAZIO') . '"',
        $label,
        ($p['tem_venda'] > 0 ? 'SIM' : 'NÃO')
    );
}

echo "\n" . str_repeat("-", 80) . "\n";
echo "\nEstados ÚNICOS na tabela:\n";

$stmt = $conn->query("SELECT DISTINCT estado FROM pedido ORDER BY estado");
$estados = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($estados as $e) {
    echo "  - '" . ($e ?: 'VAZIO') . "'\n";
}

echo "\n=== POSSÍVEL PROBLEMA ===\n";
echo "Se houver estados como 'Finalizado' ou 'FINALIZADO'\n";
echo "em vez de 'finalizado', isso causa o 'Desconhecido'\n";
?>
