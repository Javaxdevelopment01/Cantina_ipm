<?php
// API endpoint para checkout do cliente
session_start();
header('Content-Type: application/json; charset=utf-8');

// Buffer any output to avoid breaking JSON
ob_start();

try {
    // Accept both form-encoded and raw JSON
    $payloadRaw = $_POST['payload'] ?? null;
    if (!$payloadRaw) {
        $raw = file_get_contents('php://input');
        $j = json_decode($raw, true);
        $payloadRaw = $j['payload'] ?? null;
        $method = $_POST['method'] ?? ($j['method'] ?? 'mao');
        $methodDataRaw = $_POST['methodData'] ?? ($j['methodData'] ?? '{}');
    } else {
        $method = $_POST['method'] ?? 'mao';
        $methodDataRaw = $_POST['methodData'] ?? '{}';
    }

    $payload = json_decode($payloadRaw ?? '[]', true);
    $methodData = json_decode($methodDataRaw ?? '{}', true);

    if (!$payload || !is_array($payload['items'] ?? null)) {
        $buffer = ob_get_clean();
        echo json_encode(['success' => false, 'error' => 'Carrinho inválido', 'debug' => $buffer]);
        exit;
    }

    require_once __DIR__ . '/../controllers/pedidoController.php';
    $pedidoController = new PedidoController();

        // Validação server-side de stock: garante que não vendemos produtos esgotados
        // Usa o Model Produto para verificar quantidades atuais
        try {
            require_once __DIR__ . '/../Models/Produto.php';
            // conecta-se ao DB (se o model não o fizer internamente)
            if (!isset($conn)) {
                // tenta carregar a configuração caso não esteja carregada
                @require_once __DIR__ . '/../config/database.php';
            }
            $produtoModel = new Produto($conn);
        } catch (Throwable $e) {
            // se não for possível validar, continuamos mas registamos debug
            $produtoModel = null;
        }

        // Se temos um model disponível, valida cada item do payload
        $stockProblems = [];
        if ($produtoModel) {
            foreach ($payload['items'] as $it) {
                $prodId = intval($it['id'] ?? $it['product_id'] ?? 0);
                $reqQty = intval($it['qty'] ?? $it['quantity'] ?? $it['qtd'] ?? 0);
                if ($prodId <= 0) {
                    $stockProblems[] = ['id' => null, 'message' => 'ID do produto inválido', 'item' => $it];
                    continue;
                }
                $p = $produtoModel->buscar($prodId);
                if (!$p) {
                    $stockProblems[] = ['id' => $prodId, 'message' => 'Produto não encontrado', 'stock' => null, 'requested' => $reqQty];
                    continue;
                }
                $available = intval($p['quantidade'] ?? 0);
                if ($available <= 0) {
                    $stockProblems[] = ['id' => $prodId, 'message' => 'Produto esgotado', 'stock' => $available, 'requested' => $reqQty, 'nome' => $p['nome'] ?? ''];
                    continue;
                }
                if ($reqQty > $available) {
                    $stockProblems[] = ['id' => $prodId, 'message' => 'Quantidade solicitada maior que o stock', 'stock' => $available, 'requested' => $reqQty, 'nome' => $p['nome'] ?? ''];
                    continue;
                }
            }
        }

        if (!empty($stockProblems)) {
            $buffer = ob_get_clean();
            $resp = ['success' => false, 'error' => 'Problemas de stock ao processar o carrinho', 'problems' => $stockProblems];
            if (!empty($buffer)) $resp['debug'] = $buffer;
            echo json_encode($resp);
            exit;
        }

    $id_cliente = $_SESSION['cliente_id'] ?? null;

    $dados = [
        'id_cliente' => $id_cliente,
        'forma_pagamento' => $method,
        'total' => $payload['total'] ?? 0,
        'items' => $payload['items'],
        'methodData' => $methodData
    ];

    $resultado = $pedidoController->criarPedido($dados);
    $buffer = ob_get_clean();

    if ($resultado['success']) {
        $resp = ['success' => true, 'orderId' => $resultado['pedido_id']];
        if (!empty($buffer)) $resp['debug'] = $buffer;
        echo json_encode($resp);
        exit;
    } else {
        $resp = ['success' => false, 'error' => $resultado['error'] ?? 'Erro ao criar pedido'];
        if (!empty($buffer)) $resp['debug'] = $buffer;
        echo json_encode($resp);
        exit;
    }

} catch (Exception $e) {
    $buffer = ob_get_clean();
    $resp = ['success' => false, 'error' => 'Exceção: ' . $e->getMessage()];
    if (!empty($buffer)) $resp['debug'] = $buffer;
    echo json_encode($resp);
    exit;
}
