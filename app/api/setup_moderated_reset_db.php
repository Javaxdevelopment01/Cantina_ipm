<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS password_reset_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(191) NOT NULL,
        new_password_hash VARCHAR(255) NOT NULL,
        user_type ENUM('vendedor', 'admin') DEFAULT 'vendedor',
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (email),
        INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $conn->exec($sql);
    echo "Tabela 'password_reset_requests' criada com sucesso.\n";
} catch (PDOException $e) {
    die("Erro ao criar tabela: " . $e->getMessage() . "\n");
}
