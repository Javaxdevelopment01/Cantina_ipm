<?php
require_once __DIR__ . '/../../config/database.php';

try {
    // ALTER TABLE para adicionar 'admin' ao ENUM
    $sql = "ALTER TABLE password_reset_requests MODIFY COLUMN user_type ENUM('vendedor', 'admin') DEFAULT 'vendedor';";
    $conn->exec($sql);
    echo "Tabela 'password_reset_requests' atualizada com sucesso (ENUM adicionado).\n";
} catch (PDOException $e) {
    echo "Nota: Pode falhar se já existir, ignorando se for erro recorrente.\n";
    die("Erro: " . $e->getMessage() . "\n");
}
