<?php
session_start();
require_once __DIR__ . '/../../controllers/pedidoController.php';

// Detecta se a requisição é AJAX/JSON
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isJsonRequest = (stripos($contentType, 'application/json') !== false)
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

// Se for requisição JSON/AJAX, inicia buffer para evitar que qualquer output HTML/padroes quebrem o JSON
if ($isJsonRequest) {
    ini_set('display_errors', 0);
    ob_start();
}

// Função utilitária para responder JSON de erro e registrar em log
function respondJsonError($message, $code = 500) {
    global $isJsonRequest;
    if ($isJsonRequest) {
        if (ob_get_length() !== false) { @ob_end_clean(); }
        http_response_code($code);
        header('Content-Type: application/json');
        // registra em log para debugging
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) { @mkdir($logDir, 0755, true); }
        $logFile = $logDir . '/pedidos_error.log';
        $msg = date('Y-m-d H:i:s') . " - " . $message . "\n";
        @file_put_contents($logFile, $msg, FILE_APPEND);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    } else {
        // fallback: simple text
        http_response_code($code);
        echo $message;
        exit;
    }
}

// Captura exceções não tratadas e erros fatais quando for JSON request
if ($isJsonRequest) {
    set_exception_handler(function($e) {
        respondJsonError('Exceção: ' . $e->getMessage(), 500);
    });
    register_shutdown_function(function() {
        $err = error_get_last();
        if ($err) {
            $msg = sprintf("Fatal error: %s in %s on line %d", $err['message'], $err['file'], $err['line']);
            respondJsonError($msg, 500);
        }
    });
}

// Verifica se o vendedor está logado
if (!isset($_SESSION['vendedor_id'])) {
    if ($isJsonRequest) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Acesso negado - sessão inválida']);
        exit;
    } else {
        header('Location: login_vendedor.php');
        exit;
    }
}

$pedidoController = new PedidoController();

// Handler para atualização de estado do pedido via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Suporte tanto para form-urlencoded (browser) quanto JSON (fetch com application/json)
    $input = $_POST;
    if (empty($input)) {
        $json = json_decode(file_get_contents('php://input'), true);
        if ($json) {
            // aceitar tanto 'action' quanto 'acao' por conveniência
            if (isset($json['acao']) && !isset($json['action'])) {
                $json['action'] = $json['acao'];
            }
            $input = $json;
        }
    }

    // Normaliza chaves comuns: aceitar id_pedido ou pedido_id
    if (isset($input['id_pedido']) && !isset($input['pedido_id'])) {
        $input['pedido_id'] = $input['id_pedido'];
    }
    if (isset($input['pedido_id']) && !isset($input['id_pedido'])) {
        $input['id_pedido'] = $input['pedido_id'];
    }

    $action = $input['action'] ?? null;

    if ($action === 'atualizar_estado' || $action === 'atualizarEstado') {
        if (empty($input['pedido_id'])) { respondJsonError('pedido_id ausente na requisição', 400); }
        $resultado = $pedidoController->atualizarEstadoPedido(
            $input['pedido_id'],
            $input['estado']
        );
        if ($isJsonRequest) { ob_end_clean(); }
        echo json_encode($resultado);
        exit;
    }

    if ($action === 'get_itens' || $action === 'getItens') {
        // O método agora retorna ['itens' => ..., 'cliente' => ...]
        if (empty($input['pedido_id'])) { respondJsonError('pedido_id ausente na requisição', 400); }
        $res = $pedidoController->getPedidoItens($input['pedido_id']);
        if ($isJsonRequest) { ob_end_clean(); }
        echo json_encode(['success' => true, 'itens' => $res['itens'], 'cliente' => $res['cliente']]);
        exit;
    }

    // Se chegou aqui, ação inválida
    if ($isJsonRequest) { ob_end_clean(); }
    echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    exit;
}

$pedidos = $pedidoController->listarPedidosPendentes();
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pedidos - Cantina IPM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --petroleo:#012E40; --dourado:#D4AF37; }
        .pedido-card { 
            border-radius: 8px;
            border: 1px solid #dee2e6;
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }
        .pedido-card:hover { transform: translateY(-2px); }
        .pedido-header {
            background: var(--petroleo);
            color: var(--dourado);
            padding: 1rem;
            border-radius: 8px 8px 0 0;
        }
        .pedido-body { padding: 1rem; }
        .pedido-footer { 
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0 0 8px 8px;
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            background: var(--petroleo);
            color: var(--dourado);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: none;
            z-index: 9999;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/header_vendedor.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">Gerenciar Pedidos</h2>
            <div id="lista-pedidos">
                <?php foreach ($pedidos as $pedido): ?>
                <div class="pedido-card" data-pedido-id="<?= $pedido['id'] ?>">
                    <div class="pedido-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Pedido #<?= $pedido['id'] ?></strong>
                            <?php if (!$pedido['lido']): ?>
                                <span class="badge bg-danger ms-2">Novo!</span>
                            <?php endif; ?>
                        </div>
                        <div><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></div>
                    </div>
                    <div class="pedido-body">
                        <div class="mb-2">
                            <strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="mb-2">
                            <strong>Forma de Pagamento:</strong> <?= htmlspecialchars($pedido['forma_pagamento'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="mb-3">
                            <strong>Total:</strong> Kz <?= number_format($pedido['total'], 2, ',', '.') ?>
                        </div>
                        <div class="itens-pedido" id="itens-<?= $pedido['id'] ?>">
                            <!-- Itens serão carregados via AJAX -->
                        </div>
                    </div>
                    <div class="pedido-footer">
                        <div class="btn-group w-100">
                            <button class="btn btn-success btn-atender" data-pedido-id="<?= $pedido['id'] ?>">
                                <i class="fas fa-check"></i> Atender Pedido
                            </button>
                            <button class="btn btn-danger btn-cancelar" data-pedido-id="<?= $pedido['id'] ?>">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($pedidos)): ?>
                <div class="alert alert-info">
                    Não há pedidos pendentes no momento.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="notification" id="notification">
    <i class="fas fa-bell me-2"></i>
    <span id="notification-text"></span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    // Função para carregar itens do pedido
    function carregarItensPedido(pedidoId) {
        fetch('pedidos.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'get_itens',
                pedido_id: pedidoId
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.itens) {
                const container = document.getElementById('itens-' + pedidoId);
                const html = data.itens.map(item => `
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>${item.quantidade}x ${item.produto_nome}</div>
                        <div>Kz ${(item.preco * item.quantidade).toLocaleString('pt-PT', {minimumFractionDigits: 2})}</div>
                    </div>
                `).join('');
                container.innerHTML = html;
            }
        });
    }

    // Carrega itens de todos os pedidos ao iniciar
    document.querySelectorAll('.pedido-card').forEach(card => {
        carregarItensPedido(card.dataset.pedidoId);
    });

    // Função para atualizar estado do pedido
    function atualizarEstadoPedido(pedidoId, estado, btn) {
        btn.disabled = true;
        fetch('pedidos.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'atualizar_estado',
                pedido_id: pedidoId,
                estado: estado
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const card = document.querySelector(`.pedido-card[data-pedido-id="${pedidoId}"]`);
                card.remove();
                
                if (document.querySelectorAll('.pedido-card').length === 0) {
                    document.getElementById('lista-pedidos').innerHTML = 
                        '<div class="alert alert-info">Não há pedidos pendentes no momento.</div>';
                }
            } else {
                alert('Erro ao atualizar pedido: ' + (data.error || 'Erro desconhecido'));
                btn.disabled = false;
            }
        })
        .catch(err => {
            alert('Erro de comunicação: ' + err);
            btn.disabled = false;
        });
    }

    // Event listeners para botões
    document.querySelectorAll('.btn-atender').forEach(btn => {
        btn.addEventListener('click', () => {
            if (confirm('Confirma atender este pedido?')) {
                atualizarEstadoPedido(btn.dataset.pedidoId, 'atendido', btn);
            }
        });
    });

    document.querySelectorAll('.btn-cancelar').forEach(btn => {
        btn.addEventListener('click', () => {
            if (confirm('Tem certeza que deseja cancelar este pedido?')) {
                atualizarEstadoPedido(btn.dataset.pedidoId, 'cancelado', btn);
            }
        });
    });

    // Sistema de notificação em tempo real
    function checkNewOrders() {
        fetch('check_new_orders.php')
        .then(r => r.json())
        .then(data => {
            if (data.newOrders) {
                showNotification('Novo pedido recebido!');
                // Atualiza a lista de pedidos
                location.reload();
            }
        });
    }

    function showNotification(text) {
        const notification = document.getElementById('notification');
        const notificationText = document.getElementById('notification-text');
        notificationText.textContent = text;
        notification.style.display = 'block';
        
        // Toca um som de notificação
        const audio = new Audio('/assets/sounds/notification.mp3');
        audio.play();

        setTimeout(() => {
            notification.style.display = 'none';
        }, 5000);
    }

    // Verifica novos pedidos a cada 30 segundos
    setInterval(checkNewOrders, 30000);
})();
</script>

</body>
</html>