<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS login_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type ENUM('admin', 'vendedor') NOT NULL,
        login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        status ENUM('success', 'failed') DEFAULT 'success',
        INDEX (user_id),
        INDEX (login_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $conn->exec($sql);
    echo "Tabela 'login_history' criada com sucesso.\n";
} catch (PDOException $e) {
    die("Erro ao criar tabela: " . $e->getMessage() . "\n");
}
