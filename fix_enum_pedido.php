<?php
/**
 * Script para corrigir a estrutura da tabela pedido
 * Altera ENUM de ('pendente','atendido','cancelado') 
 * para ('pendente','atendido','finalizado','cancelado')
 */

require_once __DIR__ . '/config/database.php';

echo "=== CORRIGINDO ENUM DA TABELA PEDIDO ===\n\n";

try {
    // Altera a estrutura da tabela para adicionar 'finalizado'
    echo "1. Alterando ENUM da coluna 'estado'...\n";
    $sql = "ALTER TABLE `pedido` MODIFY COLUMN `estado` 
            ENUM('pendente', 'atendido', 'Finalizado', 'cancelado')
            COLLATE utf8mb4_unicode_ci 
            DEFAULT 'pendente'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "   ✓ ENUM atualizado!\n";
    
    // Verifica estrutura
    echo "\n2. Verificando nova estrutura...\n";
    $stmt = $conn->query("DESCRIBE pedido estado");
    $estrutura = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   Campo: " . $estrutura['Field'] . "\n";
    echo "   Tipo: " . $estrutura['Type'] . "\n";
    echo "   Padrão: " . $estrutura['Default'] . "\n";
    
    echo "\n✓ SUCESSO!\n";
    echo "Novo ENUM: 'pendente', 'atendido', 'finalizado', 'cancelado'\n";
    
    echo "\n=== FLUXO CORRETO ===\n";
    echo "1. Pendente     → Pedido criado\n";
    echo "2. Atendido     → Vendedor marca como atender (cria venda)\n";
    echo "3. Finalizado   → Factura gerada com pagamento ✓ (VERDE)\n";
    echo "4. Cancelado    → Pedido cancelado\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
}
?>

