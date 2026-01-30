<?php
session_start();
if(!isset($_SESSION['vendedor_id'])) {
    header('Location: login_vendedor.php');
    exit;
}
require_once __DIR__ . '/../../../config/database.php';

// Carrega configurações (para logo e dados da loja)
$settings = [];
$settingsPath = __DIR__ . '/../../../config/settings_vendedor.json';
if (file_exists($settingsPath)) {
    $settings = json_decode(file_get_contents($settingsPath), true) ?: [];
}

// Função de segurança para output
function safe($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Buscar todas as vendas (pedidos atendidos)
$stmt = $conn->prepare("
    SELECT 
        v.id,
        v.data_venda,
        v.total,
        v.valor_pago,
        v.troco,
        v.estado,
        p.id as pedido_id,
        p.forma_pagamento,
        c.nome as cliente_nome,
        vd.nome as vendedor_nome
    FROM venda v
    JOIN pedido p ON v.id_pedido = p.id
    LEFT JOIN cliente c ON p.id_cliente = c.id
    LEFT JOIN vendedor vd ON v.id_vendedor = vd.id
    ORDER BY v.data_venda DESC
");
$stmt->execute();
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca nome do vendedor logado para usar na factura
$stmtV = $conn->prepare("SELECT nome FROM vendedor WHERE id = ?");
$stmtV->execute([$_SESSION['vendedor_id']]);
$vendedorRow = $stmtV->fetch(PDO::FETCH_ASSOC);
$vendedor_nome = $vendedorRow['nome'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendas - Cantina IPM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f7f8fa; }

        .main { 
            margin-left: 250px; 
            padding: 30px; 
            transition: 0.3s;
        }

        @media (max-width: 1024px) {
            .main {
                margin-left: 0 !important;
                padding: 80px 15px 15px !important;
            }
            .card {
                padding: 15px;
            }
            
            /* Transformar Tabela em Cards */
            .table thead { display: none; }
            .table, .table tbody, .table tr, .table td { display: block; width: 100%; }
            .table tr {
                margin-bottom: 20px;
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 4px 10px rgba(0,0,0,0.05);
                position: relative;
            }
            .table td {
                text-align: right;
                padding: 12px 0;
                border-bottom: 1px solid #f0f0f0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 14px;
            }
            .table td:last-child {
                border-bottom: none;
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
                padding-top: 15px;
                margin-top: 5px;
            }
            .table td::before {
                content: attr(data-label);
                font-weight: 700;
                color: #012E40;
                text-transform: uppercase;
                font-size: 12px;
                letter-spacing: 0.5px;
            }
            
            .btn {
                width: 100%;
                margin: 0;
                justify-content: center;
                height: 40px;
                display: flex;
                align-items: center;
            }
            .actions { width: 100%; flex-direction: column; gap: 8px; }
            .action-btn { width: 100%; margin: 0; }
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #012E40;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
        }

    /* Espaçamento entre botões de ação */
    .actions { display: inline-flex; gap: 10px; align-items: center; }
    .action-btn { display: inline-block; }

        .btn-primary {
            background: #012E40;
            color: white;
        }

        .btn-primary:hover {
            background: #024159;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-control {
            width: 100%;
            padding: 0.375rem 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            font-size: 1rem;
        }

        .valor-destaque {
            font-size: 1.2rem;
            font-weight: bold;
            color: #012E40;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 0.25rem;
        }

        .itens-list {
            max-height: 300px;
            overflow-y: auto;
            margin: 1rem 0;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 16px 0;
            font-weight: bold;
            border-top: 2px solid #012E40;
            margin-top: 16px;
        }

        .mb-3 { margin-bottom: 1rem; }
        .mt-3 { margin-top: 1rem; }
        .btn-block { width: 100%; }
        
        @media print {
            body * {
                visibility: hidden;
            }
            #impressaoFactura, #impressaoFactura * {
                visibility: visible;
            }
            #impressaoFactura {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm; /* Largura padrão de papel térmico */
            }
        }
    </style>
</head>
<body>

<?php include 'includes/menu_vendedor.php'; ?>

<div class="main">
    <div class="card">
        <h2 style="color:#012E40; margin-bottom:20px;">Histórico de Vendas</h2>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Vendedor</th>
                        <th>Total</th>
                        <th>Valor Pago</th>
                        <th>Troco</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendas as $venda): ?>
                    <tr>
                        <td data-label="ID">#<?= safe($venda['id']) ?></td>
                        <td data-label="Data"><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
                        <td data-label="Cliente"><?= safe($venda['cliente_nome']) ?></td>
                        <td data-label="Vendedor"><?= safe($venda['vendedor_nome']) ?></td>
                        <td data-label="Total">Kz <?= number_format($venda['total'], 2, ',', '.') ?></td>
                        <td data-label="Valor Pago">Kz <?= number_format($venda['valor_pago'], 2, ',', '.') ?></td>
                        <td data-label="Troco">Kz <?= number_format($venda['troco'], 2, ',', '.') ?></td>
                        <td data-label="Estado">
                            <span class="badge badge-success">
                                <?= safe($venda['estado']) ?>
                            </span>
                        </td>
                        <td data-label="Ações">
                            <div class="actions">
                                <button class="btn btn-primary action-btn" onclick="verDetalhes(<?= $venda['pedido_id'] ?>)">
                                    <i class="fas fa-eye"></i> Detalhes
                                </button>
                                <button class="btn btn-primary action-btn" onclick="gerarFactura(<?= $venda['pedido_id'] ?>)">
                                    <i class="fas fa-file-invoice"></i> Factura
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detalhes da Venda -->
<div id="modalDetalhes" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Detalhes da Venda</h3>
            <button class="modal-close" onclick="fecharModal()">&times;</button>
        </div>
        <div id="detalhesVenda">
            <div class="dados-cliente mb-3">
                <h4>Dados do Cliente</h4>
                <p id="nomeCliente"></p>
            </div>
            <div class="itens-list" id="listaItens">
                <!-- Itens serão inseridos aqui via JavaScript -->
            </div>
            <div class="pagamento-section mt-3" id="secaoPagamento">
                <h4>Pagamento</h4>
                <div class="form-group">
                    <label>Valor Total:</label>
                    <div class="valor-destaque" id="valorTotal"></div>
                </div>
                <div class="form-group">
                    <label>Valor Pago:</label>
                    <input type="number" step="0.01" class="form-control" id="valorPago" oninput="calcularTroco()">
                </div>
                <div class="form-group">
                    <label>Troco:</label>
                    <div class="valor-destaque" id="valorTroco">Kz 0,00</div>
                </div>
                <button class="btn btn-primary btn-block mt-3" onclick="finalizarVenda()">
                    Finalizar e Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

            <!-- Modal Gerar Factura (separado) -->
            <div id="modalFactura" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Gerar Factura</h3>
                        <button class="modal-close" onclick="fecharModalFactura()">&times;</button>
                    </div>
                    <div id="conteudoFactura">
                        <div class="form-group">
                            <label>Nome do Cliente</label>
                            <input type="text" id="facturaCliente" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Valor Total</label>
                            <div class="valor-destaque" id="facturaValorTotal">Kz 0,00</div>
                        </div>
                        <div class="form-group">
                            <label>Valor Pago</label>
                            <input type="number" step="0.01" id="facturaValorPago" class="form-control" oninput="calcularTrocoFactura()">
                        </div>
                        <div class="form-group">
                            <label>Troco</label>
                            <div class="valor-destaque" id="facturaTroco">Kz 0,00</div>
                        </div>
                        <div class="form-group">
                            <label>Data / Hora</label>
                            <div id="facturaDataHora" class="valor-destaque"></div>
                        </div>
                        <div class="form-group">
                            <label>Vendedor</label>
                            <div id="facturaVendedor" class="valor-destaque"><?= safe($vendedorNome) ?></div>
                        </div>
                        <button class="btn btn-primary btn-block mt-3" onclick="confirmarGerarFactura()">Gerar e Imprimir Factura</button>
                    </div>
                </div>
            </div>

<!-- CSS para Impressão -->
<style media="print">
    * { margin: 0; padding: 0; }
    body { margin: 0; padding: 0; }
    #impressaoFactura { display: block !important; }
    body > *:not(#impressaoFactura) { display: none; }
    @page { size: 58mm auto; margin: 0; }
</style>

<!-- Template da Factura Térmica (58mm - SPP-R200III Optimization) -->
<div id="impressaoFactura" style="display:none;">
    <div style="width: 58mm; margin: 0; padding: 0; font-family: 'Courier New', monospace; font-size: 16px; color: #000; line-height: 1.2; text-align: center; font-weight: bold;">
        
        <!-- Recibo Único (Preenchido dinamicamente) -->
        <div class="receipt-copy" style="padding-top: 5px;">
            <div style="margin-bottom: 8px;">
                <div style="font-size: 14px; font-weight: 800; margin-bottom: 2px;">*** <span data-fact="via_recibo">VIA CLIENTE</span> ***</div>
                <div style="font-size: 18px; font-weight: 900; text-transform: uppercase;">CANTINA IPM</div>
                <div style="font-size: 14px;">Sequele Maiombe</div>
                <div style="margin-top: 4px; border-top: 2px solid #000; border-bottom: 2px solid #000; padding: 4px 0; font-weight: 900; font-size: 16px;">
                    RECIBO: <span data-fact="numero"></span>
                </div>
            </div>

            <!-- DADOS -->
            <div style="margin-bottom: 6px; font-size: 15px; text-align: left; padding-left: 2px;">
                <div>CLI: <span data-fact="cliente"></span></div>
                <div>DATA: <span data-fact="data_atual"></span></div>
                <div>VEND: <span data-fact="vendedor"></span></div>
            </div>

            <!-- ITENS -->
            <div style="border-bottom: 2px solid #000; margin-bottom: 3px; font-weight: 900; display: flex; font-size: 15px; justify-content: center;">
                <span style="width: 55%; text-align: left;">DESC</span>
                <span style="width: 15%; text-align: center;">QTD</span>
                <span style="width: 30%; text-align: right;">TOT</span>
            </div>
            <div data-fact="itens_lista" style="margin-bottom: 6px;">
                <!-- Preenchido via JS -->
            </div>

            <!-- RESUMO -->
            <div style="border-top: 2px solid #000; padding-top: 4px; margin: 0 2px;">
                <div style="display: flex; justify-content: space-between; font-weight: 900; font-size: 20px;">
                    <span>TOTAL:</span>
                    <span data-fact="total_geral"></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 15px;">
                    <span>PAGO:</span>
                    <span data-fact="valor_pago_fmt"></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 900;">
                    <span>TROCO:</span>
                    <span data-fact="troco_fmt"></span>
                </div>
            </div>

            <!-- RODAPÉ -->
            <div style="margin-top: 10px;">
                <div style="font-weight: 900; font-size: 16px;">OBRIGADO!</div>
                <div style="font-size: 12px;">Software Cantina IPM</div>
            </div>
        </div>

        <!-- Espaço extra para o corte da impressora -->
        <div style="height: 40px;"></div>
    </div>
</div>

<script>
let dadosVendaAtual = null;
var vendedorNome = '<?= safe($vendedorNome) ?>';

// --- Funções de Impressão Sequencial ---
let etapaImpressao = 0; // 0: Inativo, 1: Cliente, 2: Vendedor

function iniciarImpressaoSequencial() {
    etapaImpressao = 1;
    preencherFactura('VIA CLIENTE');
    document.getElementById('impressaoFactura').style.display = 'block';
    
    // Pequeno delay para renderização
    setTimeout(() => {
        window.print();
    }, 200);
}

// Ouve o evento após a impressão (ou cancelamento da janela de impressão)
window.onafterprint = function() {
    if (etapaImpressao === 1) {
        // Acabou de imprimir a Via Cliente
        etapaImpressao = 2;
        
        // Configura para Via Vendedor
        preencherFactura('VIA VENDEDOR');
        
        // Pequeno delay para garantir que o spooler limpou ou o usuário está pronto
        // Em muitos navegadores, a próxima chamada de window.print() precisa ser assíncrona
        setTimeout(() => {
            if (confirm('A Via do Cliente foi impressa. Imprimir Via do Vendedor agora?')) {
                window.print();
            } else {
                // Se cancelar, encerra
                finalizarSequencia();
            }
        }, 500);

    } else if (etapaImpressao === 2) {
        // Acabou de imprimir a Via Vendedor
        finalizarSequencia();
    }
};

function finalizarSequencia() {
    etapaImpressao = 0;
    document.getElementById('impressaoFactura').style.display = 'none';
    location.reload();
}

// --- Fim Funções Sequenciais ---

function verDetalhes(pedidoId) {
    fetch('pedidos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            acao: 'get_itens',
            id_pedido: pedidoId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data || data.success === false) {
            throw new Error((data && data.error) || 'Erro ao carregar detalhes');
        }

        dadosVendaAtual = { 
            pedidoId: pedidoId,
            itens: data.itens,
            cliente: data.cliente,
            forma_pagamento: data.forma_pagamento,
            total: 0
        };

        const listaItens = document.getElementById('listaItens');
        listaItens.innerHTML = '';
        
        document.getElementById('nomeCliente').textContent = `Cliente: ${data.cliente.nome || 'Não identificado'}`;

        data.itens.forEach(item => {
            const itemTotal = item.quantidade * item.preco;
            dadosVendaAtual.total += itemTotal;
            listaItens.innerHTML += `
                <div class="item-row">
                    <div>${item.quantidade}x ${item.produto_nome}</div>
                    <div>Kz ${itemTotal.toLocaleString('pt-PT', {minimumFractionDigits: 2})}</div>
                </div>
            `;
        });

        document.getElementById('valorTotal').textContent = `Kz ${dadosVendaAtual.total.toLocaleString('pt-PT', {minimumFractionDigits: 2})}`;
        document.getElementById('valorPago').value = '';
        document.getElementById('valorTroco').textContent = 'Kz 0,00';
        document.getElementById('modalDetalhes').style.display = 'flex';
    })
    .catch(error => alert('Erro: ' + error.message));
}

function calcularTroco() {
    const valorPago = parseFloat(document.getElementById('valorPago').value) || 0;
    const troco = valorPago - dadosVendaAtual.total;
    document.getElementById('valorTroco').textContent = `Kz ${Math.max(0, troco).toLocaleString('pt-PT', {minimumFractionDigits: 2})}`;
}

function calcularTrocoFactura() {
    const valorPago = parseFloat(document.getElementById('facturaValorPago').value) || 0;
    const totalText = document.getElementById('facturaValorTotal').textContent.replace(/[Kz\s\.]/g, '').replace(',', '.');
    const total = parseFloat(totalText) || 0;
    const troco = valorPago - total;
    document.getElementById('facturaTroco').textContent = `Kz ${Math.max(0, troco).toLocaleString('pt-PT', {minimumFractionDigits: 2})}`;
}

function finalizarVenda() {
    const valorPago = parseFloat(document.getElementById('valorPago').value);
    if (!valorPago || valorPago < dadosVendaAtual.total) {
        alert('Valor insuficiente.');
        return;
    }
    const troco = valorPago - dadosVendaAtual.total;

    fetch('processar_venda.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ pedido_id: dadosVendaAtual.pedidoId, valor_pago: valorPago, troco: troco })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            fecharModal();
            iniciarImpressaoSequencial();
        } else {
            alert('Erro: ' + data.error);
        }
    })
    .catch(e => alert('Erro na requisição'));
}

function gerarFactura(pedidoId) {
    fetch('pedidos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ acao: 'get_itens', id_pedido: pedidoId })
    })
    .then(r => r.json())
    .then(data => {
        if (!data || data.success === false) throw new Error(data.error || 'Erro dados');
        
        dadosVendaAtual = { pedidoId: pedidoId, itens: data.itens, cliente: data.cliente, forma_pagamento: data.forma_pagamento, total: 0 };
        dadosVendaAtual.itens.forEach(it => { dadosVendaAtual.total += it.quantidade * it.preco; });

        document.getElementById('facturaCliente').value = dadosVendaAtual.cliente.nome || '';
        document.getElementById('facturaValorTotal').textContent = 'Kz ' + dadosVendaAtual.total.toLocaleString('pt-PT', {minimumFractionDigits: 2});
        document.getElementById('facturaValorPago').value = '';
        document.getElementById('facturaTroco').textContent = 'Kz 0,00';
        
        const now = new Date();
        document.getElementById('facturaDataHora').textContent = now.toLocaleDateString('pt-PT') + ' ' + now.toLocaleTimeString('pt-PT');
        document.getElementById('modalFactura').style.display = 'flex';
    })
    .catch(e => alert('Erro: ' + e.message));
}

function fecharModalFactura() {
    document.getElementById('modalFactura').style.display = 'none';
}

function confirmarGerarFactura() {
    const valorPago = parseFloat(document.getElementById('facturaValorPago').value);
    if (!valorPago || valorPago < dadosVendaAtual.total) {
        alert('Valor insuficiente.');
        return;
    }
    const troco = valorPago - dadosVendaAtual.total;

    fetch('processar_venda.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ pedido_id: dadosVendaAtual.pedidoId, valor_pago: valorPago, troco: troco })
    })
    .then(r => r.json())
    .then(resp => {
        if (resp.success) {
            document.getElementById('valorPago').value = valorPago; 
            fecharModalFactura();
            iniciarImpressaoSequencial();
        } else {
            alert('Erro: ' + resp.error);
        }
    })
    .catch(e => alert('Erro ao processar'));
}

function preencherFactura(nomeVia = 'VIA CLIENTE') {
    const data = new Date();
    const dataFormatada = data.toLocaleDateString('pt-PT') + ' ' + data.toLocaleTimeString('pt-PT');

    const setAll = (selector, value) => {
        document.querySelectorAll(selector).forEach(el => {
            el.innerHTML = value;
        });
    };

    setAll('[data-fact="via_recibo"]', nomeVia);
    setAll('[data-fact="numero"]', String(dadosVendaAtual.pedidoId).padStart(6, '0'));
    setAll('[data-fact="data_atual"]', dataFormatada);
    setAll('[data-fact="cliente"]', (dadosVendaAtual.cliente.nome || 'Consumidor Final').substring(0, 18).toUpperCase());
    setAll('[data-fact="vendedor"]', vendedorNome.substring(0, 15).toUpperCase());

    let itensHTML = '';
    dadosVendaAtual.itens.forEach(item => {
        const preco = parseFloat(item.preco) || 0;
        const total = parseFloat(item.quantidade) * preco;
        const nomeProd = (item.produto_nome || '').substring(0, 15).toUpperCase();
        itensHTML += `
            <div style="display: flex; margin-bottom: 2px; font-size: 16px; justify-content: center; font-weight: bold;">
                <span style="width: 55%; text-align: left;">${nomeProd}</span>
                <span style="width: 15%; text-align: center;">${item.quantidade}</span>
                <span style="width: 30%; text-align: right; font-weight: 900;">${total.toFixed(2)}</span>
            </div>
        `;
    });
    setAll('[data-fact="itens_lista"]', itensHTML);

    const valorPagoEl = document.getElementById('facturaValorPago') || document.getElementById('valorPago');
    const valorPago = parseFloat(valorPagoEl ? valorPagoEl.value : 0) || 0;
    const troco = Math.max(0, valorPago - dadosVendaAtual.total);

    setAll('[data-fact="total_geral"]', `${dadosVendaAtual.total.toFixed(2)} Kz`);
    setAll('[data-fact="valor_pago_fmt"]', `${valorPago.toFixed(2)} Kz`);
    setAll('[data-fact="troco_fmt"]', `${troco.toFixed(2)} Kz`);
}

function fecharModal() {
    document.getElementById('modalDetalhes').style.display = 'none';
}

// Fecha o modal se clicar fora dele
window.onclick = function(event) {
    const modalDetalhes = document.getElementById('modalDetalhes');
    const modalFactura = document.getElementById('modalFactura');
    if (event.target == modalDetalhes) {
        modalDetalhes.style.display = 'none';
    }
    if (event.target == modalFactura) {
        modalFactura.style.display = 'none';
    }
}

// (removida) duplicate gerarFactura — a função correta está definida acima.
</script>

</body>
</html>
