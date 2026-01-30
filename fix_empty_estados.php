<?php
/**
 * Atualiza pedidos com estado vazio:
 * - Se existir uma venda com valor_pago>0 associada -> finalizado
 * - Caso contrário -> pendente
 */
require_once __DIR__ . '/config/database.php';

try {
    $stmt = $conn->query("SELECT id FROM pedido WHERE estado IS NULL OR estado = ''");
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $updated = 0;
    // Detectar nome da coluna que referencia pedido na tabela venda
    $colStmt = $conn->query("DESCRIBE venda");
    $cols = array_column($colStmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $pedidoCol = null;
    if (in_array('id_pedido', $cols)) {
        $pedidoCol = 'id_pedido';
    } elseif (in_array('pedido_id', $cols)) {
        $pedidoCol = 'pedido_id';
    }

    if (!$pedidoCol) {
        throw new Exception("Coluna de referência a pedido não encontrada na tabela 'venda'. Esperado 'id_pedido' ou 'pedido_id'.");
    }

    $check = $conn->prepare("SELECT COUNT(*) FROM venda WHERE $pedidoCol = ? AND valor_pago > 0");
    $upFinal = $conn->prepare("UPDATE pedido SET estado = 'Finalizado' WHERE id = ?");
    $upPendente = $conn->prepare("UPDATE pedido SET estado = 'pendente' WHERE id = ?");

    foreach ($ids as $id) {
        $check->execute([$id]);
        $c = (int)$check->fetchColumn();
        if ($c > 0) {
            $upFinal->execute([$id]);
            $updated++;
        } else {
            $upPendente->execute([$id]);
            $updated++;
        }
    }

    header('Content-Type: text/plain; charset=utf-8');
    echo "OK\nUpdated: $updated\n";
} catch (Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "ERROR\n" . $e->getMessage();
}

?>
