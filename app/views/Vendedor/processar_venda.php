<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['vendedor_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

// Pega o JSON do body
$dados = json_decode(file_get_contents('php://input'), true);

if (!$dados || !isset($dados['pedido_id']) || !isset($dados['valor_pago'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

try {
    $conn->beginTransaction();

    // Busca o pedido para confirmar o total
    $stmt = $conn->prepare("
        SELECT p.*, v.id as venda_id 
        FROM pedido p 
        LEFT JOIN venda v ON v.id_pedido = p.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$dados['pedido_id']]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        throw new Exception('Pedido não encontrado');
    }

    // Valida o pagamento
    $valor_pago = floatval($dados['valor_pago']);
    $total = floatval($pedido['total']);
    $troco = $valor_pago - $total;

    if ($valor_pago < $total) {
        throw new Exception('Valor pago insuficiente');
    }

    if ($pedido['venda_id']) {
        // Atualiza a venda existente
        $stmt = $conn->prepare("
            UPDATE venda 
            SET valor_pago = :valor_pago,
                troco = :troco,
                estado = 'finalizada'
            WHERE id_pedido = :pedido_id
        ");
    } else {
        // Cria nova venda
        $stmt = $conn->prepare("
            INSERT INTO venda (id_pedido, id_vendedor, total, valor_pago, troco, estado) 
            VALUES (:pedido_id, :vendedor_id, :total, :valor_pago, :troco, 'finalizada')
        ");
        $stmt->bindParam(':vendedor_id', $_SESSION['vendedor_id']);
        $stmt->bindParam(':total', $total);
    }

    $stmt->bindParam(':valor_pago', $valor_pago);
    $stmt->bindParam(':troco', $troco);
    $stmt->bindParam(':pedido_id', $dados['pedido_id']);
    $stmt->execute();

    // Atualiza o status do pedido
    $stmt = $conn->prepare("UPDATE pedido SET estado = 'Finalizado' WHERE id = ?");
    $stmt->execute([$dados['pedido_id']]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>