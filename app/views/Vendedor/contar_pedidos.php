<?php
require_once __DIR__ . '/../../../config/database.php';
// Conta pedidos pendentes na tabela correta 'pedido'
$stmt = $conn->query("SELECT COUNT(*) FROM pedido WHERE estado='pendente'");
echo $stmt->fetchColumn();
?>
