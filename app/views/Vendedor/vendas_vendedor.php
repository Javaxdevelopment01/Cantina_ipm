<?php
session_start();
if(!isset($_SESSION['vendedor_id'])) {
    header('Location: login_vendedor.php');
    exit;
}
require_once __DIR__ . '/../../../config/database.php';

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
                        <td>#<?= safe($venda['id']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
                        <td><?= safe($venda['cliente_nome']) ?></td>
                        <td><?= safe($venda['vendedor_nome']) ?></td>
                        <td>Kz <?= number_format($venda['total'], 2, ',', '.') ?></td>
                        <td>Kz <?= number_format($venda['valor_pago'], 2, ',', '.') ?></td>
                        <td>Kz <?= number_format($venda['troco'], 2, ',', '.') ?></td>
                        <td>
                            <span class="badge badge-success">
                                <?= safe($venda['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-primary action-btn" onclick="verDetalhes(<?= $venda['pedido_id'] ?>)">
                                    <i class="fas fa-eye"></i> Ver Detalhes
                                </button>
                                <button class="btn btn-primary action-btn" onclick="gerarFactura(<?= $venda['pedido_id'] ?>)">
                                    <i class="fas fa-file-invoice"></i> Gerar Factura
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
                            <div id="facturaVendedor" class="valor-destaque"><?= safe($vendedor_nome) ?></div>
                        </div>
                        <button class="btn btn-primary btn-block mt-3" onclick="confirmarGerarFactura()">Gerar e Imprimir Factura</button>
                    </div>
                </div>
            </div>

<!-- Template da Factura (invisível até impressão) -->
<div id="impressaoFactura" style="display:none;">
    <div style="width:80mm; font-family:monospace; font-size:12px; color:#000;">
        <div style="text-align:center; margin-bottom:6px;">
            <div style="font-size:14px; font-weight:bold;">CANTINA IPM</div>
            <div style="font-size:11px;">FACTURA SIMPLIFICADA</div>
        </div>

        <div id="facturaDetalhesVenda" style="font-size:11px; margin-bottom:6px;">
            <!-- Dados da venda serão inseridos aqui -->
        </div>

        <div id="facturaItens" style="font-size:11px;">
            <!-- Itens serão inseridos aqui -->
        </div>

        <div style="border-top:1px dashed #000;margin:8px 0;"></div>

        <div id="facturaTotais" style="font-size:11px;">
            <!-- Totais serão inseridos aqui -->
        </div>

        <div style="text-align:center;font-size:10px;margin-top:8px;">
            Obrigado pela preferência!
        </div>
    </div>
</div>

<script>
let dadosVendaAtual = null;
// Nome do vendedor (injetado pelo servidor)
var vendedorNome = '<?= safe($vendedor_nome) ?>';

function verDetalhes(pedidoId) {
    // Busca os itens do pedido
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
        // Validação básica da resposta
        if (!data) {
            throw new Error('Resposta vazia do servidor');
        }
        if (data.success === false) {
            // Mensagem de erro retornada pelo servidor
            const serverMsg = data.error || 'Resposta inválida do servidor';
            throw new Error(serverMsg);
        }
        if (!data.itens || !data.cliente) {
            throw new Error('Dados incompletos recebidos do servidor');
        }

        dadosVendaAtual = { 
            pedidoId: pedidoId,
            itens: data.itens,
            cliente: data.cliente,
            total: 0
        };

        const listaItens = document.getElementById('listaItens');
        listaItens.innerHTML = '';
        
        // Mostra nome do cliente
        document.getElementById('nomeCliente').textContent = 
            `Cliente: ${data.cliente.nome || 'Não identificado'}`;

        // Adiciona cada item à lista
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

        // Mostra o total
        const totalFormatado = dadosVendaAtual.total.toLocaleString('pt-PT', {minimumFractionDigits: 2});
        document.getElementById('valorTotal').textContent = `Kz ${totalFormatado}`;
        
        // Limpa campos do pagamento
        document.getElementById('valorPago').value = '';
        document.getElementById('valorTroco').textContent = 'Kz 0,00';

        // Mostra o modal
        document.getElementById('modalDetalhes').style.display = 'flex';
    })
    .catch(error => {
        console.error('Erro ao buscar detalhes:', error);
        // Mostrar mensagem mais específica quando possível
        alert('Erro ao carregar os detalhes da venda: ' + (error.message || error));
    });
}

function calcularTroco() {
    const valorPago = parseFloat(document.getElementById('valorPago').value) || 0;
    const troco = valorPago - dadosVendaAtual.total;
    document.getElementById('valorTroco').textContent = 
        `Kz ${Math.max(0, troco).toLocaleString('pt-PT', {minimumFractionDigits: 2})}`;
}

// Calcular troco no modal da factura
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
        alert('Por favor, insira um valor pago válido e suficiente.');
        return;
    }

    const troco = valorPago - dadosVendaAtual.total;

    // Atualiza a venda com os valores de pagamento
    fetch('processar_venda.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            pedido_id: dadosVendaAtual.pedidoId,
            valor_pago: valorPago,
            troco: troco
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            preencherFactura();
            document.getElementById('impressaoFactura').style.display = 'block';
            setTimeout(() => { window.print(); }, 200);
            setTimeout(() => { location.reload(); }, 1000); // Recarrega para atualizar a lista
        } else {
            alert('Erro ao finalizar a venda: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao finalizar a venda');
    });
}

// Abrir modal de factura sem abrir o modal de detalhes
function gerarFactura(pedidoId) {
    // Busca itens e cliente para calcular o total e preencher o nome
    fetch('pedidos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ acao: 'get_itens', id_pedido: pedidoId })
    })
    .then(r => r.json())
    .then(data => {
        if (!data || data.success === false) {
            throw new Error((data && data.error) ? data.error : 'Resposta inválida');
        }

        dadosVendaAtual = { pedidoId: pedidoId, itens: data.itens, cliente: data.cliente, total: 0 };
        dadosVendaAtual.itens.forEach(it => { dadosVendaAtual.total += it.quantidade * it.preco; });

        // Preenche o modal da factura
        document.getElementById('facturaCliente').value = dadosVendaAtual.cliente.nome || '';
        document.getElementById('facturaValorTotal').textContent = 'Kz ' + dadosVendaAtual.total.toLocaleString('pt-PT', {minimumFractionDigits: 2});
        document.getElementById('facturaValorPago').value = '';
        document.getElementById('facturaTroco').textContent = 'Kz 0,00';
        const now = new Date();
        document.getElementById('facturaDataHora').textContent = now.toLocaleDateString('pt-PT') + ' ' + now.toLocaleTimeString('pt-PT');

        // mostrar modal da factura
        document.getElementById('modalFactura').style.display = 'flex';
    })
    .catch(err => {
        console.error('Erro ao abrir modal da factura:', err);
        alert('Erro ao carregar dados para a factura: ' + (err.message || err));
    });
}

function fecharModalFactura() {
    document.getElementById('modalFactura').style.display = 'none';
}

// Confirmar gerar factura: atualiza venda e imprime
function confirmarGerarFactura() {
    const valorPago = parseFloat(document.getElementById('facturaValorPago').value);
    if (!valorPago || valorPago < dadosVendaAtual.total) {
        alert('Por favor, insira um valor pago válido e suficiente.');
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
            // preencher e imprimir a factura (reaproveitar preencherFactura)
            // Ajusta campos usados por preencherFactura
            document.getElementById('valorPago').value = valorPago; // para preencherFactura ler
            // fechar modal da factura antes de imprimir
            fecharModalFactura();
            preencherFactura();
            // garantir que o template esteja visível para impressão
            document.getElementById('impressaoFactura').style.display = 'block';
            setTimeout(() => { window.print(); }, 200);
            // após impressão recarregar
            setTimeout(() => { location.reload(); }, 1000);
        } else {
            throw new Error(resp.error || 'Erro no servidor');
        }
    })
    .catch(err => {
        console.error('Erro ao processar factura:', err);
        alert('Erro ao processar a factura: ' + (err.message || err));
    });
}

function preencherFactura() {
    const data = new Date();
    const dataFormatada = data.toLocaleDateString('pt-PT');
    const horaFormatada = data.toLocaleTimeString('pt-PT');

    // Preenche os detalhes da venda
    document.getElementById('facturaDetalhesVenda').innerHTML = `
        <div>Pedido: #${dadosVendaAtual.pedidoId}</div>
        <div>Data: ${dataFormatada} ${horaFormatada}</div>
        <div>Cliente: ${dadosVendaAtual.cliente.nome || 'Não identificado'}</div>
        <div>Vendedor: ${vendedorNome}</div>
    `;

    // Preenche os itens (formato compacto para térmica)
    let itensHTML = '<div style="width:100%;">';
    itensHTML += '<div style="display:flex; font-weight:bold; border-bottom:1px solid #000; padding-bottom:4px;">'
        + '<div style="flex:1;">Produto</div>'
        + '<div style="width:40px; text-align:right;">Qtd</div>'
        + '<div style="width:70px; text-align:right;">Preço</div>'
        + '</div>';

    dadosVendaAtual.itens.forEach(item => {
        const total = item.quantidade * item.preco;
        itensHTML += '<div style="display:flex; padding:6px 0; border-bottom:1px dashed #ccc;">'
            + `<div style="flex:1;">${item.produto_nome}</div>`
            + `<div style="width:40px; text-align:right;">${item.quantidade}x</div>`
            + `<div style="width:70px; text-align:right;">Kz ${total.toLocaleString('pt-PT', {minimumFractionDigits: 2})}</div>`
            + '</div>';
    });
    itensHTML += '</div>';
    document.getElementById('facturaItens').innerHTML = itensHTML;

    // Preenche os totais
    const valorPago = parseFloat(document.getElementById('valorPago').value);
    const troco = valorPago - dadosVendaAtual.total;
    document.getElementById('facturaTotais').innerHTML = `
        <div style="display:flex; justify-content:space-between; padding-top:6px;">
            <div style="font-weight:bold;">Total:</div>
            <div>Kz ${dadosVendaAtual.total.toLocaleString('pt-PT', {minimumFractionDigits: 2})}</div>
        </div>
        <div style="display:flex; justify-content:space-between;">
            <div>Valor Pago:</div>
            <div>Kz ${valorPago.toLocaleString('pt-PT', {minimumFractionDigits: 2})}</div>
        </div>
        <div style="display:flex; justify-content:space-between; border-top:1px dashed #000; padding-top:6px; font-weight:bold;">
            <div>Troco:</div>
            <div>Kz ${troco.toLocaleString('pt-PT', {minimumFractionDigits: 2})}</div>
        </div>
    `;
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
