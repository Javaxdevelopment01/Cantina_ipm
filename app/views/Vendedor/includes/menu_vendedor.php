<?php
// includes/menu_vendedor.php
// Não chamar session_start() aqui, pois já foi iniciado no dashboard

require_once __DIR__ . '/../../../../config/database.php'; // Ajuste do caminho correto

// Buscar dados do vendedor logado
$stmt = $conn->prepare("SELECT nome, imagem FROM vendedor WHERE id = ?");
$stmt->execute([$_SESSION['vendedor_id']]);
$vendedor = $stmt->fetch(PDO::FETCH_ASSOC);

// Nome e imagem segura
$vendedorNome = htmlspecialchars($vendedor['nome'] ?? 'Vendedor', ENT_QUOTES, 'UTF-8');
$vendedorImagem = !empty($vendedor['imagem']) ? htmlspecialchars($vendedor['imagem'], ENT_QUOTES, 'UTF-8') : 'default.png';

// Captura o nome da página atual
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root {
    --petroleo: #012E40;
    --dourado: #D4AF37;
    --bg: #f8f9fa;
}

body { font-family:"Segoe UI", sans-serif; margin:0; background:var(--bg); }

.sidebar {
    position: fixed; left:0; top:0; bottom:0; width:250px;
    background: var(--petroleo); color:white; padding:1.5rem 1rem;
    overflow-y:auto; transition: all 0.3s ease; z-index:100;
}
.sidebar.collapsed { width:80px; }

.sidebar .logo {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:8px; font-weight:bold; font-size:1.4rem; margin-bottom:2rem; text-align:center;
    color: var(--dourado); letter-spacing:1px; transition: all 0.3s ease;
}
.sidebar.collapsed .logo-text { display:none; }

.sidebar .logo .profile-img {
    width: 60px; height: 60px; border-radius: 50%; object-fit: cover;
}
.sidebar .logo .store-logo {
    width: 120px; height: auto; margin-bottom: 10px; object-fit: contain;
}
.menu-small-logo {
    width: 40px; height: 40px; object-fit: contain; border-radius:6px;
}

.sidebar a {
    display:flex; align-items:center; gap:10px; color:white; text-decoration:none;
    padding:0.7rem 1rem; border-radius:0.4rem; margin-bottom:0.3rem;
    transition: all 0.3s ease; font-size:15px;
}
.sidebar a:hover, .sidebar a.active {
    background: var(--dourado); color: var(--petroleo); font-weight:600; transform:translateX(3px);
}
.sidebar.collapsed a { justify-content:center; padding:0.7rem 0; }
.sidebar.collapsed .link-text { display:none; }

.collapse-btn {
    position:absolute; bottom:20px; left:50%; transform:translateX(-50%);
    background: var(--dourado); border:none; color: var(--petroleo); font-weight:bold;
    padding:8px 14px; border-radius:5px; cursor:pointer; transition:all 0.3s;
    font-size:14px; display:flex; align-items:center; gap:6px;
}
.collapse-btn:hover { background: var(--petroleo); color: var(--dourado); }
</style>

<div class="sidebar" id="sidebar">
   <div class="logo">
        <!-- Volta a mostrar a imagem do vendedor no topo do menu -->
        <img src="/app/<?= $vendedorImagem ?>" alt="Foto Vendedor" class="profile-img">
        <span class="logo-text"><?= $vendedorNome ?></span>
    </div>


    <a href="dashboard_vendedor.php" class="<?= $current_page == 'dashboard_vendedor.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-gauge-high"></i><span class="link-text">Dashboard</span>
    </a>

    <a href="produtos_vendedor.php" class="<?= $current_page == 'produtos_vendedor.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-box"></i><span class="link-text">Produtos</span>
    </a>

    <a href="clientes_vendedor.php" class="<?= $current_page == 'clientes_vendedor.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-users"></i><span class="link-text">Clientes</span>
    </a>

    <a href="vendas_vendedor.php" class="<?= $current_page == 'vendas_vendedor.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-cart-shopping"></i><span class="link-text">Vendas</span>
    </a>

    <a href="pedidos_vendedor.php" class="<?= $current_page == 'pedidos_vendedor.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-receipt"></i><span class="link-text">Pedidos</span>
    </a>

    <a href="alertas_estoque.php" class="<?= $current_page == 'alertas_estoque.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-triangle-exclamation"></i><span class="link-text">Alertas de Estoque</span>
    </a>

    <a href="relatorios_vendedor.php" class="<?= $current_page == 'relatorios_vendedor.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-line"></i><span class="link-text">Relatórios</span>
    </a>

    <a href="definicoes_vendedor.php" class="<?= $current_page == 'definicoes_vendedor.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-gear"></i><span class="link-text">Definições</span>
    </a>
    <?php
    // Pequeno espaço para mostrar a logo carregada nas definições (após o link Definições)
    $baseSettingsPath = __DIR__ . '/../../../../config/settings_vendedor.json';
    $vendedorSettingsPath = __DIR__ . '/../../../../config/settings_vendedor_' . $_SESSION['vendedor_id'] . '.json';
    $settings = [];
    if (file_exists($baseSettingsPath)) {
        $settings = json_decode(file_get_contents($baseSettingsPath), true) ?: [];
    }
    if (file_exists($vendedorSettingsPath)) {
        $vendedorSettings = json_decode(file_get_contents($vendedorSettingsPath), true) ?: [];
        $settings = array_replace_recursive($settings, $vendedorSettings);
    }
    $menuLogo = $settings['store']['logo'] ?? '';
    ?>
    <div style="padding:8px 12px;">
        <?php if (!empty($menuLogo)): ?>
            <img src="<?= htmlspecialchars($menuLogo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="menu-small-logo">
        <?php else: ?>
            <!-- Espaço reservado quando não há logo -->
            <div style="width:40px;height:40px;border-radius:6px;background:rgba(255,255,255,0.06);"></div>
        <?php endif; ?>
    </div>

    <button class="collapse-btn" id="collapseBtn">
        <i class="fa-solid fa-angles-left" id="collapseIcon"></i>
        <span class="link-text"></span>
    </button>
</div>

<script>
const sidebar = document.getElementById('sidebar');
const collapseBtn = document.getElementById('collapseBtn');
const collapseIcon = document.getElementById('collapseIcon');
const main = document.querySelector('.main');

collapseBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    main.classList.toggle('collapsed');

    if(sidebar.classList.contains('collapsed')){
        collapseIcon.classList.replace('fa-angles-left','fa-angles-right');
    } else {
        collapseIcon.classList.replace('fa-angles-right','fa-angles-left');
    }
});
</script>
