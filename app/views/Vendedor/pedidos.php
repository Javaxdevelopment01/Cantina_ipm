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
        if (empty($input['pedido_id'])) { respondJsonError('pedido_id ausente na requisição', 400); }
        $res = $pedidoController->getPedidoItens($input['pedido_id']);
        if ($isJsonRequest) { ob_end_clean(); }
        echo json_encode(['success' => true, 'itens' => $res['itens'], 'cliente' => $res['cliente']]);
        exit;
    }

    if ($action === 'listar' || $action === 'listar_pedidos') {
        $pedidos = $pedidoController->listarPedidosPendentes();
        if ($isJsonRequest) { ob_end_clean(); }
        echo json_encode(['success' => true, 'pedidos' => $pedidos]);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        
        :root { 
            --petroleo:#012E40; 
            --dourado:#D4AF37; 
            --bg:#f7f8fa;
            --text:#333;
            --border:#dee2e6;
        }
        
        body { 
            font-family:'Segoe UI', sans-serif; 
            background:var(--bg); 
            color:var(--text);
        }
        
        .sidebar { 
            position:fixed; left:0; top:0; bottom:0; width:250px;
            background:var(--petroleo); color:white; padding:1.5rem 1rem;
            overflow-y:auto; transition:all 0.3s ease; z-index:100;
        }
        .sidebar.collapsed { width:80px; }
        
        .logo { 
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            gap:8px; font-weight:bold; font-size:1.2rem; margin-bottom:2rem; text-align:center;
            color:var(--dourado); letter-spacing:1px; transition:all 0.3s ease;
        }
        .sidebar.collapsed .logo-text { display:none; }
        
        .sidebar a {
            display:flex; align-items:center; gap:10px; color:white; text-decoration:none;
            padding:0.7rem 1rem; border-radius:0.4rem; margin-bottom:0.3rem;
            transition:all 0.3s ease; font-size:15px;
        }
        .sidebar a:hover, .sidebar a.active {
            background:var(--dourado); color:var(--petroleo); font-weight:600; transform:translateX(3px);
        }
        .sidebar.collapsed a { justify-content:center; padding:0.7rem 0; }
        .sidebar.collapsed .link-text { display:none; }
        
        .collapse-btn {
            position:absolute; bottom:20px; left:50%; transform:translateX(-50%);
            background:var(--dourado); border:none; color:var(--petroleo); font-weight:bold;
            padding:8px 14px; border-radius:5px; cursor:pointer; transition:all 0.3s;
            font-size:14px; display:flex; align-items:center; gap:6px;
        }
        .collapse-btn:hover { background:var(--petroleo); color:var(--dourado); }
        
        .main-content {
            margin-left:250px; padding:30px; transition:margin-left 0.3s ease;
        }
        .main-content.collapsed { margin-left:80px; }
        
        .page-header {
            margin-bottom:30px; padding-bottom:20px; border-bottom:2px solid var(--border);
        }
        .page-title {
            font-size:28px; font-weight:700; color:var(--petroleo); display:flex; align-items:center; gap:12px;
        }
        
        .pedido-card { 
            border-radius:12px;
            border:1px solid var(--border);
            background:white;
            margin-bottom:20px;
            transition:all 0.3s ease;
            box-shadow:0 2px 8px rgba(0,0,0,0.08);
            overflow:hidden;
        }
        .pedido-card:hover { 
            transform:translateY(-4px);
            box-shadow:0 8px 20px rgba(0,0,0,0.12);
        }
        
        .pedido-header {
            background:linear-gradient(135deg, var(--petroleo), #0d5c7a);
            color:white;
            padding:16px;
            display:flex; justify-content:space-between; align-items:center;
            flex-wrap:wrap; gap:10px;
        }
        
        .pedido-id { 
            font-size:18px; font-weight:700;
            display:flex; align-items:center; gap:8px;
        }
        
        .badge-novo {
            background:#dc3545; color:white; padding:4px 10px; border-radius:12px; 
            font-size:11px; font-weight:600;
        }
        
        .pedido-date { font-size:13px; opacity:0.9; }
        
        .pedido-body { 
            padding:16px;
            display:grid; grid-template-columns:1fr 1fr; gap:20px;
        }
        
        .info-group {
            display:flex; flex-direction:column; gap:4px;
        }
        .info-label {
            font-size:12px; font-weight:600; color:var(--petroleo); text-transform:uppercase; letter-spacing:0.5px;
        }
        .info-value {
            font-size:16px; font-weight:500; color:var(--text);
        }
        
        .total-group {
            background:linear-gradient(135deg, var(--dourado)15, transparent);
            padding:12px; border-radius:8px; border-left:4px solid var(--dourado);
        }
        .total-label { font-size:11px; font-weight:600; color:#666; text-transform:uppercase; }
        .total-value { font-size:20px; font-weight:700; color:var(--petroleo); }
        
        .itens-pedido {
            background:#f9f9f9; padding:12px; border-radius:8px; max-height:200px; overflow-y:auto;
        }
        .item-row {
            display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);
            font-size:13px;
        }
        .item-row:last-child { border-bottom:none; }
        .item-qty { 
            background:var(--petroleo); color:white; padding:2px 8px; border-radius:4px;
            font-weight:600; min-width:40px; text-align:center;
        }
        .item-name { flex:1; margin:0 10px; }
        .item-price { color:var(--petroleo); font-weight:600; }
        
        .pedido-footer { 
            background:#f8f9fa;
            padding:16px;
            display:flex; gap:10px; flex-wrap:wrap;
        }
        
        .btn {
            flex:1; min-width:120px; padding:12px; border:none; border-radius:8px;
            cursor:pointer; font-weight:600; font-size:14px;
            transition:all 0.3s ease; display:flex; align-items:center; justify-content:center; gap:6px;
        }
        
        .btn-atender { 
            background:#28a745; color:white;
        }
        .btn-atender:hover { background:#218838; transform:translateY(-2px); }
        
        .btn-cancelar { 
            background:#dc3545; color:white;
        }
        .btn-cancelar:hover { background:#c82333; transform:translateY(-2px); }
        
        .empty-state {
            text-align:center; padding:40px; color:#999;
        }
        .empty-state i { font-size:48px; margin-bottom:16px; opacity:0.3; }
        
        .notification {
            position:fixed; top:20px; right:20px; padding:16px 20px;
            background:var(--petroleo); color:var(--dourado); border-radius:8px;
            box-shadow:0 4px 12px rgba(0,0,0,0.15); display:none; z-index:9999;
            animation:slideIn 0.3s ease-out; font-weight:500;
        }
        @keyframes slideIn {
            from { transform:translateX(400px); opacity:0; }
            to { transform:translateX(0); opacity:1; }
        }
        
        @media (max-width:768px) {
            .sidebar { width:220px; transform:translateX(-100%); }
            .sidebar.show { transform:translateX(0); }
            .main-content { margin-left:0; padding:16px; }
            .pedido-body { grid-template-columns:1fr; }
            .page-title { font-size:20px; }
            .btn { font-size:12px; padding:10px; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/menu_vendedor.php'; ?>

<div class="main-content">

    <div class="page-header">
        <div class="page-title">
            <i class="fa-solid fa-receipt"></i>
            Gerenciar Pedidos
        </div>
    </div>

    <div id="lista-pedidos">
        <?php foreach ($pedidos as $pedido): ?>
        <div class="pedido-card" data-pedido-id="<?= $pedido['id'] ?>">
            <div class="pedido-header">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div class="pedido-id">
                        <i class="fa-solid fa-hashtag"></i> <?= $pedido['id'] ?>
                    </div>
                    <?php if (!$pedido['lido']): ?>
                        <span class="badge-novo"><i class="fa-solid fa-star"></i> Novo!</span>
                    <?php endif; ?>
                </div>
                <div class="pedido-date">
                    <i class="fa-solid fa-clock"></i> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?>
                </div>
            </div>
            
            <div class="pedido-body">
                <div>
                    <div class="info-group">
                        <span class="info-label"><i class="fa-solid fa-user"></i> Cliente</span>
                        <span class="info-value"><?= htmlspecialchars($pedido['cliente_nome'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
                <div>
                    <div class="info-group">
                        <span class="info-label"><i class="fa-solid fa-credit-card"></i> Pagamento</span>
                        <span class="info-value"><?= htmlspecialchars($pedido['forma_pagamento'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
                
                <div style="grid-column:1/-1;">
                    <div class="info-label"><i class="fa-solid fa-box"></i> Itens</div>
                    <div class="itens-pedido" id="itens-<?= $pedido['id'] ?>">
                        <div style="text-align:center; color:#999; padding:20px;">Carregando...</div>
                    </div>
                </div>
                
                <div class="total-group">
                    <div class="total-label">Total do Pedido</div>
                    <div class="total-value">Kz <?= number_format($pedido['total'], 2, ',', '.') ?></div>
                </div>
            </div>
            
            <div class="pedido-footer">
                <button class="btn btn-atender" data-pedido-id="<?= $pedido['id'] ?>">
                    <i class="fa-solid fa-check-circle"></i> Atender
                </button>
                <button class="btn btn-cancelar" data-pedido-id="<?= $pedido['id'] ?>">
                    <i class="fa-solid fa-times-circle"></i> Cancelar
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($pedidos)): ?>
        <div class="empty-state">
            <div><i class="fa-solid fa-inbox"></i></div>
            <h3>Nenhum Pedido Pendente</h3>
            <p>Todos os pedidos foram atendidos ou cancelados.</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<div class="notification" id="notification">
    <i class="fas fa-bell me-2"></i>
    <span id="notification-text"></span>
</div>

<script>
(() => {
    const sidebar = document.getElementById('sidebar');
    const collapseBtn = document.getElementById('collapseBtn');
    const mainContent = document.querySelector('.main-content');

    // Toggle sidebar collapse
    if (collapseBtn && sidebar) {
        collapseBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            if (mainContent) mainContent.classList.toggle('collapsed');
        });
    }

    // Carrega itens do pedido
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
                    <div class="item-row">
                        <span class="item-qty">${item.quantidade}</span>
                        <span class="item-name">${item.produto_nome}</span>
                        <span class="item-price">Kz ${(item.preco * item.quantidade).toLocaleString('pt-PT', {minimumFractionDigits: 2})}</span>
                    </div>
                `).join('');
                container.innerHTML = html;
            }
        })
        .catch(err => {
            const container = document.getElementById('itens-' + pedidoId);
            container.innerHTML = '<div style="color:red; font-size:12px;">Erro ao carregar itens</div>';
        });
    }

    // Carrega itens ao iniciar
    document.querySelectorAll('.pedido-card').forEach(card => {
        carregarItensPedido(card.dataset.pedidoId);
    });

    // Atualiza estado do pedido
    function atualizarEstadoPedido(pedidoId, estado, btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processando...';
        
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
                card.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    card.remove();
                    if (document.querySelectorAll('.pedido-card').length === 0) {
                        document.getElementById('lista-pedidos').innerHTML = `
                            <div class="empty-state">
                                <div><i class="fa-solid fa-inbox"></i></div>
                                <h3>Nenhum Pedido Pendente</h3>
                                <p>Todos os pedidos foram atendidos ou cancelados.</p>
                            </div>
                        `;
                    }
                }, 300);
                showNotification(estado === 'atendido' ? '✓ Pedido atendido!' : '✕ Pedido cancelado!');
            } else {
                alert('Erro: ' + (data.error || 'Erro desconhecido'));
                btn.disabled = false;
                btn.innerHTML = estado === 'atendido' ? '<i class="fa-solid fa-check-circle"></i> Atender' : '<i class="fa-solid fa-times-circle"></i> Cancelar';
            }
        })
        .catch(err => {
            alert('Erro: ' + err);
            btn.disabled = false;
            btn.innerHTML = estado === 'atendido' ? '<i class="fa-solid fa-check-circle"></i> Atender' : '<i class="fa-solid fa-times-circle"></i> Cancelar';
        });
    }

    // Função para recarregar a lista de pedidos via AJAX
    function recarregarListaPedidos() {
        fetch('pedidos.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: new URLSearchParams({ action: 'listar' })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('lista-pedidos');
                if (data.pedidos.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div><i class="fa-solid fa-inbox"></i></div>
                            <h3>Nenhum Pedido Pendente</h3>
                            <p>Todos os pedidos foram atendidos ou cancelados.</p>
                        </div>
                    `;
                    return;
                }

                let html = '';
                data.pedidos.forEach(pedido => {
                    const dataFormatada = new Date(pedido.data_pedido).toLocaleString('pt-PT', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'});
                    html += `
                        <div class="pedido-card" data-pedido-id="${pedido.id}">
                            <div class="pedido-header">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div class="pedido-id">
                                        <i class="fa-solid fa-hashtag"></i> ${pedido.id}
                                    </div>
                                    ${!pedido.lido ? '<span class="badge-novo"><i class="fa-solid fa-star"></i> Novo!</span>' : ''}
                                </div>
                                <div class="pedido-date">
                                    <i class="fa-solid fa-clock"></i> ${dataFormatada}
                                </div>
                            </div>
                            
                            <div class="pedido-body">
                                <div>
                                    <div class="info-group">
                                        <span class="info-label"><i class="fa-solid fa-user"></i> Cliente</span>
                                        <span class="info-value">${pedido.cliente_nome || 'N/A'}</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="info-group">
                                        <span class="info-label"><i class="fa-solid fa-credit-card"></i> Pagamento</span>
                                        <span class="info-value">${pedido.forma_pagamento || 'N/A'}</span>
                                    </div>
                                </div>
                                
                                <div style="grid-column:1/-1;">
                                    <div class="info-label"><i class="fa-solid fa-box"></i> Itens</div>
                                    <div class="itens-pedido" id="itens-${pedido.id}">
                                        <div style="text-align:center; color:#999; padding:20px;">Carregando...</div>
                                    </div>
                                </div>
                                
                                <div class="total-group">
                                    <div class="total-label">Total do Pedido</div>
                                    <div class="total-value">Kz ${parseFloat(pedido.total).toLocaleString('pt-PT', {minimumFractionDigits: 2})}</div>
                                </div>
                            </div>
                            
                            <div class="pedido-footer">
                                <button class="btn btn-atender" data-pedido-id="${pedido.id}">
                                    <i class="fa-solid fa-check-circle"></i> Atender
                                </button>
                                <button class="btn btn-cancelar" data-pedido-id="${pedido.id}">
                                    <i class="fa-solid fa-times-circle"></i> Cancelar
                                </button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
                
                // Recarregar os itens para cada card novo e vincular botões
                data.pedidos.forEach(pedido => carregarItensPedido(pedido.id));
                vincularBotoes();
            }
        });
    }

    function vincularBotoes() {
        document.querySelectorAll('.btn-atender').forEach(btn => {
            btn.onclick = () => {
                if (confirm('✓ Confirma atender este pedido?')) {
                    atualizarEstadoPedido(btn.dataset.pedidoId, 'atendido', btn);
                }
            };
        });

        document.querySelectorAll('.btn-cancelar').forEach(btn => {
            btn.onclick = () => {
                if (confirm('✕ Tem certeza que deseja cancelar este pedido?')) {
                    atualizarEstadoPedido(btn.dataset.pedidoId, 'cancelado', btn);
                }
            };
        });
    }

    // Polling: verificar novos pedidos a cada 10 segundos
    setInterval(recarregarListaPedidos, 10000);

    // Notificação
    function showNotification(text) {
        const notification = document.getElementById('notification');
        const textEl = document.getElementById('notification-text');
        textEl.textContent = text;
        notification.style.display = 'block';
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }

    // Inicialização
    vincularBotoes();
})();
</script>

<style>
    @keyframes slideOut {
        to { opacity:0; transform:translateX(-100%); }
    }
</style>


<!-- YASMIN Vendor Widget -->
<style>
/* Encapsulated styles for YASMIN Widget on Orders Page */
:root { --petroleo: #012E40; --dourado: #D4AF37; }
#yasminWidget { position:fixed; right:30px; bottom:30px; width:360px; background:#fff; border-radius:12px; box-shadow:0 12px 40px rgba(1,46,64,0.15); display:flex; flex-direction:column; z-index:9999; overflow:hidden; opacity:0; visibility:hidden; transform:translateY(20px); transition:all 0.3s ease-out; pointer-events:none; font-family:'Segoe UI', sans-serif; }
#yasminWidget.active { opacity:1; visibility:visible; transform:translateY(0); pointer-events:auto; animation:slideUpIn 0.3s ease-out; }
@keyframes slideUpIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
.yasmin-header { background:linear-gradient(135deg, var(--petroleo), #0d5c7a); color:var(--dourado); padding:14px; display:flex; align-items:center; justify-content:space-between; }
.yasmin-title { font-weight:700; font-size:1rem; display:flex; align-items:center; gap:8px; }
.yasmin-body { padding:16px; max-height:300px; overflow-y:auto; background:#f9fafb; min-height:200px; }
.yasmin-message { padding:10px 12px; border-radius:8px; margin-bottom:10px; font-size:0.95rem; line-height:1.4; }
.yasmin-message.yasmin-bot { background:#e8f0f6; color:#1f2937; border-left:3px solid var(--petroleo); }
.yasmin-message.yasmin-user { background:var(--dourado); color:var(--petroleo); margin-left:20px; text-align:right; }
.yasmin-footer { padding:12px; border-top:1px solid #e5e7eb; background:#fff; }
.yasmin-footer .input-group-sm { display:flex; gap:5px; }
.yasmin-footer .form-control { flex:1; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:0.9rem; }
#botaoYasmin { position:fixed; right:30px; bottom:30px; width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg, var(--petroleo), #0d5c7a); color:var(--dourado); border:none; z-index:9998; box-shadow:0 8px 24px rgba(1,46,64,0.2); cursor:pointer; font-size:24px; transition:transform 0.2s, box-shadow 0.2s; display:flex; align-items:center; justify-content:center; }
#botaoYasmin:hover { transform:scale(1.1); box-shadow:0 12px 32px rgba(1,46,64,0.3); }
#botaoYasmin.hidden { display:none; }
#yasminMic.recording { color: red; animation: pulse 1s infinite; }
</style>

<div id="yasminWidget">
    <div class="yasmin-header">
        <div class="yasmin-title">
            <i class="fa-solid fa-sparkles"></i> YASMIN
        </div>
        <button class="btn btn-sm btn-light" id="closeYasmin" title="Fechar" style="background:transparent; border:none; color:white;"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="yasmin-body" id="yasminBody">
        <div class="yasmin-message yasmin-bot">
            Olá! Sou a YASMIN, tua assistente.<br/>
            Posso ajudar a gerir teus pedidos e verificar o stock!
        </div>
    </div>
    <div class="yasmin-footer">
        <div class="input-group input-group-sm">
            <button id="yasminMic" class="btn btn-outline-secondary" title="Falar" type="button" style="min-width:42px;"><i class="fa-solid fa-microphone"></i></button>
            <input id="yasminInput" class="form-control" placeholder="Ex: 'Quantos pedidos pendentes?'..." />
            <button id="yasminSend" class="btn btn-success" title="Enviar" style="background:var(--petroleo); color:var(--dourado); border:none;"><i class="fa-solid fa-paper-plane"></i></button>
            <button id="yasminPlayToggle" class="btn btn-outline-primary" title="Ouvir respostas" type="button" style="min-width:42px; margin-left:6px;"><i class="fa-solid fa-volume-high"></i></button>
        </div>
    </div>
</div>

<button id="botaoYasmin" title="Abrir assistente YASMIN">
    <i class="fa-solid fa-wand-magic-sparkles"></i>
</button>

<script>
(() => {
    // Hide Global Button if exists
    const globalBtn = document.getElementById('globalIaBtn');
    if(globalBtn) globalBtn.style.display = 'none';

    // YASMIN Vendor Logic for Orders Page
    const BASE_URL = '<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), "/app/views/Vendedor"); ?>'; // Tenta inferir base url relativo
    
    const yasminBtn = document.getElementById('botaoYasmin');
    const yasminWidget = document.getElementById('yasminWidget');
    const yasminBody = document.getElementById('yasminBody');
    const yasminInput = document.getElementById('yasminInput');
    const yasminSend = document.getElementById('yasminSend');
    const closeYasminBtn = document.getElementById('closeYasmin');
    const yasminMic = document.getElementById('yasminMic');
    
    let yasminAtivo = false;
    let currentAudio = null;
    let isAudioPlaying = false;
    
    function abrirYasmin() {
        yasminWidget.classList.add('active');
        yasminAtivo = true;
        yasminInput.focus();
        yasminBtn.classList.add('hidden');
    }
    
    function fecharYasmin() {
        yasminWidget.classList.remove('active');
        yasminAtivo = false;
        yasminBtn.classList.remove('hidden');
    }
    
    function addYasminMessage(texto, isUser = false) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `yasmin-message ${isUser ? 'yasmin-user' : 'yasmin-bot'}`;
        msgDiv.innerHTML = texto;
        yasminBody.appendChild(msgDiv);
        yasminBody.scrollTop = yasminBody.scrollHeight;
    }
    
    // Auto-scroll logic
    function scrollToBottom() {
        yasminBody.scrollTop = yasminBody.scrollHeight;
    }

    async function enviarYasminMessage() {
        const msg = yasminInput.value.trim();
        if (!msg) return;
        
        addYasminMessage(msg, true);
        yasminInput.value = '';
        yasminInput.focus();
        
        const typingDiv = document.createElement('div');
        typingDiv.className = 'yasmin-message yasmin-bot';
        typingDiv.innerHTML = '<em>YASMIN está a escrever...</em>';
        yasminBody.appendChild(typingDiv);
        scrollToBottom();
        
        try {
            // Contexto simplificado para a página de pedidos (pode evoluir)
            const vendorContext = {
                pagina: 'pedidos_vendedor'
                // Idealmente aqui injetaríamos os totais, mas esta página carrega via AJAX. 
                // A API PHP do Yasmin já faz consultas ao banco, então o contexto básico ajuda, 
                // mas a API é robusta o suficiente para buscar dados sozinha se o contexto vier vazio.
            };
            
            // Endpoint correto: app/api/yasmin_vendor_api.php
            // Usando caminho relativo a partir de app/views/Vendedor/
            const apiUrl = '../../api/yasmin_vendor_api.php';
            
            let resp = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mensagem: msg, audio: true, vendor_context: vendorContext })
            });

            typingDiv.remove();
            
            if (!resp.ok) throw new Error('Erro na API');
            
            const data = await resp.json();
            
            if (data.success) {
                addYasminMessage(data.mensagem || 'Sem resposta.');
                
                 // Reproduz áudio se disponível
                if (data.audio_base64) {
                    try {
                        const audioBytes = atob(data.audio_base64);
                        const len = audioBytes.length;
                        const bytes = new Uint8Array(len);
                        for (let i = 0; i < len; i++) bytes[i] = audioBytes.charCodeAt(i);
                        const mimeType = data.audio_mime || 'audio/mpeg';
                        const blob = new Blob([bytes], { type: mimeType });
                        const url = URL.createObjectURL(blob);
                        const audio = new Audio(url);
                        
                        currentAudio = audio;
                        audio.play().catch(e => console.warn('Autoplay blocked', e));
                    } catch(e) { console.error(e); }
                }
            } else {
                addYasminMessage('Desculpa, não consegui entender.', false);
            }
            
        } catch (err) {
            typingDiv.remove();
            console.error(err);
            addYasminMessage('Erro de conexão com YASMIN.', false);
        }
    }
    
    // Listeners
    yasminBtn.addEventListener('click', abrirYasmin);
    closeYasminBtn.addEventListener('click', fecharYasmin);
    yasminSend.addEventListener('click', enviarYasminMessage);
    yasminInput.addEventListener('keypress', e => { if(e.key==='Enter') enviarYasminMessage(); });
    
    // Mic logic (Web Speech API)
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new SR();
        recognition.lang = 'pt-PT';
        
        recognition.onstart = () => yasminMic.classList.add('recording');
        recognition.onend = () => yasminMic.classList.remove('recording');
        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            yasminInput.value = transcript;
            enviarYasminMessage();
        };
        
        yasminMic.addEventListener('click', () => recognition.start());
    } else {
        yasminMic.style.display = 'none';
    }

})();
</script>

</body>
</html>