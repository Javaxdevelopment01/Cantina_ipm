<?php
header('Content-Type: application/json; charset=UTF-8');

try {
    require_once __DIR__ . '/../../config/database.php';
    
    // Verificar colunas da tabela admin
    $result = $conn->query("DESCRIBE admin");
    $colunas = $result->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // Adicionar coluna criado_em se não existir
    if (!in_array('criado_em', $colunas)) {
        $conn->exec("ALTER TABLE admin ADD COLUMN criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo json_encode(['status' => 'Coluna criado_em adicionada com sucesso', 'colunas' => $colunas]);
    } else {
        echo json_encode(['status' => 'Coluna criado_em já existe', 'colunas' => $colunas]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
