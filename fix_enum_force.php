<?php
/**
 * Script SQL direto - FORÇA a mudança do ENUM
 */

require_once __DIR__ . '/config/database.php';

echo "=== EXECUTANDO FORÇA SQL ===\n\n";

try {
    // Desativa verificações temporariamente
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "✓ Desativadas verificações de chave estrangeira\n";
    
    // Executa o ALTER TABLE com CHANGE (mais robusto)
    $sql = "ALTER TABLE `pedido` CHANGE COLUMN `estado` `estado` 
            ENUM('pendente', 'atendido', 'Finalizado', 'cancelado') 
            COLLATE utf8mb4_unicode_ci 
            DEFAULT 'pendente' NOT NULL";
    
    $conn->exec($sql);
    echo "✓ ENUM alterado com sucesso!\n";
    
    // Reativa as verificações
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✓ Reativadas verificações de chave estrangeira\n";
    
    // Verifica resultado
    echo "\n=== VERIFICAÇÃO ===\n";
    $stmt = $conn->query("DESCRIBE pedido estado");
    $desc = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Type: " . $desc['Type'] . "\n";
    
    // Lista valores do ENUM
    preg_match_all("/'([^']*)'/", $desc['Type'], $matches);
    echo "Valores: " . implode(', ', $matches[1]) . "\n";
    
    // Ajusta a mensagem para refletir a mudança
    echo "✓ SUCESSO! ENUM atualizado corretamente.\n";
    
    echo "\n✓ SUCESSO! ENUM atualizado corretamente.\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
}
?>
