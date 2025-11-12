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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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

<div class="d-flex justify-content-between align-items-center p-3 bg-light border-bottom">
    <div class="d-flex align-items-center gap-3">
        <h4 class="m-0" style="color:var(--petroleo)">Cantina IPM</h4>
        <nav class="nav">
            <a class="nav-link" href="dashboard_vendedor.php">Dashboard</a>
            <a class="nav-link" href="pedidos.php">Pedidos</a>
            <a class="nav-link" href="vendas_vendedor.php">Vendas</a>
            <a class="nav-link" href="relatorios_vendedor.php">Relatórios</a>
        </nav>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted"><?= $horaData ?></span>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>
</div>

<div class="notification" id="notification">
    <i class="fas fa-bell me-2"></i>
    <span id="notification-text"></span>
</div>

<script src="/assets/js/pedido-notificacoes.js"></script>
