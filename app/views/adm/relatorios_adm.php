<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_adm.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

function safe($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Base de dados para tabelas (reaproveita queries do relatorios_vendedor.php)
// Vendas
$stmt = $conn->prepare("
    SELECT 
        v.id,
        v.data_venda,
        v.total,
        v.valor_pago,
        v.troco,
        v.estado,
        p.id AS pedido_id,
        c.nome AS cliente_nome,
        vd.nome AS vendedor_nome
    FROM venda v
    JOIN pedido p ON v.id_pedido = p.id
    LEFT JOIN cliente c ON p.id_cliente = c.id
    LEFT JOIN vendedor vd ON v.id_vendedor = vd.id
    ORDER BY v.data_venda DESC
");
$stmt->execute();
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Produtos
$stmt = $conn->prepare("
    SELECT p.*, c.nome AS categoria_nome 
    FROM produto p 
    LEFT JOIN categoria c ON p.categoria_id = c.id 
    ORDER BY p.id DESC
");
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Categorias
$stmt = $conn->prepare("SELECT id, nome FROM categoria ORDER BY nome ASC");
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vendedores
$stmt = $conn->prepare("SELECT id, nome FROM vendedor ORDER BY nome ASC");
$stmt->execute();
$vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Relatórios - ADM | Cantina IPM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background:#f3f4f6; color:#111827; }

        .main-adm-page {
            margin-left: 250px;
            padding: 26px;
        }
        @media (max-width: 767.98px) {
            .main-adm-page {
                margin-left: 0;
                padding: 18px 16px 26px;
            }
        }

        .card-pro {
            background: #fff;
            border-radius:16px;
            box-shadow: 0 10px 32px rgba(15,23,42,0.06);
            padding:20px;
            border:1px solid rgba(148,163,184,0.18);
        }

        .indicators {
            display:flex;
            gap:12px;
            margin-bottom:18px;
            flex-wrap:wrap;
        }
        .indicator {
            background:#fff;
            padding:14px;
            border-radius:12px;
            box-shadow: 0 4px 14px rgba(15,23,42,0.06);
            flex:1;
            min-width:180px;
        }
        .indicator .muted { font-size:0.8rem; color:#6b7280; margin-bottom:0.35rem; }
        .indicator .value { font-size:1.4rem; font-weight:700; color:#0f172a; }

        .section-title {
            font-size:1.05rem;
            font-weight:600;
            color:#0f172a;
        }

        .small-muted { font-size:0.9rem; color:#6b7280; }

        .report-actions button {
            margin-left:0.3rem;
        }

        .filters-row {
            display:flex;
            flex-wrap:wrap;
            gap:0.4rem;
            align-items:flex-end;
        }

        @media print {
            * { 
                margin: 0; 
                padding: 0; 
                visibility: hidden; 
                box-sizing: border-box;
            }
            html, body { width: 100%; height: 100%; }
            body { background: white; color: #000; }
            #reportArea, #reportArea * { 
                visibility: visible;
            }
            #reportArea { 
                position: absolute; 
                left: 0; 
                top: 0; 
                width: 100%; 
                padding: 20mm;
            }
            .no-print { display: none !important; }
            
            /* Cabeçalho profissional */
            #reportArea h1, #reportArea h2, #reportArea h3, #reportArea h4, #reportArea h5 {
                margin-top: 12pt;
                margin-bottom: 8pt;
                font-weight: 700;
                color: #000;
            }
            
            /* Tabela de relatório */
            #tableVendas, #tableProdutos {
                width: 100%;
                border-collapse: collapse;
                margin: 15pt 0;
                font-size: 10pt;
                line-height: 1.2;
            }
            
            #tableVendas thead, #tableProdutos thead {
                background-color: #012E40;
                color: #fff;
            }
            
            #tableVendas th, #tableProdutos th {
                padding: 10pt;
                text-align: left;
                font-weight: 700;
                border: 1px solid #012E40;
                font-size: 10pt;
            }
            
            #tableVendas td, #tableProdutos td {
                padding: 8pt;
                border: 1px solid #ddd;
                font-size: 10pt;
                word-wrap: break-word;
            }
            
            #tableVendas tbody tr:nth-child(odd),
            #tableProdutos tbody tr:nth-child(odd) {
                background-color: #f9f9f9;
            }
            
            #tableVendas tbody tr:nth-child(even),
            #tableProdutos tbody tr:nth-child(even) {
                background-color: #fff;
            }
            
            /* Rodapé de página */
            #reportArea::after {
                content: attr(data-footer);
                display: block;
                margin-top: 20pt;
                padding-top: 10pt;
                border-top: 1px solid #ccc;
                font-size: 9pt;
                color: #666;
                text-align: center;
            }
            
            /* Quebras de página */
            #tableVendas, #tableProdutos {
                page-break-inside: avoid;
            }
            
            #tableVendas tr, #tableProdutos tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/menu_adm.php'; ?>

<div class="main-adm-page">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="section-title mb-1">Relatórios da Cantina</h2>
            <div class="small-muted">Visão consolidada de vendas, produtos e desempenho dos vendedores.</div>
        </div>
        <div class="report-actions no-print">
            <button class="btn btn-outline-secondary btn-sm" id="btnPrint">
                <i class="fa fa-print"></i> Imprimir
            </button>
            <button class="btn btn-outline-primary btn-sm" id="btnPdf">
                <i class="fa fa-file-pdf"></i> Exportar PDF
            </button>
            <button class="btn btn-outline-success btn-sm" id="btnExcel">
                <i class="fa fa-file-excel"></i> Exportar Excel
            </button>
        </div>
    </div>

    <div class="card-pro mb-3">
        <div class="indicators" id="indicatorsRow">
            <div class="indicator">
                <div class="muted">Total de Vendas</div>
                <div class="value" id="ind_total_vendas">Kz 0,00</div>
            </div>
            <div class="indicator">
                <div class="muted">Total de Clientes</div>
                <div class="value" id="ind_total_clientes">0</div>
            </div>
            <div class="indicator">
                <div class="muted">Produtos em Stock</div>
                <div class="value" id="ind_produtos_stock">0</div>
            </div>
            <div class="indicator">
                <div class="muted">Produtos Baixo Stock (&lt;5)</div>
                <div class="value" id="ind_produtos_baixo">0</div>
            </div>
            <div class="indicator">
                <div class="muted">Vendas Mês (atual)</div>
                <div class="value" id="ind_vendas_mes">Kz 0,00</div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-7 mb-3 mb-md-0">
                <div class="card-pro p-3">
                    <h5>Vendas Mensais (últimos 12 meses)</h5>
                    <canvas id="chartVendasMensais" height="160"></canvas>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card-pro p-3">
                    <h5>Top Produtos (valor em stock)</h5>
                    <canvas id="chartTopProdutos" height="160"></canvas>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12">
                <div class="card-pro p-3">
                    <h5>Vendas por Vendedor</h5>
                    <canvas id="chartVendasVendedor" height="80"></canvas>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs" id="relTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="vendas-tab" data-bs-toggle="tab" data-bs-target="#vendas" type="button">
                    Vendas
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="produtos-tab" data-bs-toggle="tab" data-bs-target="#produtos" type="button">
                    Produtos
                </button>
            </li>
        </ul>

        <div class="tab-content mt-3" id="relTabContent">
            <div class="tab-pane fade show active" id="vendas">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <div class="filters-row">
                        <label class="small-muted">De</label>
                        <input type="date" id="venda_from" class="form-control form-control-sm" />
                        <label class="small-muted">Até</label>
                        <input type="date" id="venda_to" class="form-control form-control-sm" />
                        <select id="filter_vendedor" class="form-select form-select-sm">
                            <option value="">Todos vendedores</option>
                            <?php foreach ($vendedores as $vd): ?>
                                <option value="<?= safe($vd['nome']) ?>"><?= safe($vd['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filter_estado" class="form-select form-select-sm">
                            <option value="">Todos estados</option>
                            <option value="finalizada">finalizada</option>
                            <option value="pendente">pendente</option>
                        </select>
                        <button class="btn btn-sm btn-secondary" id="btnAplicarFiltroVendas">Aplicar</button>
                        <button class="btn btn-sm btn-outline-secondary" id="btnLimparFiltroVendas">Limpar</button>
                    </div>
                </div>

                <div id="reportArea">
                    <div style="text-align: center; margin-bottom: 12px;">
                        <!-- Logo IPM -->
                        <img src="/assets/images/ipm_logo.png" alt="Logo IPM" style="max-width: 60px; height: auto; margin-bottom: 8px;">
                        <h3 style="margin: 6px 0 2px 0; color: #012E40; font-size: 14pt;">INSTITUTO POLITÉCNICO MAIOMBE</h3>
                        <h4 style="margin: 2px 0 6px 0; color: #666; font-size: 11pt; font-weight: normal;">CANTINA IPM</h4>
                        <p style="margin: 0; font-size: 8pt; color: #666;">Relatório de Vendas</p>
                        <p style="margin: 3px 0 0 0; font-size: 7pt; color: #999;">Gerado em <?= date('d/m/Y H:i') ?></p>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="tableVendas" style="font-size: 8pt; margin-bottom: 0;">
                            <thead class="table-dark">
                            <tr>
                                <th style="width: 4%; padding: 5pt;">#</th>
                                <th style="width: 10%; padding: 5pt;">Data</th>
                                <th style="width: 15%; padding: 5pt;">Cliente</th>
                                <th style="width: 20%; padding: 5pt;">Vendedor</th>
                                <th style="width: 9%; padding: 5pt; text-align: right;">Total</th>
                                <th style="width: 9%; padding: 5pt; text-align: right;">V.Pago</th>
                                <th style="width: 9%; padding: 5pt; text-align: right;">Troco</th>
                                <th style="width: 8%; padding: 5pt;">Est.</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($vendas as $row): ?>
                                <tr data-data="<?= safe($row['data_venda']) ?>" data-vendedor="<?= safe($row['vendedor_nome']) ?>" data-estado="<?= safe($row['estado']) ?>" style="font-size: 8pt;">
                                    <td style="padding: 4pt;"><?= safe($row['id']) ?></td>
                                    <td style="padding: 4pt;"><?= date('d/m/y', strtotime($row['data_venda'])) ?></td>
                                    <td style="padding: 4pt;"><?= safe(substr($row['cliente_nome'] ?? 'N/A', 0, 18)) ?></td>
                                    <td style="padding: 4pt;"><?= safe($row['vendedor_nome'] ?? 'N/A') ?></td>
                                    <td style="text-align: right; padding: 4pt;"><?= number_format($row['total'], 2, ',', '.') ?></td>
                                    <td style="text-align: right; padding: 4pt;"><?= number_format($row['valor_pago'], 2, ',', '.') ?></td>
                                    <td style="text-align: right; padding: 4pt;"><?= number_format($row['troco'], 2, ',', '.') ?></td>
                                    <td style="padding: 4pt; font-size: 7pt;"><?= safe($row['estado']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="produtos">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <div class="filters-row">
                        <input type="text" id="produto_search" class="form-control form-control-sm" placeholder="Procurar produto..." />
                        <select id="filter_categoria" class="form-select form-select-sm">
                            <option value="">Todas categorias</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= safe($c['nome']) ?>"><?= safe($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-secondary" id="btnAplicarFiltroProdutos">Aplicar</button>
                        <button class="btn btn-sm btn-outline-secondary" id="btnLimparFiltroProdutos">Limpar</button>
                    </div>
                </div>

                <div id="reportArea">
                    <div style="text-align: center; margin-bottom: 12px;">
                        <!-- Logo IPM -->
                        <img src="/assets/images/ipm_logo.png" alt="Logo IPM" style="max-width: 60px; height: auto; margin-bottom: 8px;">
                        <h3 style="margin: 6px 0 2px 0; color: #012E40; font-size: 14pt;">INSTITUTO POLITÉCNICO MAIOMBE</h3>
                        <h4 style="margin: 2px 0 6px 0; color: #666; font-size: 11pt; font-weight: normal;">CANTINA IPM</h4>
                        <p style="margin: 0; font-size: 8pt; color: #666;">Relatório de Produtos</p>
                        <p style="margin: 3px 0 0 0; font-size: 7pt; color: #999;">Gerado em <?= date('d/m/Y H:i') ?></p>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="tableProdutos" style="font-size: 8pt; margin-bottom: 0;">
                            <thead class="table-dark">
                            <tr>
                                <th style="width: 5%; padding: 5pt;">#</th>
                                <th style="width: 35%; padding: 5pt;">Nome</th>
                                <th style="width: 25%; padding: 5pt;">Categoria</th>
                                <th style="width: 15%; padding: 5pt; text-align: center;">Qtd</th>
                                <th style="width: 15%; padding: 5pt; text-align: right;">Preço</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($produtos as $p): ?>
                                <tr data-categoria="<?= safe($p['categoria_nome']) ?>" style="font-size: 8pt;">
                                    <td style="padding: 4pt;"><?= safe($p['id']) ?></td>
                                    <td style="padding: 4pt;"><?= safe(substr($p['nome'], 0, 30)) ?></td>
                                    <td style="padding: 4pt;"><?= safe(substr($p['categoria_nome'], 0, 20)) ?></td>
                                    <td style="text-align: center; padding: 4pt;"><?= (int)$p['quantidade'] ?></td>
                                    <td style="text-align: right; padding: 4pt;"><?= number_format($p['preco'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bibliotecas -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>

<script>
    function applyFiltroVendas(){
        const from = document.getElementById('venda_from').value;
        const to = document.getElementById('venda_to').value;
        const vendedor = document.getElementById('filter_vendedor').value;
        const estado = document.getElementById('filter_estado').value;

        document.querySelectorAll('#tableVendas tbody tr').forEach(tr => {
            const data = tr.dataset.data || '';
            const rowVendedor = tr.dataset.vendedor || '';
            const rowEstado = tr.dataset.estado || '';
            let show = true;

            if (from) {
                show = show && (new Date(data) >= new Date(from + 'T00:00:00'));
            }
            if (to) {
                show = show && (new Date(data) <= new Date(to + 'T23:59:59'));
            }
            if (vendedor) {
                show = show && (rowVendedor === vendedor);
            }
            if (estado) {
                show = show && (rowEstado === estado);
            }

            tr.style.display = show ? '' : 'none';
        });
    }

    function applyFiltroProdutos(){
        const search = document.getElementById('produto_search').value.toLowerCase();
        const cat = document.getElementById('filter_categoria').value;
        document.querySelectorAll('#tableProdutos tbody tr').forEach(tr => {
            const nome = tr.cells[1].textContent.toLowerCase();
            const categoria = tr.dataset.categoria || '';
            let show = true;
            if (search) {
                show = show && nome.includes(search);
            }
            if (cat) {
                show = show && (categoria === cat);
            }
            tr.style.display = show ? '' : 'none';
        });
    }

    async function exportTableToPDF(tableId, title){
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'pt', 'a4');
        const pageWidth = doc.internal.pageSize.getWidth();
        let currentY = 40;

        doc.setFontSize(16);
        doc.text(title, 40, currentY);
        currentY += 30;

        doc.setFontSize(12);
        doc.text('Indicadores:', 40, currentY);
        currentY += 20;

        const indicators = [
            ['Total de Vendas:', document.getElementById('ind_total_vendas').textContent],
            ['Total de Clientes:', document.getElementById('ind_total_clientes').textContent],
            ['Produtos em Stock:', document.getElementById('ind_produtos_stock').textContent],
            ['Produtos Baixo Stock:', document.getElementById('ind_produtos_baixo').textContent],
            ['Vendas Mês:', document.getElementById('ind_vendas_mes').textContent]
        ];
        doc.setFontSize(10);
        indicators.forEach(ind => {
            doc.text(ind[0] + ' ' + ind[1], 40, currentY);
            currentY += 15;
        });
        currentY += 10;

        const res = doc.autoTableHtmlToJson(document.getElementById(tableId));
        doc.autoTable({
            head: [res.columns],
            body: res.data,
            startY: currentY,
            styles: {fontSize: 9},
            margin: {top: 40, right: 40, bottom: 40, left: 40}
        });

        doc.save(title.replace(/\s+/g,'_') + '.pdf');
    }

    function exportTableToExcel(tableId, filename){
        const fullReport = document.createElement('table');

        const indRow = document.createElement('tr');
        ['Total de Vendas', 'Total de Clientes', 'Produtos em Stock', 'Produtos Baixo Stock', 'Vendas Mês'].forEach(header => {
            const th = document.createElement('th');
            th.textContent = header;
            indRow.appendChild(th);
        });
        fullReport.appendChild(indRow);

        const valRow = document.createElement('tr');
        [
            document.getElementById('ind_total_vendas').textContent,
            document.getElementById('ind_total_clientes').textContent,
            document.getElementById('ind_produtos_stock').textContent,
            document.getElementById('ind_produtos_baixo').textContent,
            document.getElementById('ind_vendas_mes').textContent
        ].forEach(value => {
            const td = document.createElement('td');
            td.textContent = value;
            valRow.appendChild(td);
        });
        fullReport.appendChild(valRow);

        fullReport.appendChild(document.createElement('tr'));

        const originalTable = document.getElementById(tableId);
        Array.from(originalTable.rows).forEach(row => {
            fullReport.appendChild(row.cloneNode(true));
        });

        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(fullReport, {raw:true});
        XLSX.utils.book_append_sheet(wb, ws, 'Relatório');
        XLSX.writeFile(wb, filename + '.xlsx');
    }

    let chartVendasMensais = null, chartTopProdutos = null, chartVendasVendedor = null;

    function fetchRelatorioDataAdm(){
        fetch('../../../app/api/relatorio_data.php', {
            credentials: 'same-origin',
            headers: { 'Cache-Control': 'no-cache' }
        })
            .then(r => r.json())
            .then(payload => {
                if (!payload.success) {
                    return;
                }
                const ind = payload.indicadores || {};
                document.getElementById('ind_total_vendas').textContent =
                    'Kz ' + Number(ind.total_vendas || 0).toLocaleString('pt-PT', {minimumFractionDigits:2});
                document.getElementById('ind_total_clientes').textContent = ind.total_clientes || 0;
                document.getElementById('ind_produtos_stock').textContent = ind.produtos_em_stock || 0;
                document.getElementById('ind_produtos_baixo').textContent = ind.produtos_baixo_stock || 0;
                document.getElementById('ind_vendas_mes').textContent =
                    'Kz ' + Number(ind.vendas_mes_atual || 0).toLocaleString('pt-PT', {minimumFractionDigits:2});

                const labels = (payload.series && payload.series.labels) || [];
                const data = (payload.series && payload.series.data) || [];
                if (!chartVendasMensais) {
                    const ctx = document.getElementById('chartVendasMensais').getContext('2d');
                    chartVendasMensais = new Chart(ctx, {
                        type:'bar',
                        data:{labels, datasets:[{label:'Total (Kz)', data, backgroundColor:'#0d6efd'}]},
                        options:{responsive:true, plugins:{legend:{display:false}}}
                    });
                } else {
                    chartVendasMensais.data.labels = labels;
                    chartVendasMensais.data.datasets[0].data = data;
                    chartVendasMensais.update();
                }

                const top = payload.top_produtos || [];
                const topLabels = top.map(t => t.nome);
                const topData = top.map(t => Number(t.valor_estoque || t.total_vendido || 0));
                if (!chartTopProdutos) {
                    const ctx2 = document.getElementById('chartTopProdutos').getContext('2d');
                    chartTopProdutos = new Chart(ctx2, {
                        type:'doughnut',
                        data:{labels:topLabels, datasets:[{data:topData, backgroundColor:['#f59e0b','#f97316','#ef4444','#0ea5e9','#34d399','#60a5fa','#7c3aed','#a3e635']}]},
                        options:{responsive:true, plugins:{legend:{position:'right'}}}
                    });
                } else {
                    chartTopProdutos.data.labels = topLabels;
                    chartTopProdutos.data.datasets[0].data = topData;
                    chartTopProdutos.update();
                }

                const vv = payload.vendas_por_vendedor || [];
                const vvLabels = vv.map(x => x.nome);
                const vvData = vv.map(x => Number(x.total_vendas || 0));
                if (!chartVendasVendedor) {
                    const ctx3 = document.getElementById('chartVendasVendedor').getContext('2d');
                    chartVendasVendedor = new Chart(ctx3, {
                        type:'bar',
                        data:{labels:vvLabels, datasets:[{label:'Total (Kz)', data:vvData, backgroundColor:'#0ea5e9'}]},
                        options:{indexAxis:'y', responsive:true, plugins:{legend:{display:false}}}
                    });
                } else {
                    chartVendasVendedor.data.labels = vvLabels;
                    chartVendasVendedor.data.datasets[0].data = vvData;
                    chartVendasVendedor.update();
                }
            })
            .catch(err => console.error('Erro relatório ADM:', err));
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('btnAplicarFiltroVendas').addEventListener('click', applyFiltroVendas);
        document.getElementById('btnLimparFiltroVendas').addEventListener('click', () => {
            document.getElementById('venda_from').value = '';
            document.getElementById('venda_to').value = '';
            document.getElementById('filter_vendedor').value = '';
            document.getElementById('filter_estado').value = '';
            applyFiltroVendas();
        });

        document.getElementById('btnAplicarFiltroProdutos').addEventListener('click', applyFiltroProdutos);
        document.getElementById('btnLimparFiltroProdutos').addEventListener('click', () => {
            document.getElementById('produto_search').value = '';
            document.getElementById('filter_categoria').value = '';
            applyFiltroProdutos();
        });

        document.getElementById('btnPrint').addEventListener('click', () => {
            window.print();
        });
        document.getElementById('btnPdf').addEventListener('click', () => {
            const activeTab = document.querySelector('.tab-pane.active');
            if (activeTab && activeTab.id === 'vendas') {
                exportTableToPDF('tableVendas','Relatorio_Vendas_Adm');
            } else {
                exportTableToPDF('tableProdutos','Relatorio_Produtos_Adm');
            }
        });
        document.getElementById('btnExcel').addEventListener('click', () => {
            const activeTab = document.querySelector('.tab-pane.active');
            if (activeTab && activeTab.id === 'vendas') {
                exportTableToExcel('tableVendas','Relatorio_Vendas_Adm');
            } else {
                exportTableToExcel('tableProdutos','Relatorio_Produtos_Adm');
            }
        });

        fetchRelatorioDataAdm();
        setInterval(fetchRelatorioDataAdm, 60 * 1000);
    });
</script>

</body>
</html>


