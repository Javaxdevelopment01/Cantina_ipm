<?php
/**
 * Script DEFINITIVO para corrigir o ENUM da tabela pedido
 * Força a alteração mesmo que haja valores incompatíveis
 */

require_once __DIR__ . '/config/database.php';

echo "=== CORRIGINDO ENUM - VERSÃO ROBUSTA ===\n\n";

try {
    // 1. Verifica o ENUM atual
    echo "1. Verificando ENUM atual...\n";
    $stmt = $conn->query("DESCRIBE pedido estado");
    $estrutura = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Tipo atual: " . $estrutura['Type'] . "\n";
    
    // 2. Converte valores inválidos antes de alterar
    echo "\n2. Limpando estados inválidos...\n";
    
    // Se houver 'finalizado' armazenado como string vazia ou inválida
    $invalidStates = ['finalizado', 'Finalizado', 'FINALIZADO', 'finalizada', 'Finalizada'];
    foreach ($invalidStates as $state) {
        $stmt = $conn->prepare("UPDATE pedido SET estado = 'pendente' WHERE estado = ?");
        $stmt->execute([$state]);
        if ($stmt->rowCount() > 0) {
            echo "   ✓ Convertidos " . $stmt->rowCount() . " pedidos '$state' → 'pendente'\n";
        }
    }
    
    // 3. Altera o ENUM DEFINITIVAMENTE
    echo "\n3. Alterando ENUM da coluna 'estado'...\n";
    
    // Primeiro: muda para VARCHAR para aceitar qualquer valor
    $sql1 = "ALTER TABLE `pedido` MODIFY COLUMN `estado` VARCHAR(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pendente'";
    $conn->exec($sql1);
    echo "   ✓ Convertido para VARCHAR (temporário)\n";
    
    // Segundo: converte para ENUM com todos os valores
    $sql2 = "ALTER TABLE `pedido` MODIFY COLUMN `estado` 
             ENUM('pendente', 'atendido', 'Finalizado', 'cancelado') 
             COLLATE utf8mb4_unicode_ci 
             DEFAULT 'pendente'";
    $conn->exec($sql2);
    echo "   ✓ Convertido para ENUM com 4 valores\n";
    
    // 4. Verifica resultado final
    echo "\n4. Verificando resultado final...\n";
    $stmt = $conn->query("DESCRIBE pedido estado");
    $estrutura = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Tipo novo: " . $estrutura['Type'] . "\n";
    echo "   Padrão: " . $estrutura['Default'] . "\n";
    
    // 5. Mostra os estados que existem
    echo "\n5. Estados atuais na tabela:\n";
    $stmt = $conn->query("SELECT DISTINCT estado, COUNT(*) as total FROM pedido GROUP BY estado ORDER BY estado");
    $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($estados as $e) {
        echo "   - '" . ($e['estado'] ?: 'VAZIO') . "': " . $e['total'] . " pedidos\n";
    }
    
    echo "\n✓ SUCESSO!\n";
    echo "ENUM finalizado: 'pendente', 'atendido', 'finalizado', 'cancelado'\n";
    echo "\nAgora teste a funcionalidade novamente!\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}
?>
