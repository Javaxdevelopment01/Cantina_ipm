<?php
session_start();
require_once __DIR__ . '/../../controllers/pedidoController.php';

// Verifica se o vendedor está logado
if (!isset($_SESSION['vendedor_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json');

$pedidoController = new PedidoController();
$newOrders = $pedidoController->checkNewOrders();

echo json_encode(['newOrders' => $newOrders]);
?>