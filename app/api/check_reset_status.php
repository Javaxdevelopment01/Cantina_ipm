<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';

$response = ['status' => 'error', 'message' => ''];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $response['message'] = 'ID inválido.';
    echo json_encode($response);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT status FROM password_reset_requests WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $response['status'] = $row['status'];
        $response['success'] = true;
    } else {
        $response['message'] = 'Pedido não encontrado.';
    }

} catch (Exception $e) {
    $response['message'] = 'Erro ao consultar.';
}

echo json_encode($response);
