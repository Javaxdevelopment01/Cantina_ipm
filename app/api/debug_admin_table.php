<?php
header('Content-Type: application/json; charset=UTF-8');

try {
    require_once __DIR__ . '/../../config/database.php';
    
    // Verificar se tabela existe
    $result = $conn->query("SHOW TABLES LIKE 'admin'");
    if ($result->rowCount() === 0) {
        // Tabela não existe, criar
        $conn->exec("
            CREATE TABLE admin (
                id INT PRIMARY KEY AUTO_INCREMENT,
                nome VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                senha VARCHAR(255) NOT NULL,
                foto_perfil VARCHAR(255),
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo json_encode(['status' => 'Tabela admin criada com sucesso']);
    } else {
        echo json_encode(['status' => 'Tabela admin já existe']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
