<?php
/**
 * Script para verificar exatamente qual estado está sendo salvo
 * e por que está vindo como "Desconhecido"
 */

require_once __DIR__ . '/config/database.php';

echo "=== DIAGNÓSTICO DETALHADO DO BUG 'DESCONHECIDO' ===\n\n";

// Busca um pedido que deveria ser finalizado
$stmt = $conn->query("
    SELECT 
        p.id,
        p.estado,
        LOWER(p.estado) as estado_lower,
        HEX(p.estado) as estado_hex,
        LENGTH(p.estado) as estado_length,
        (SELECT COUNT(*) FROM venda WHERE venda.id_pedido = p.id) as tem_venda
    FROM pedido 
    ORDER BY p.id DESC 
    LIMIT 5
");

$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Últimos 5 pedidos com análise detalhada:\n";
echo str_repeat("-", 100) . "\n";

foreach ($pedidos as $p) {
    $estado = $p['estado'];
    $estadoLower = strtolower($estado ?? '');
    
    // Verifica o que seria exibido
    if ($estadoLower === 'pendente') {
        $exibicao = '✓ Pendente (🟡) - Seria reconhecido';
    } elseif ($estadoLower === 'atendido') {
        $exibicao = '✓ Atendido (🔵) - Seria reconhecido';
    } elseif ($estadoLower === 'finalizado') {
        $exibicao = '✓ Finalizado (🟢) - Seria reconhecido';
    } elseif ($estadoLower === 'cancelado') {
        $exibicao = '✓ Cancelado (🔴) - Seria reconhecido';
    } else {
        $exibicao = '✗ DESCONHECIDO ⚠️ - NÃO SERIA RECONHECIDO!';
    }
    
    echo sprintf(
        "ID: %d\n" .
        "  Valor BD: '%s' (HEX: %s, Len: %d)\n" .
        "  Lowercase: '%s'\n" .
        "  Exibição: %s\n" .
        "  Tem venda: %s\n\n",
        $p['id'],
        $estado ?: '(VAZIO)',
        $p['estado_hex'],
        $p['estado_length'],
        $estadoLower,
        $exibicao,
        ($p['tem_venda'] > 0 ? 'SIM' : 'NÃO')
    );
}

echo str_repeat("-", 100) . "\n";
echo "\n=== VERIFICAÇÃO DO ENUM ===\n";

$stmt = $conn->query("DESCRIBE pedido estado");
$desc = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Tipo: " . $desc['Type'] . "\n";
echo "Padrão: " . $desc['Default'] . "\n";

if (strpos($desc['Type'], 'enum') !== false) {
    preg_match_all("/'([^']*)'/", $desc['Type'], $matches);
    echo "Valores permitidos: " . implode(', ', $matches[1]) . "\n";
}

echo "\n=== PRÓXIMO PASSO ===\n";
echo "Se o ENUM não tiver 'finalizado', execute:\n";
echo "http://localhost/fix_enum_pedido_v2.php\n";
?>
