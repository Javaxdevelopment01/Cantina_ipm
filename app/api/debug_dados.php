<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';

try {
    $diagnostico = [];
    
    // 1. Contagem de registros em cada tabela
    $tabelas = ['produto', 'venda', 'venda_item', 'cliente', 'vendedor'];
    foreach ($tabelas as $tabela) {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM $tabela");
        $diagnostico['contagem'][$tabela] = $stmt->fetchColumn();
    }
    
    // 2. Amostra de produtos
    $stmt = $conn->query("SELECT id, nome, quantidade, preco FROM produto LIMIT 5");
    $diagnostico['produtos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Amostra de vendas recentes
    $stmt = $conn->query("SELECT id, data_venda, total, id_vendedor FROM venda ORDER BY data_venda DESC LIMIT 5");
    $diagnostico['vendas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Soma total de vendas
    $stmt = $conn->query("SELECT IFNULL(SUM(total),0) as total_vendas FROM venda");
    $diagnostico['total_vendas'] = floatval($stmt->fetchColumn());
    
    // 5. Produtos em estoque
    $stmt = $conn->query("SELECT COUNT(*) FROM produto WHERE quantidade > 0");
    $diagnostico['produtos_em_stock'] = intval($stmt->fetchColumn());
    
    // 6. Vendas por mês (últimos 3 meses)
    $stmt = $conn->query("SELECT DATE_FORMAT(data_venda, '%Y-%m') as mes, 
                         IFNULL(SUM(total),0) as total 
                         FROM venda 
                         WHERE data_venda >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH) 
                         GROUP BY mes 
                         ORDER BY mes DESC");
    $diagnostico['vendas_ultimos_meses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'diagnostico' => $diagnostico,
        'mensagem' => 'Execute este script para ver os dados existentes no banco'
    ], JSON_PRETTY_PRINT);

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>