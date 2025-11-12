<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Verifica se o vendedor está autenticado
if (!isset($_SESSION['vendedor_id'])) {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

// Ação (atender ou ignorar)
$acao = $_GET['action'] ?? '';

if ($acao === 'atender') {
    // Atualiza o estado dos pedidos pendentes para "em processamento"
    $stmt = $conn->prepare("UPDATE pedido SET estado = 'em processamento' WHERE estado = 'pendente'");
    $stmt->execute();
    echo json_encode(['sucesso' => true, 'mensagem' => 'Pedidos marcados como em processamento']);
}
elseif ($acao === 'ignorar') {
    // Atualiza o estado dos pedidos pendentes para "ignorado"
    $stmt = $conn->prepare("UPDATE pedido SET estado = 'ignorado' WHERE estado = 'pendente'");
    $stmt->execute();
    echo json_encode(['sucesso' => true, 'mensagem' => 'Pedidos ignorados']);
}
else {
    http_response_code(400);
    echo json_encode(['erro' => 'Ação inválida']);
}
