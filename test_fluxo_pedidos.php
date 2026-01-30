<?php
/**
 * Script de teste para verificar o fluxo de estados dos pedidos
 */

require_once __DIR__ . '/config/database.php';

echo "=== TESTE DE FLUXO DE ESTADOS DE PEDIDOS ===\n\n";

// Verifica os estados que existem na tabela pedido
$stmt = $conn->query("SELECT DISTINCT estado FROM pedido ORDER BY estado");
$estados = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Estados encontrados na tabela pedido:\n";
foreach ($estados as $e) {
    echo "  - '" . ($e ?: '(vazio)') . "'\n";
}

echo "\n=== CONTAGEM POR ESTADO ===\n";
$stmt = $conn->query("SELECT estado, COUNT(*) as total FROM pedido GROUP BY estado ORDER BY total DESC");
$contagem = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($contagem as $c) {
    echo ($c['estado'] ?: '(vazio)') . ": " . $c['total'] . "\n";
}

echo "\n=== FLUXO ESPERADO ===\n";
echo "1. pendente      → Pedido criado pelo cliente\n";
echo "2. atendido      → Vendedor marca como atender (cria venda)\n";
echo "3. finalizado    → Factura gerada com pagamento completo\n";
echo "4. cancelado     → Pedido foi cancelado\n";

echo "\n=== VERIFICAÇÃO DE INCONSISTÊNCIAS ===\n";

// Verifica se há estados não reconhecidos (além dos esperados)
$estadosEsperados = ['pendente', 'atendido', 'finalizado', 'cancelado', ''];
// Normaliza para comparação case-insensitive e trata NULL/empty como ''
$estadosNormalized = array_map(function($v){ return $v === null ? '' : strtolower($v); }, $estados);
$estadosEsperadosNormalized = array_map('strtolower', $estadosEsperados);
$inconsistentesNormalized = array_diff($estadosNormalized, $estadosEsperadosNormalized);

// Reconstruir lista de valores originais que são inconsistentes (para exibição)
$inconsistentes = [];
foreach ($estados as $orig) {
    $norm = $orig === null ? '' : strtolower($orig);
    if (in_array($norm, $inconsistentesNormalized, true) && !in_array($orig, $inconsistentes, true)) {
        $inconsistentes[] = $orig;
    }
}

if (empty($inconsistentes)) {
    echo "✓ Todos os estados estão consistentes!\n";
} else {
    echo "✗ Estados não reconhecidos encontrados:\n";
    foreach ($inconsistentes as $e) {
        echo "  - '" . ($e ?: '(vazio)') . "'\n";
    }
}

echo "\n=== CONCLUSÃO ===\n";
echo "Se os pedidos agora só mostram: 'Pendente', 'Finalizado' ou 'Cancelado'\n";
echo "em vez de 'Desconhecido', o problema foi resolvido! ✓\n";
?>
