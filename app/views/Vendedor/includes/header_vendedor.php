<?php
date_default_timezone_set('Africa/Luanda');
$horaData = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Vendedor - Cantina IPM</title>
    <!-- Local styles only: removed external CDN dependencies -->
<?php
// Carrega configurações padrão e específicas do vendedor
$baseSettingsPath = __DIR__ . '/../../../config/settings_vendedor.json';
$vendedorSettingsPath = __DIR__ . '/../../../config/settings_vendedor_' . $_SESSION['vendedor_id'] . '.json';

// Carrega configurações padrão
$s = [];
if (file_exists($baseSettingsPath)) {
    $s = json_decode(file_get_contents($baseSettingsPath), true) ?: [];
}

// Sobrescreve com configurações específicas do vendedor se existirem
if (file_exists($vendedorSettingsPath)) {
    $vendedorSettings = json_decode(file_get_contents($vendedorSettingsPath), true) ?: [];
    $s = array_replace_recursive($s, $vendedorSettings);
    $primary = $s['theme']['primary_color'] ?? '#012E40';
    $secondary = $s['theme']['secondary_color'] ?? '#D4AF37';
    $mode = $s['theme']['mode'] ?? 'light';
    
    // Injetar variáveis CSS do tema
    echo "<style>
    :root {
        --petroleo: {$primary};
        --dourado: {$secondary};
        --text-main: " . ($mode === 'dark' ? '#e6eef6' : '#212529') . ";
        --bg-main: " . ($mode === 'dark' ? '#0b1220' : '#f8f9fa') . ";
        --bg-panel: " . ($mode === 'dark' ? '#1a1f2e' : '#ffffff') . ";
        --border-color: " . ($mode === 'dark' ? '#2a3041' : '#dee2e6') . ";
    }

    body { 
        background: var(--bg-main) !important; 
        color: var(--text-main) !important; 
    }

    .bg-light { 
        background: var(--bg-panel) !important;
    }

    .panel {
        background: var(--bg-panel) !important;
        border: 1px solid var(--border-color);
    }

    input, select, textarea {
        background: var(--bg-panel) !important;
        color: var(--text-main) !important;
        border-color: var(--border-color) !important;
    }

    .nav-link {
        color: var(--text-main) !important;
    }

    .sidebar {
        background: var(--petroleo) !important;
    }

    .sidebar a:hover, .sidebar a.active {
        background: var(--dourado) !important;
        color: var(--petroleo) !important;
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
    </style>";
}
?>
</head>
<body>

<?php
// Determine store logo (from settings) and ensure it has a leading slash
$storeLogo = '';
if (!empty($s['store']['logo'])) {
    $rawLogo = $s['store']['logo'];
    if (strpos($rawLogo, '/') !== 0) {
        $rawLogo = '/' . ltrim($rawLogo, '/');
    }
    $storeLogo = htmlspecialchars($rawLogo, ENT_QUOTES, 'UTF-8');
}
$storeName = htmlspecialchars($s['store']['nome'] ?? 'Cantina IPM', ENT_QUOTES, 'UTF-8');
?>

<div class="d-flex justify-content-between align-items-center p-3 bg-light border-bottom">
    <style>
    /* header logo styling */
    .header-logo { width:48px; height:auto; object-fit:contain; border-radius:6px; margin-right:10px; }
    .header-brand { display:flex; align-items:center; gap:10px; }
    </style>

    <div class="d-flex align-items-center gap-3 header-brand">
        <?php if ($storeLogo): ?>
            <img src="<?= $storeLogo ?>" alt="<?= $storeName ?>" class="header-logo">
        <?php endif; ?>
        <div>
            <h4 class="m-0" style="color:var(--petroleo)"><?= $storeName ?></h4>
            <nav class="nav">
                <a class="nav-link" href="dashboard_vendedor.php">Dashboard</a>
                <a class="nav-link" href="pedidos.php">Pedidos</a>
                <a class="nav-link" href="vendas_vendedor.php">Vendas</a>
                <a class="nav-link" href="relatorios_vendedor.php">Relatórios</a>
            </nav>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted"><?= $horaData ?></span>
        <a href="logout.php" class="btn btn-logout" style="background:transparent;border:1px solid var(--petroleo);color:var(--petroleo);padding:6px 10px;border-radius:6px;text-decoration:none;">
            <!-- simple inline logout icon (SVG) -->
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;margin-right:6px;">
                <path d="M16 17L21 12L16 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M21 12H9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 19H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Sair
        </a>
    </div>
</div>

<div class="notification" id="notification">
    <!-- simple bell icon SVG -->
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;margin-right:6px;">
        <path d="M15 17H9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M18 8a6 6 0 10-12 0c0 7-3 8-3 8h18s-3-1-3-8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M13.73 21a2 2 0 01-3.46 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span id="notification-text"></span>
</div>

<?php
// Include the vendor sidebar here so pages that load this header will have the lateral menu
include __DIR__ . '/menu_vendedor.php';
?>

<script src="/assets/js/pedido-notificacoes.js"></script>
