<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_adm.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

function safe($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// KPIs básicos para o administrador
$totalProdutos = 0;
$totalVendas = 0;
$totalClientes = 0;
$totalVendedores = 0;
$pedidosPendentes = 0;
$faturacaoHoje = 0.0;
$produtosDados = [];
$faturacaoDados = [];
$vendedoresDados = [];

try {
    // Tenta obter dados do banco
    $stmt = $conn->query("SELECT COUNT(*) as total FROM produto");
    if ($stmt) $totalProdutos = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM venda");
    if ($stmt) $totalVendas = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM cliente");
    if ($stmt) $totalClientes = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM vendedor");
    if ($stmt) $totalVendedores = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM pedido WHERE estado = 'pendente'");
    if ($stmt) $pedidosPendentes = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->query("SELECT COALESCE(SUM(total),0) as total FROM venda WHERE DATE(data_venda) = CURDATE()");
    if ($stmt) $faturacaoHoje = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Dados para gráfico de produtos mais vendidos (igual ao dashboard vendedor)
    $stmt = $conn->prepare("
        SELECT p.nome, SUM(pi.quantidade) AS total_vendido
        FROM pedido_itens pi
        JOIN produto p ON pi.id_produto = p.id
        JOIN pedido pd ON pi.id_pedido = pd.id
        WHERE pd.estado = 'atendido'
        GROUP BY pi.id_produto
        ORDER BY total_vendido DESC
        LIMIT 5
    ");
    $stmt->execute();
    $produtosDados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Dados para gráfico de faturação diária (últimos 7 dias)
    $stmt = $conn->query("
        SELECT DATE(data_venda) as data, SUM(total) as total 
        FROM venda 
        WHERE data_venda >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(data_venda) 
        ORDER BY data
    ");
    if ($stmt) $faturacaoDados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Dados para gráfico de desempenho por vendedor (quantidade de vendas e soma total)
        $stmt = $conn->prepare(
            "SELECT vd.id, COALESCE(vd.nome, 'Sem nome') AS nome, COUNT(v.id) AS total_vendas, COALESCE(SUM(v.total),0) AS total_valor
             FROM venda v
             LEFT JOIN vendedor vd ON v.id_vendedor = vd.id
             GROUP BY vd.id, vd.nome
             ORDER BY total_vendas DESC
             LIMIT 10"
        );
        $stmt->execute();
        $vendedoresDados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Se alguma tabela não existir ainda, define valores padrão
    error_log("Erro ao carregar dados do dashboard: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador - Cantina IPM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f3f4f6;
            color: #0f172a;
        }

        .topbar-adm {
            margin-left: 250px;
            padding: 14px 26px;
            background: #ffffff;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            gap: 1rem;
        }

        .topbar-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: #0f172a;
        }

        .topbar-sub {
            font-size: 0.83rem;
            color: #6b7280;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.78rem;
            background: rgba(212, 175, 55, 0.1);
            color: #92400e;
        }

        .kpi-card {
            border-radius: 16px;
            background: #ffffff;
            padding: 1.2rem 1.3rem;
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(148, 163, 184, 0.18);
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            height: 100%;
        }

        .kpi-label {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .kpi-value {
            font-size: 1.65rem;
            font-weight: 700;
            color: #0f172a;
        }

        .kpi-icon {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(212, 175, 55, 0.1);
            color: #d97706;
            font-size: 1rem;
        }

        .alert-kpi {
            border-radius: 14px;
            background: #fef3c7;
            color: #92400e;
            padding: 0.9rem 1rem;
            border: 1px solid #fde68a;
            font-size: 0.9rem;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
        }

        .main-adm {
            margin-left: 250px;
            padding: 26px;
        }

        .main-adm.collapsed,
        .topbar-adm.collapsed {
            margin-left: 80px;
        }

        .btn-toggle-sidebar {
            display: none;
            border: none;
            background: transparent;
            color: #0f172a;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-outline-primary {
            border-color: #3b82f6;
            color: #3b82f6;
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: #eff6ff;
        }

        .btn-outline-secondary {
            border-color: #6b7280;
            color: #6b7280;
            background: transparent;
        }

        .btn-outline-secondary:hover {
            background: #f3f4f6;
        }

        .d-grid {
            display: grid;
        }

        .d-flex {
            display: flex;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .justify-content-center {
            justify-content: center;
        }

        .align-items-center {
            align-items: center;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .gap-3 {
            gap: 0.75rem;
        }

        /* Layout específico para charts lado a lado (compactos) */
        .charts-row {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .charts-row .chart-card {
            flex: 1 1 calc(50% - 8px);
            min-width: 260px;
        }

        .mb-1 {
            margin-bottom: 0.25rem;
        }

        .mb-2 {
            margin-bottom: 0.5rem;
        }

        .mb-3 {
            margin-bottom: 0.75rem;
        }

        .me-1 {
            margin-right: 0.25rem;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        .row {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
        }

        .col-12 {
            grid-column: 1 / -1;
        }

        .col-sm-6 {
            grid-column: span 1;
        }

        .col-lg-3 {
            grid-column: span 1;
        }

        .col-lg-6 {
            grid-column: span 1 / -1;
        }

        .col-xl-7 {
            grid-column: span 1 / -1;
        }

        .col-xl-5 {
            grid-column: span 1 / -1;
        }

        .text-muted {
            color: #6b7280;
        }

        .text-success {
            color: #10b981;
        }

        .small {
            font-size: 0.875rem;
        }

        .text-dark {
            color: #1f2937;
        }

        .text-bg-warning {
            background-color: #fbbf24;
            color: #1f2937;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .text-bg-success {
            background-color: #d1fae5;
            color: #065f46;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .badge {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .chart-container {
            position: relative;
            height: 180px;
            margin: 0.6rem 0;
        }

        code {
            background: #f3f4f6;
            padding: 0.1rem 0.4rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }

        ul {
            margin-left: 1.5rem;
            line-height: 1.6;
        }

        li {
            margin-bottom: 0.4rem;
        }

        @media (max-width: 1024px) {
            .col-lg-3 {
                grid-column: span 2;
            }

            .col-lg-6 {
                grid-column: span 1 / -1;
            }

            .col-xl-7,
            .col-xl-5 {
                grid-column: span 1 / -1;
            }
        }

        @media (max-width: 767.98px) {
            .topbar-adm,
            .main-adm,
            .topbar-adm.collapsed,
            .main-adm.collapsed {
                margin-left: 0;
                padding: 16px;
            }

            .btn-toggle-sidebar {
                display: inline-flex;
            }

            .topbar-adm {
                padding-block: 10px;
            }

            .col-sm-6 {
                grid-column: span 1;
            }

            .col-lg-3 {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/menu_adm.php'; ?>

<div class="topbar-adm">
    <div class="d-flex align-items-center gap-2 topbar-adm-title-group">
        <button class="btn-toggle-sidebar" id="btnToggleSidebar" aria-label="Menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="topbar-title">Painel do Administrador</div>
        <div class="topbar-sub">Resumo geral da operação da Cantina IPM</div>
    </div>
    <div>
        <span class="chip">
            <i class="fa-solid fa-circle text-success" style="font-size:0.55rem;"></i>
            Sessão ativa como <strong><?= safe($_SESSION['admin_nome'] ?? 'Administrador') ?></strong>
        </span>
    </div>
</div>

<div class="main-adm">
    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="kpi-label">Produtos registados</span>
                    <span class="kpi-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                </div>
                <span class="kpi-value"><?= $totalProdutos ?></span>
                <span class="text-muted small">Gestão via módulo de produtos</span>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="kpi-label">Clientes registados</span>
                    <span class="kpi-icon"><i class="fa-solid fa-users"></i></span>
                </div>
                <span class="kpi-value"><?= $totalClientes ?></span>
                <span class="text-muted small">Alunos / utilizadores do sistema</span>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="kpi-label">Vendedores</span>
                    <span class="kpi-icon"><i class="fa-solid fa-user-tie"></i></span>
                </div>
                <span class="kpi-value"><?= $totalVendedores ?></span>
                <span class="text-muted small">Contas ativas de vendedor</span>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="kpi-card">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="kpi-label">Vendas registadas</span>
                    <span class="kpi-icon"><i class="fa-solid fa-receipt"></i></span>
                </div>
                <span class="kpi-value"><?= $totalVendas ?></span>
                <span class="text-muted small">Total acumulado na base de dados</span>
            </div>
        </div>
    </div>

    <!-- Gráficos: movidos para logo abaixo dos KPIs principais -->
    <div class="charts-row mb-3">
        <div class="chart-card kpi-card">
            <p class="section-title mb-2">Faturação Diária (7 dias)</p>
            <div class="chart-container" style="height:180px;">
                <canvas id="chartFaturacao"></canvas>
            </div>
        </div>
        <div class="chart-card kpi-card">
            <p class="section-title mb-2">Produtos Mais Vendidos</p>
            <div class="chart-container" style="height:180px;">
                <canvas id="chartProdutos"></canvas>
            </div>
        </div>
    </div>

    <!-- Duas linhas com pares lado a lado: (Vendedores | Pedidos) e (Faturação de hoje | Atalhos) -->
    <div class="charts-row mb-3">
        <div class="chart-card kpi-card">
            <p class="section-title mb-2">Desempenho por Vendedor (top 10)</p>
            <div class="chart-container" style="height:180px;">
                <canvas id="chartVendedores"></canvas>
            </div>
            <p class="text-muted small mb-0">Este gráfico mostra quantas vendas cada vendedor realizou (ordem decrescente).</p>
        </div>

        <div class="chart-card kpi-card">
            <p class="section-title mb-2">Pedidos pendentes</p>
            <div class="chart-container" style="height:180px; display:flex; align-items:center; justify-content:center;">
                <div style="text-align:center;">
                    <div style="font-size:2.1rem; font-weight:700; color:#92400e;"><?= $pedidosPendentes ?></div>
                    <div class="text-muted small">Pedidos pendentes na fila</div>
                </div>
            </div>
            <p class="text-muted small mb-0">Acompanhe a carga de trabalho dos vendedores.</p>
        </div>
    </div>

    <div class="charts-row mb-3">
        <div class="chart-card kpi-card">
            <p class="section-title mb-2">Faturação de hoje</p>
            <div class="chart-container" style="height:180px; display:flex; align-items:center; justify-content:center;">
                <div style="text-align:center;">
                    <div style="font-size:2.1rem; font-weight:700; color:#065f46;">Kz <?= number_format($faturacaoHoje, 2, ',', '.') ?></div>
                    <div class="text-muted small">Valor total do dia</div>
                </div>
            </div>
            <p class="text-muted small mb-0">Valor registado na tabela de vendas para o dia corrente.</p>
        </div>

        <div class="chart-card kpi-card">
            <p class="section-title mb-2">Atalhos rápidos</p>
            <div class="chart-container" style="height:180px; display:flex; align-items:center; justify-content:center;">
                <div style="width:100%;">
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(110px, 1fr)); gap:8px;">
                        <a href="gestao_produtos.php" class="btn btn-outline-primary">Produtos</a>
                        <a href="gestao_vendedores.php" class="btn btn-outline-primary">Vendedores</a>
                        <a href="gestao_pedidos.php" class="btn btn-outline-primary">Pedidos</a>
                        <a href="gestao_clientes.php" class="btn btn-outline-primary">Clientes</a>
                        <a href="gestao_resets.php" class="btn btn-outline-warning">Senhas</a>
                        <a href="historico_logins.php" class="btn btn-outline-info">Histórico</a>
                        <a href="relatorios_adm.php" class="btn btn-outline-secondary">Relatórios</a>
                    </div>
                </div>
            </div>
            <p class="text-muted small mb-0">Acesso rápido aos módulos administrativos.</p>
        </div>
    </div>
</div>

<script>
    // Sincroniza estado colapsado da sidebar com a topbar/main (classe .collapsed)
    (function () {
        const sidebar = document.getElementById('sidebarAdm');
        const main = document.querySelector('.main-adm');
        const topbar = document.querySelector('.topbar-adm');
        const btnToggle = document.getElementById('btnToggleSidebar');

        const observer = new MutationObserver(() => {
            const collapsed = sidebar.classList.contains('collapsed');
            if (collapsed) {
                main.classList.add('collapsed');
                topbar.classList.add('collapsed');
            } else {
                main.classList.remove('collapsed');
                topbar.classList.remove('collapsed');
            }
        });

        observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });

        // Em mobile, usar o botão hamburguer para mostrar/esconder a sidebar
        if (btnToggle) {
            btnToggle.addEventListener('click', () => {
                // Em ecrãs pequenos a sidebar usa a classe "show" para entrar/sair
                if (window.matchMedia('(max-width: 767.98px)').matches) {
                    sidebar.classList.toggle('show');
                } else {
                    sidebar.classList.toggle('collapsed');
                }
            });
        }

            // Gráfico de Desempenho por Vendedor
            const ctxVendedores = document.getElementById('chartVendedores');
            if (ctxVendedores) {
                const labelsVendedores = <?php echo json_encode(array_map(function($v){ return $v['nome']; }, $vendedoresDados)); ?>;
                const dataVendas = <?php echo json_encode(array_map(function($v){ return (int)$v['total_vendas']; }, $vendedoresDados)); ?>;
                const dataValores = <?php echo json_encode(array_map(function($v){ return (float)$v['total_valor']; }, $vendedoresDados)); ?>;

                // gera cores distintas por vendedor (HSL distribuído)
                const colorPalette = (labels) => {
                    if (!labels || !labels.length) return ['rgba(99,102,241,0.85)'];
                    return labels.map((_, i) => {
                        const hue = Math.round((i * (360 / labels.length)) % 360);
                        return `hsl(${hue} 70% 50%)`;
                    });
                };

                const bgColors = colorPalette(labelsVendedores);
                const borderColors = bgColors.map(c => c.replace(' 50%)', ' 40%)'));

                new Chart(ctxVendedores.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: (labelsVendedores && labelsVendedores.length) ? labelsVendedores : ['Sem dados'],
                        datasets: [{
                            label: 'Número de Vendas',
                            data: (dataVendas && dataVendas.length) ? dataVendas : [0],
                            backgroundColor: bgColors,
                            borderColor: borderColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true, position: 'top' },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let v = context.parsed.y;
                                        return 'Vendas: ' + v;
                                    },
                                    afterLabel: function(context) {
                                        const idx = context.dataIndex;
                                        const val = (dataValores && dataValores[idx]) ? dataValores[idx] : 0;
                                        return 'Total: Kz ' + Number(val).toLocaleString('pt-PT');
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } }
                        }
                    }
                });
            }
    })();

    // Gráfico de Faturação Diária
    const ctxFaturacao = document.getElementById('chartFaturacao');
    if (ctxFaturacao) {
        new Chart(ctxFaturacao.getContext('2d'), {
            type: 'line',
            data: {
                labels: [<?php echo !empty($faturacaoDados) ? implode(',', array_map(function($d) { return "'" . date('d/m', strtotime($d['data'])) . "'"; }, $faturacaoDados)) : "'Sem dados'" ?>],
                datasets: [{
                    label: 'Faturação (Kz)',
                    data: [<?php echo !empty($faturacaoDados) ? implode(',', array_map(function($d) { return $d['total'] ?? 0; }, $faturacaoDados)) : "0" ?>],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#10b981',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Kz ' + value.toLocaleString('pt-PT');
                            }
                        }
                    }
                }
            }
        });
    }

    // Gráfico de Produtos Mais Vendidos
    const ctxProdutos = document.getElementById('chartProdutos');
    if (ctxProdutos) {
        new Chart(ctxProdutos.getContext('2d'), {
            type: 'bar',
            data: {
                labels: [<?php echo !empty($produtosDados) ? implode(',', array_map(function($p) { return "'" . addslashes($p['nome']) . "'"; }, $produtosDados)) : "'Sem dados'" ?>],
                datasets: [{
                    label: 'Quantidade Vendida',
                    data: [<?php echo !empty($produtosDados) ? implode(',', array_map(function($p) { return $p['total_vendido'] ?? 0; }, $produtosDados)) : "0" ?>],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(168, 85, 247, 0.8)'
                    ],
                    borderColor: [
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(251, 146, 60)',
                        'rgb(239, 68, 68)',
                        'rgb(168, 85, 247)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
</script>
<!-- JavaScript Responsivo Global -->
<script src="../../assets/js/responsive.js"></script>

</body>
</html>


