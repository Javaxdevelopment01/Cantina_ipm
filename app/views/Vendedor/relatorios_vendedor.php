<?php
session_start();
if(!isset($_SESSION['vendedor_id'])){
	header('Location: login_vendedor.php');
	exit;
}

require_once __DIR__ . '/../../../config/database.php';
include __DIR__ . '/includes/menu_vendedor.php';

// small helper used in templates
function safe($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Fetch vendas, produtos, categorias, vendedores
$stmt = $conn->prepare("SELECT v.id, v.data_venda, v.total, v.valor_pago, v.troco, v.estado, p.id as pedido_id, c.nome as cliente_nome, vd.nome as vendedor_nome FROM venda v JOIN pedido p ON v.id_pedido = p.id LEFT JOIN cliente c ON p.id_cliente = c.id LEFT JOIN vendedor vd ON v.id_vendedor = vd.id ORDER BY v.data_venda DESC");
$stmt->execute();
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $conn->prepare("SELECT p.*, c.nome AS categoria_nome FROM produto p LEFT JOIN categoria c ON p.categoria_id = c.id ORDER BY p.id DESC");
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $conn->prepare("SELECT id, nome FROM categoria ORDER BY nome ASC");
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $conn->prepare("SELECT id, nome FROM vendedor ORDER BY nome ASC");
$stmt->execute();
$vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Relatórios - Cantina IPM</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<style>
		body { font-family: 'Segoe UI', Arial, sans-serif; background:#f6f7fb; color:#222; }
		.main { margin-left: 240px; padding: 30px; }
		.card-pro { background: #fff; border-radius:12px; box-shadow: 0 6px 18px rgba(2,20,30,0.06); padding:20px; }
		.indicators { display:flex; gap:12px; margin-bottom:18px; flex-wrap:wrap; }
		.indicator { background:#fff; padding:14px; border-radius:10px; box-shadow: 0 4px 12px rgba(2,20,30,0.05); flex:1; min-width:180px; }
		.indicator h4 { margin:0; color:#012E40; }
		.indicator .value { font-size:1.4rem; font-weight:700; color:#0b3a4a; }
		.small-muted { font-size:0.9rem; color:#666; }
		@media print { 
			body * { visibility:hidden; } 
			#reportArea, #reportArea * { visibility:visible; } 
			#reportArea { position:absolute; left:0; top:0; width:100%; } 
			.no-print { display:none !important; }
			.indicators-print { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
			.indicators-print .row { display: flex; justify-content: space-between; }
			.indicators-print .col { flex: 1; padding: 10px; }
			.charts-print img { max-width: 100%; height: auto; margin-bottom: 20px; }
			.charts-print h5 { margin-bottom: 10px; color: #333; }
		}
	</style>
</head>
<body>

<div class="main">
	<div class="card-pro">
		<div class="indicators" id="indicatorsRow">
			<div class="indicator"><div class="muted">Total de Vendas</div><h4 class="value" id="ind_total_vendas">Kz 0,00</h4></div>
			<div class="indicator"><div class="muted">Total de Clientes</div><h4 class="value" id="ind_total_clientes">0</h4></div>
			<div class="indicator"><div class="muted">Produtos em Stock</div><h4 class="value" id="ind_produtos_stock">0</h4></div>
			<div class="indicator"><div class="muted">Produtos Baixo Stock (&lt;5)</div><h4 class="value" id="ind_produtos_baixo">0</h4></div>
			<div class="indicator"><div class="muted">Vendas Mês (atual)</div><h4 class="value" id="ind_vendas_mes">Kz 0,00</h4></div>
		</div>

		<div class="row mb-3">
			<div class="col-md-7"><div class="card-pro p-3"><h5>Vendas Mensais (últimos 12 meses)</h5><canvas id="chartVendasMensais" height="160"></canvas></div></div>
			<div class="col-md-5"><div class="card-pro p-3"><h5>Top Produtos</h5><canvas id="chartTopProdutos" height="160"></canvas></div></div>
		</div>

		<div class="row mb-3"><div class="col-12"><div class="card-pro p-3"><h5>Vendas por Vendedor</h5><canvas id="chartVendasVendedor" height="80"></canvas></div></div></div>

		<div class="d-flex justify-content-between align-items-center mb-3">
			<div><h3 class="section-title">Relatórios Profissionais</h3><div class="small-muted">Resumo das vendas e produtos cadastrados. Use os filtros para restringir os dados.</div></div>
			<div class="report-actions no-print"><button class="btn btn-outline-secondary" id="btnPrint"><i class="fa fa-print"></i> Imprimir</button><button class="btn btn-outline-primary" id="btnPdf"><i class="fa fa-file-pdf"></i> Exportar PDF</button><button class="btn btn-outline-success" id="btnExcel"><i class="fa fa-file-excel"></i> Exportar Excel</button></div>
		</div>

		<ul class="nav nav-tabs" id="relTab" role="tablist"><li class="nav-item"><button class="nav-link active" id="vendas-tab" data-bs-toggle="tab" data-bs-target="#vendas" type="button">Vendas</button></li><li class="nav-item"><button class="nav-link" id="produtos-tab" data-bs-toggle="tab" data-bs-target="#produtos" type="button">Produtos</button></li></ul>

		<div class="tab-content mt-3" id="relTabContent">
			<div class="tab-pane fade show active" id="vendas">
				<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
					<div class="filters-row">
						<label class="small-muted">De</label>
						<input type="date" id="venda_from" class="form-control form-control-sm" />
						<label class="small-muted">Até</label>
						<input type="date" id="venda_to" class="form-control form-control-sm" />
						<select id="filter_vendedor" class="form-select form-select-sm"><option value="">Todos vendedores</option><?php foreach($vendedores as $vd): ?><option value="<?= safe($vd['nome']) ?>"><?= safe($vd['nome']) ?></option><?php endforeach; ?></select>
						<select id="filter_estado" class="form-select form-select-sm"><option value="">Todos estados</option><option value="finalizada">finalizada</option><option value="pendente">pendente</option></select>
						<button class="btn btn-sm btn-secondary" id="btnAplicarFiltroVendas">Aplicar</button>
						<button class="btn btn-sm btn-outline-secondary" id="btnLimparFiltroVendas">Limpar</button>
					</div>
				</div>

				<div id="reportArea">
					<div class="table-responsive">
						<table class="table table-hover table-striped" id="tableVendas"><thead class="table-light"><tr><th>#</th><th>Data</th><th>Cliente</th><th>Vendedor</th><th>Total</th><th>Valor Pago</th><th>Troco</th><th>Estado</th></tr></thead>
							<tbody><?php foreach($vendas as $row): ?><tr data-data="<?= safe($row['data_venda']) ?>" data-vendedor="<?= safe($row['vendedor_nome']) ?>" data-estado="<?= safe($row['estado']) ?>"><td><?= safe($row['id']) ?></td><td><?= date('d/m/Y H:i', strtotime($row['data_venda'])) ?></td><td><?= safe($row['cliente_nome']) ?></td><td><?= safe($row['vendedor_nome']) ?></td><td><?= number_format($row['total'],2,',','.') ?></td><td><?= number_format($row['valor_pago'],2,',','.') ?></td><td><?= number_format($row['troco'],2,',','.') ?></td><td><?= safe($row['estado']) ?></td></tr><?php endforeach; ?></tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="tab-pane fade" id="produtos">
				<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
					<div class="filters-row">
						<input type="text" id="produto_search" class="form-control form-control-sm" placeholder="Procurar produto..." />
						<select id="filter_categoria" class="form-select form-select-sm"><option value="">Todas categorias</option><?php foreach($categorias as $c): ?><option value="<?= safe($c['nome']) ?>"><?= safe($c['nome']) ?></option><?php endforeach; ?></select>
						<button class="btn btn-sm btn-secondary" id="btnAplicarFiltroProdutos">Aplicar</button>
						<button class="btn btn-sm btn-outline-secondary" id="btnLimparFiltroProdutos">Limpar</button>
					</div>
				</div>

				<div class="table-responsive">
					<table class="table table-hover table-striped" id="tableProdutos"><thead class="table-light"><tr><th>#</th><th>Nome</th><th>Categoria</th><th>Quantidade</th><th>Preço</th></tr></thead>
						<tbody><?php foreach($produtos as $p): ?><tr data-categoria="<?= safe($p['categoria_nome']) ?>"><td><?= safe($p['id']) ?></td><td><?= safe($p['nome']) ?></td><td><?= safe($p['categoria_nome']) ?></td><td><?= intval($p['quantidade']) ?></td><td><?= number_format($p['preco'],2,',','.') ?></td></tr><?php endforeach; ?></tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>

<script>
	function formatCurrencyStr(val){ return 'Kz ' + Number(val).toLocaleString('pt-PT', {minimumFractionDigits:2}); }

	function applyFiltroVendas(){
		const from = document.getElementById('venda_from').value; const to = document.getElementById('venda_to').value; const vendedor = document.getElementById('filter_vendedor').value; const estado = document.getElementById('filter_estado').value;
		document.querySelectorAll('#tableVendas tbody tr').forEach(tr => {
			const data = tr.dataset.data || ''; const rowVendedor = tr.dataset.vendedor || ''; const rowEstado = tr.dataset.estado || ''; let show = true;
			if(from){ show = show && (new Date(data) >= new Date(from+'T00:00:00')); }
			if(to){ show = show && (new Date(data) <= new Date(to+'T23:59:59')); }
			if(vendedor){ show = show && (rowVendedor === vendedor); }
			if(estado){ show = show && (rowEstado === estado); }
			tr.style.display = show ? '' : 'none';
		});
	}

	function applyFiltroProdutos(){
		const search = document.getElementById('produto_search').value.toLowerCase(); const cat = document.getElementById('filter_categoria').value;
		document.querySelectorAll('#tableProdutos tbody tr').forEach(tr => {
			const nome = tr.cells[1].textContent.toLowerCase(); const categoria = tr.dataset.categoria || ''; let show = true;
			if(search){ show = show && nome.includes(search); }
			if(cat){ show = show && (categoria === cat); }
			tr.style.display = show ? '' : 'none';
		});
	}

	async function exportTableToPDF(tableId, title){ 
		// Captura os gráficos como imagens
		const chartsPromises = [
			chartVendasMensais?.toBase64Image(),
			chartTopProdutos?.toBase64Image(),
			chartVendasVendedor?.toBase64Image()
		];
		const [chartVendas, chartProdutos, chartVendedores] = await Promise.all(chartsPromises);
		
		const { jsPDF } = window.jspdf;
		const doc = new jsPDF('l', 'pt', 'a4');
		const pageWidth = doc.internal.pageSize.getWidth();
		let currentY = 40;

		// Título
		doc.setFontSize(16);
		doc.text(title, 40, currentY);
		currentY += 30;

		// Indicadores
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
		indicators.forEach((ind, i) => {
			doc.text(`${ind[0]} ${ind[1]}`, 40, currentY);
			currentY += 15;
		});
		currentY += 10;

		// Gráficos
		if (chartVendas) {
			const imgWidth = 400;
			const imgHeight = (imgWidth * 160) / 600; // Mantém proporção
			doc.addImage(chartVendas, 'PNG', 40, currentY, imgWidth, imgHeight);
			currentY += imgHeight + 20;
		}

		if (chartProdutos) {
			const imgWidth = 300;
			const imgHeight = (imgWidth * 160) / 400;
			doc.addImage(chartProdutos, 'PNG', 40, currentY, imgWidth, imgHeight);
			currentY += imgHeight + 20;
		}

		if (chartVendedores) {
			const imgWidth = 400;
			const imgHeight = (imgWidth * 80) / 600;
			doc.addImage(chartVendedores, 'PNG', 40, currentY, imgWidth, imgHeight);
			currentY += imgHeight + 20;
		}

		// Tabela
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
		// Criar uma tabela temporária com todos os dados
		const fullReport = document.createElement('table');
		
		// Adicionar indicadores
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

		// Adicionar linha em branco
		fullReport.appendChild(document.createElement('tr'));

		// Adicionar a tabela original
		const originalTable = document.getElementById(tableId);
		Array.from(originalTable.rows).forEach(row => {
			fullReport.appendChild(row.cloneNode(true));
		});

		// Exportar para Excel
		const wb = XLSX.utils.book_new();
		const ws = XLSX.utils.table_to_sheet(fullReport, {raw:true});
		XLSX.utils.book_append_sheet(wb, ws, 'Relatório');
		XLSX.writeFile(wb, filename + '.xlsx');
	}

	document.addEventListener('DOMContentLoaded', function(){
		document.getElementById('btnAplicarFiltroVendas').addEventListener('click', applyFiltroVendas);
		document.getElementById('btnLimparFiltroVendas').addEventListener('click', ()=>{ document.getElementById('venda_from').value=''; document.getElementById('venda_to').value=''; document.getElementById('filter_vendedor').value=''; document.getElementById('filter_estado').value=''; applyFiltroVendas(); });
		document.getElementById('btnAplicarFiltroProdutos').addEventListener('click', applyFiltroProdutos);
		document.getElementById('btnLimparFiltroProdutos').addEventListener('click', ()=>{ document.getElementById('produto_search').value=''; document.getElementById('filter_categoria').value=''; applyFiltroProdutos(); });
		document.getElementById('btnPrint').addEventListener('click', ()=>{
			updatePrintImages(); // Atualiza imagens antes de imprimir
			window.print();
		});
		document.getElementById('btnPdf').addEventListener('click', ()=>{ const activeTab = document.querySelector('.tab-pane.active'); if(activeTab && activeTab.id==='vendas') exportTableToPDF('tableVendas','Relatorio_Vendas'); else exportTableToPDF('tableProdutos','Relatorio_Produtos'); });
		document.getElementById('btnExcel').addEventListener('click', ()=>{ const activeTab = document.querySelector('.tab-pane.active'); if(activeTab && activeTab.id==='vendas') exportTableToExcel('tableVendas','Relatorio_Vendas'); else exportTableToExcel('tableProdutos','Relatorio_Produtos'); });
	});

	// Charts
	let chartVendasMensais = null, chartTopProdutos = null, chartVendasVendedor = null;

	function updatePrintImages() {
		// Atualiza indicadores para impressão
		document.getElementById('print_total_vendas').textContent = document.getElementById('ind_total_vendas').textContent;
		document.getElementById('print_total_clientes').textContent = document.getElementById('ind_total_clientes').textContent;
		document.getElementById('print_produtos_stock').textContent = document.getElementById('ind_produtos_stock').textContent;
		document.getElementById('print_produtos_baixo').textContent = document.getElementById('ind_produtos_baixo').textContent;
		document.getElementById('print_vendas_mes').textContent = document.getElementById('ind_vendas_mes').textContent;

		// Atualiza imagens dos gráficos para impressão
		if (chartVendasMensais) document.getElementById('print_chart_vendas').src = chartVendasMensais.toBase64Image();
		if (chartTopProdutos) document.getElementById('print_chart_produtos').src = chartTopProdutos.toBase64Image();
		if (chartVendasVendedor) document.getElementById('print_chart_vendedor').src = chartVendasVendedor.toBase64Image();
	}

	function fetchRelatorioData(){
		console.log('Buscando dados do relatório...');
		// Usar caminho relativo para a API
		fetch('../../../app/api/relatorio_data.php', { 
			credentials: 'same-origin',
			headers: {
				'Cache-Control': 'no-cache'
			}
		})
		.then(r => {
			console.log('Status da resposta:', r.status, r.statusText);
			if (!r.ok) {
				if (r.status === 401) {
					alert('Sessão expirada! Por favor, faça login novamente.');
					window.location.href = 'login_vendedor.php';
					return;
				}
				alert('Erro: ' + r.status + ' ' + r.statusText + '\nTentando reconectar...');
				throw new Error('HTTP ' + r.status);
			}
			return r.json();
		})
		.then(payload => {
			console.log('Dados recebidos:', payload);
			if(!payload.success) {
				if(payload.error === 'não autorizado') {
					alert('Sessão expirada! Por favor, faça login novamente.');
					window.location.href = 'login_vendedor.php';
					return;
				}
				alert('Erro na API: ' + (payload.error || 'Erro desconhecido'));
				console.error('API relatorio failed', payload);
				return;
			}
				const ind = payload.indicadores || {};
				document.getElementById('ind_total_vendas').textContent = 'Kz ' + Number(ind.total_vendas||0).toLocaleString('pt-PT',{minimumFractionDigits:2});
				document.getElementById('ind_total_clientes').textContent = ind.total_clientes||0;
				document.getElementById('ind_produtos_stock').textContent = ind.produtos_em_stock||0;
				document.getElementById('ind_produtos_baixo').textContent = ind.produtos_baixo_stock||0;
				document.getElementById('ind_vendas_mes').textContent = 'Kz ' + Number(ind.vendas_mes_atual||0).toLocaleString('pt-PT',{minimumFractionDigits:2});

				const labels = (payload.series && payload.series.labels) || [];
				const data = (payload.series && payload.series.data) || [];
				if(!chartVendasMensais){ const ctx = document.getElementById('chartVendasMensais').getContext('2d'); chartVendasMensais = new Chart(ctx,{type:'bar',data:{labels, datasets:[{label:'Total (Kz)', data, backgroundColor:'#0d6efd'}]}, options:{responsive:true, plugins:{legend:{display:false}}}}); } else { chartVendasMensais.data.labels = labels; chartVendasMensais.data.datasets[0].data = data; chartVendasMensais.update(); }

				const top = payload.top_produtos || []; const topLabels = top.map(t=>t.nome); const topData = top.map(t=>Number(t.total_vendido||0));
				if(!chartTopProdutos){ const ctx2 = document.getElementById('chartTopProdutos').getContext('2d'); chartTopProdutos = new Chart(ctx2,{type:'doughnut', data:{labels:topLabels, datasets:[{data:topData, backgroundColor:['#f59e0b','#f97316','#ef4444','#0ea5e9','#34d399','#60a5fa','#7c3aed','#a3e635']}]}, options:{responsive:true, plugins:{legend:{position:'right'}}}}); } else { chartTopProdutos.data.labels = topLabels; chartTopProdutos.data.datasets[0].data = topData; chartTopProdutos.update(); }

				const vv = payload.vendas_por_vendedor || []; const vvLabels = vv.map(x=>x.nome); const vvData = vv.map(x=>Number(x.total_vendas||0));
				if(!chartVendasVendedor){ const ctx3 = document.getElementById('chartVendasVendedor').getContext('2d'); chartVendasVendedor = new Chart(ctx3,{type:'bar', data:{labels:vvLabels, datasets:[{label:'Total (Kz)', data:vvData, backgroundColor:'#0ea5e9'}]}, options:{indexAxis:'y', responsive:true, plugins:{legend:{display:false}}}}); } else { chartVendasVendedor.data.labels = vvLabels; chartVendasVendedor.data.datasets[0].data = vvData; chartVendasVendedor.update(); }

				// Atualiza imagens para impressão e PDF
				updatePrintImages();
			})
			.catch(err=>console.error('Erro ao carregar relatorio:', err));
	}

	fetchRelatorioData(); setInterval(fetchRelatorioData, 60*1000);
</script>
</body>
</html>
 
