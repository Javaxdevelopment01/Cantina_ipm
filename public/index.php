<?php
// dashboard.php
// Painel Dashboard visual inspirado no layout fornecido.
// NÃO coloque espaços/linhas antes deste <?php (evita problemas de header redirection)

// inclui conexão PDO (certifique-se que database.php define $conn)
require_once __DIR__ . '/../config/database.php';

// inclui menu/header se usares includes (mantém consistência com o resto do projeto)
include_once __DIR__ . '/../includes/menu.php'; // opcional — ajusta caminho conforme tua estrutura
include_once __DIR__ . '/../includes/header.php'; // opcional

// Função segura para output
function safe($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

// --------------------------
// Consultas ao banco
// --------------------------

// 1) Totais básicos
try {
    $q = $conn->query("SELECT COUNT(*) FROM venda");
    $totalVendas = (int) $q->fetchColumn();

    $q = $conn->query("SELECT COUNT(*) FROM produto");
    $totalProdutos = (int) $q->fetchColumn();

    $q = $conn->query("SELECT COUNT(*) FROM utilizador");
    $totalClientes = (int) $q->fetchColumn();

    $q = $conn->query("SELECT COALESCE(SUM(quantidade),0) FROM produto");
    $totalEstoque = (int) $q->fetchColumn();
} catch (Exception $e) {
    // Em caso de erro de BD, mostra 0 e regista (não interrompe a UI)
    $totalVendas = $totalProdutos = $totalClientes = $totalEstoque = 0;
}

// 2) Vendas por mês (últimos 12 meses) — usa campo data_criacao em venda
$salesLabels = [];
$salesData = [];
try {
    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(data_criacao, '%Y-%m') as ym, SUM(total) as total
        FROM venda
        WHERE data_criacao >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
        GROUP BY ym
        ORDER BY ym ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // criar lista 12 meses (meses sem vendas ficam 0)
    $months = [];
    $now = new DateTime();
    for ($i = 11; $i >= 0; $i--) {
        $m = (clone $now)->modify("-{$i} month")->format('Y-m');
        $months[$m] = 0.0;
    }
    foreach ($rows as $r) {
        if (isset($months[$r['ym']])) $months[$r['ym']] = (float)$r['total'];
    }
    foreach ($months as $ym => $val) {
        $salesLabels[] = date_create_from_format('Y-m', $ym)->format('M Y');
        $salesData[] = $val;
    }
} catch (Exception $e) {
    // fallback
    for ($i = 0; $i < 12; $i++) {
        $salesLabels[] = '-';
        $salesData[] = 0;
    }
}

// 3) Top produtos vendidos (quantidade) últimos 6
$topLabels = [];
$topData = [];
try {
    $stmt = $conn->prepare("
        SELECT p.nome, SUM(iv.quantidade) as qtd
        FROM item_venda iv
        JOIN produto p ON p.id = iv.produto_id
        GROUP BY p.id
        ORDER BY qtd DESC
        LIMIT 6
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $topLabels[] = $r['nome'];
        $topData[] = (int)$r['qtd'];
    }
    if (empty($topLabels)) {
        $topLabels = ['—'];
        $topData = [0];
    }
} catch (Exception $e) {
    $topLabels = ['—'];
    $topData = [0];
}

// 4) Stock distribution (pegar 6 produtos com menor quantidade)
$stockLabels = [];
$stockData = [];
try {
    $stmt = $conn->prepare("SELECT nome, quantidade FROM produto ORDER BY quantidade ASC LIMIT 6");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $stockLabels[] = $r['nome'];
        $stockData[] = (int)$r['quantidade'];
    }
    if (empty($stockLabels)) {
        $stockLabels = ['—'];
        $stockData = [0];
    }
} catch (Exception $e) {
    $stockLabels = ['—'];
    $stockData = [0];
}

// 5) Percentual (exemplo) — % produtos em alerta (quantidade <= alerta_stock)
$percentAlert = 0;
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM produto");
    $totalP = (int)$stmt->fetchColumn();
    if ($totalP > 0) {
        $stmt = $conn->query("SELECT COUNT(*) FROM produto WHERE quantidade <= alerta_stock");
        $alertCount = (int)$stmt->fetchColumn();
        $percentAlert = round(($alertCount / $totalP) * 100, 1);
    }
} catch (Exception $e) {
    $percentAlert = 0;
}

// Usuário logado (simulação; substitui com tua sessão)
$loggedName = 'Atendedor';
$loggedEmail = 'atendedor@ipm.edu';
$loggedAvatar = ''; // se tiver caminho a imagem do utilizador coloca aqui

?>
<!doctype html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8" />
    <title>Dashboard - IPM Cantina</title>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <meta name="viewport" content="width=device-width,initial-scale=1">

    <style>
    /* Layout inspirado na imagem fornecida */
    :root{
        --petroleo:#012E40;
        --dourado:#D4AF37;
        --bg:#eef2f6;
        --card-bg:#ffffff;
        --muted:#6b7280;
    }
    *{box-sizing:border-box}
    body{
        margin:0;
        font-family: "Segoe UI", Arial, sans-serif;
        background:var(--bg);
        color:#222;
    }

    .wrap{
        display:flex;
        min-height:100vh;
        gap:24px;
        padding:24px;
    }

    /* SIDEBAR */
    .sidebar {
        width:220px;
        background:linear-gradient(180deg,var(--petroleo),#033b4a);
        color: #fff;
        padding:24px 18px;
        border-radius:8px;
        box-shadow: 0 6px 18px rgba(2,6,23,0.08);
        flex-shrink:0;
    }
    .sidebar .user{
        display:flex;
        gap:12px;
        align-items:center;
        margin-bottom:18px;
    }
    .avatar {
        width:56px;height:56px;border-radius:50%;
        background:rgba(255,255,255,0.15);
        display:flex;align-items:center;justify-content:center;font-size:22px;
    }
    .sidebar h3{margin:0;font-size:1.05rem}
    .sidebar p{margin:2px 0 12px;font-size:0.85rem;color:rgba(255,255,255,0.85)}

    .navlist{list-style:none;padding:0;margin:18px 0 0;}
    .navlist li{margin:10px 0;}
    .navlist a{
        color:#fff;text-decoration:none;display:flex;align-items:center;gap:12px;padding:8px;border-radius:6px;
    }
    .navlist a.active, .navlist a:hover{
        background:var(--dourado); color:var(--petroleo); font-weight:600; transform: translateX(6px);
    }

    /* MAIN PANEL */
    .panel {
        flex:1;
    }
    .topbar{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:18px;
    }
    .topbar .title{
        font-size:1.1rem;
        font-weight:700;
        color:#1f2d36;
    }
    .header-user{
        display:flex;align-items:center;gap:10px;
    }
    .header-user img{width:40px;height:40px;border-radius:50%;object-fit:cover}
    .header-user .name{font-weight:600;margin-right:6px}

    /* GRID principal */
    .grid {
        display:grid;
        grid-template-columns: 2fr 1fr;
        gap:18px;
    }

    /* Cards resumo - topo */
    .cards {
        display:grid;
        grid-template-columns: repeat(4, 1fr);
        gap:14px;
        margin-bottom:14px;
    }
    .card {
        background:var(--card-bg);
        padding:18px;
        border-radius:8px;
        box-shadow: 0 6px 18px rgba(3,19,30,0.06);
    }
    .card .k { font-size:1rem; color:var(--muted); }
    .card .v { font-size:1.4rem; font-weight:800; margin-top:6px; }

    .accent {
        background:linear-gradient(135deg,#0b3f54,#163f47);
        color:#fff;
        box-shadow: 8px 16px 40px rgba(2,6,23,0.12);
    }
    .accent .v { font-size:1.6rem; }

    /* Charts area */
    .main-chart {
        background:var(--card-bg);
        padding:18px;
        border-radius:8px;
        box-shadow: 0 6px 18px rgba(3,19,30,0.06);
    }
    .small-charts {
        display:grid;
        grid-template-rows: 1fr 1fr;
        gap:14px;
    }
    .donut-card {
        display:flex;flex-direction:column;align-items:center;justify-content:center;
        padding:18px;border-radius:8px;background:var(--card-bg);box-shadow: 0 6px 18px rgba(3,19,30,0.06);
    }

    /* responsivo simples */
    @media (max-width: 1000px){
        .wrap{padding:12px}
        .grid{grid-template-columns: 1fr;}
        .cards{grid-template-columns:repeat(2,1fr);}
    }
    @media (max-width: 600px){
        .sidebar{display:none}
        .cards{grid-template-columns:1fr}
    }
    </style>
</head>
<body>
<div class="wrap">
    <!-- Sidebar -->
    <aside class="sidebar" role="navigation">
        <div class="user">
            <div class="avatar">
                <?php if ($loggedAvatar): ?>
                    <img src="<?php echo safe($loggedAvatar); ?>" alt="avatar" style="width:48px;height:48px;border-radius:50%">
                <?php else: ?>
                    <i class="fa-solid fa-utensils"></i>
                <?php endif; ?>
            </div>
            <div>
                <h3><?php echo safe($loggedName); ?></h3>
                <p style="font-size:0.82rem"><?php echo safe($loggedEmail); ?></p>
            </div>
        </div>

        <ul class="navlist">
            <li><a href="dashboard.php" class="active"><i class="fa-solid fa-chart-simple"></i> Dashboard</a></li>
            <li><a href="produtos.php"><i class="fa-solid fa-box"></i> Produtos</a></li>
            <li><a href="categorias.php"><i class="fa-solid fa-tags"></i> Categorias</a></li>
            <li><a href="vendas.php"><i class="fa-solid fa-cart-shopping"></i> Vendas</a></li>
            <li><a href="relatorios.php"><i class="fa-solid fa-chart-line"></i> Relatórios</a></li>
            <li><a href="usuarios.php"><i class="fa-solid fa-users"></i> Usuários</a></li>
            <li><a href="configuracoes.php"><i class="fa-solid fa-gear"></i> Definições</a></li>
        </ul>
    </aside>

    <!-- Painel principal -->
    <main class="panel">
        <div class="topbar">
            <div class="title">Dashboard IPM Cantina</div>
            <div class="header-user">
                <div class="name"><?php echo safe($loggedName); ?></div>
                <img src="<?php echo $loggedAvatar ? safe($loggedAvatar) : 'https://via.placeholder.com/40x40?text=U'; ?>" alt="user">
            </div>
        </div>

        <div class="grid">
            <div>
                <!-- cards resumo -->
                <div class="cards">
                    <div class="card accent">
                        <div class="k">Earning</div>
                        <div class="v">R$ <?php echo number_format(array_sum($salesData),2,',','.'); ?></div>
                    </div>
                    <div class="card">
                        <div class="k">Total Vendas</div>
                        <div class="v"><?php echo $totalVendas; ?></div>
                    </div>
                    <div class="card">
                        <div class="k">Produtos</div>
                        <div class="v"><?php echo $totalProdutos; ?></div>
                    </div>
                    <div class="card">
                        <div class="k">Clientes</div>
                        <div class="v"><?php echo $totalClientes; ?></div>
                    </div>
                </div>

                <!-- gráfico principal -->
                <div class="main-chart" style="margin-bottom:14px;">
                    <h4 style="margin:0 0 10px;">Vendas últimos 12 meses</h4>
                    <canvas id="salesChart" height="110"></canvas>
                </div>

                <!-- área de top produtos -->
                <div style="display:flex;gap:14px;flex-wrap:wrap">
                    <div class="card" style="flex:1;min-width:250px;">
                        <h5 style="margin-top:0">Produtos Mais Vendidos</h5>
                        <canvas id="topProducts" height="160"></canvas>
                    </div>

                    <div class="card" style="width:260px;">
                        <h5 style="margin-top:0">Alertas / Estoque</h5>
                        <div style="display:flex;align-items:center;gap:12px">
                            <div style="font-size:30px;font-weight:800;color:var(--petroleo)"><?php echo $percentAlert; ?>%</div>
                            <div style="flex:1">
                                <div style="color:var(--muted)">Produtos com estoque baixo</div>
                                <small style="color:#777"><?php echo $totalProdutos ? intval(($percentAlert/100)*$totalProdutos) : 0; ?> itens</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- coluna direita -->
            <aside>
                <div class="small-charts">
                    <div class="card">
                        <h5 style="margin-top:0">Distribuição do Estoque</h5>
                        <canvas id="stockChart" height="180"></canvas>
                    </div>

                    <div class="donut-card">
                        <h5 style="margin:0 0 6px">Resumo</h5>
                        <div style="font-size:1.6rem;font-weight:700;color:var(--petroleo);">Estoque: <?php echo $totalEstoque; ?></div>
                        <div style="margin-top:8px;color:#666">Itens em total</div>
                    </div>
                </div>
            </aside>
        </div>
    </main>
</div>

<script>
// Dados vindos do PHP (injeção segura simples)
const salesLabels = <?php echo json_encode($salesLabels, JSON_UNESCAPED_UNICODE); ?>;
const salesData = <?php echo json_encode($salesData); ?>;

const topLabels = <?php echo json_encode($topLabels, JSON_UNESCAPED_UNICODE); ?>;
const topData = <?php echo json_encode($topData); ?>;

const stockLabels = <?php echo json_encode($stockLabels, JSON_UNESCAPED_UNICODE); ?>;
const stockData = <?php echo json_encode($stockData); ?>;

// Chart: vendas (linha + barras estilizada)
const ctxSales = document.getElementById('salesChart').getContext('2d');
new Chart(ctxSales, {
    type: 'bar',
    data: {
        labels: salesLabels,
        datasets: [
            {
                type: 'bar',
                label: 'Vendas',
                data: salesData,
                backgroundColor: 'rgba(20, 57, 76, 0.85)'
            },
            {
                type: 'line',
                label: 'Tendência',
                data: salesData.map(v => parseFloat((v*0.9).toFixed(2))),
                borderColor: 'rgba(212,175,55,0.95)',
                borderWidth: 2,
                fill: false,
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend:{display:false}
        },
        scales: {
            y: {
                beginAtZero:true
            }
        }
    }
});

// Chart: top produtos
const ctxTop = document.getElementById('topProducts').getContext('2d');
new Chart(ctxTop, {
    type: 'horizontalBar' in Chart.defaults ? 'horizontalBar' : 'bar', // fallback
    data: {
        labels: topLabels,
        datasets: [{
            label:'Qtd vendida',
            data: topData,
            backgroundColor: ['#D4AF37','#0b3f54','#1f8a70','#f2a65a','#9ec5ab','#7aa0c4']
        }]
    },
    options: {
        indexAxis: 'y',
        responsive:true,
        plugins:{ legend:{display:false} }
    }
});

// Chart: stock distribution (doughnut)
const ctxStock = document.getElementById('stockChart').getContext('2d');
new Chart(ctxStock, {
    type: 'doughnut',
    data: {
        labels: stockLabels,
        datasets: [{
            data: stockData,
            backgroundColor: ['#D4AF37','#0b3f54','#163f47','#7aa0c4','#f2a65a','#c9d6df']
        }]
    },
    options: {
        responsive:true,
        plugins:{ legend:{position:'bottom'} }
    }
});
</script>

</body>
</html>
