<?php
// Menu lateral da área administrativa
// Importante: não chamar session_start() aqui, assume que já foi iniciado na página principal.

// __DIR__ aqui é: app/views/adm/includes
// Para chegar à raíz do projeto: ../../../../
// Depois /config/database.php
require_once __DIR__ . '/../../../../config/database.php';

$adminId   = $_SESSION['admin_id']   ?? null;
$adminNome = $_SESSION['admin_nome'] ?? 'Administrador';
$adminFoto = $_SESSION['admin_foto'] ?? null;

// Se não houver foto em sessão (por exemplo, conta criada antes do campo existir),
// vamos tentar buscar da base de dados uma única vez.
if ($adminId && empty($adminFoto)) {
    try {
        $stmt = $conn->prepare("SELECT foto_perfil FROM admin WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $adminId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $adminFoto = $row['foto_perfil'] ?? null;
            $_SESSION['admin_foto'] = $adminFoto;
        }
    } catch (Throwable $e) {
        // Se der erro aqui, apenas segue sem foto de perfil
    }
}

$adminNome = htmlspecialchars($adminNome, ENT_QUOTES, 'UTF-8');

if ($adminFoto) {
    // Normaliza caminho: se não começar com http ou /, adiciona /
    if (strpos($adminFoto, 'http://') !== 0 && strpos($adminFoto, 'https://') !== 0) {
        if ($adminFoto[0] !== '/') {
            $adminFoto = '/' . ltrim($adminFoto, '/');
        }
    }
    $adminFoto = htmlspecialchars($adminFoto, ENT_QUOTES, 'UTF-8');
} else {
    $adminFoto = null;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    /* Variáveis atualizadas para responsividade */
    :root {
        --adm-petroleo: #012E40;
        --adm-dourado: #D4AF37;
        --adm-bg: #f7f8fa;
        --sidebar-width: 250px;
        --sidebar-collapsed-width: 80px;
    }

    body {
        font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        margin: 0;
        background: var(--adm-bg);
        overflow-x: hidden; /* Previne scroll horizontal em mobile */
    }

    .sidebar-adm {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: var(--sidebar-width);
        background: var(--adm-petroleo);
        color: #ffffff;
        display: flex; /* Flex layout */
        flex-direction: column; /* Vertical stack */
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1000;
        box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        padding: 0; /* Clear padding, will use inner containers */
        overflow: hidden; /* Prevent body scroll */
    }

    /* Scrollable Area for Menu Items */
    .sidebar-content-wrapper {
        flex: 1;
        overflow-y: auto;
        padding: 1.6rem 1rem;
        width: 100%;
        /* Custom scrollbar for webkit */
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,0.2) transparent;
    }
    
    .sidebar-content-wrapper::-webkit-scrollbar { width: 4px; }
    .sidebar-content-wrapper::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }

    /* Footer Area for Collapse Button */
    .sidebar-footer {
        flex-shrink: 0;
        padding: 1rem;
        background: rgba(0,0,0,0.1); /* Slight separation */
        display: flex;
        justify-content: center;
        align-items: center;
        border-top: 1px solid rgba(255,255,255,0.05);
    }

    .sidebar-adm.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    .sidebar-adm .logo {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 2rem;
        text-align: center;
        color: var(--adm-dourado);
        letter-spacing: 0.04em;
        transition: all 0.3s ease;
    }

    .sidebar-adm .avatar,
    .sidebar-adm .avatar-img {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25);
        object-fit: cover;
    }

    .sidebar-adm .avatar {
        background: radial-gradient(circle at 30% 20%, #ffeaa7, #D4AF37);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--adm-petroleo);
        font-size: 1.35rem;
        font-weight: 800;
    }

    .sidebar-adm.collapsed .logo-text {
        display: none;
    }

    /* Ajustes quando recolhido para ficar apenas com ícones */
    .sidebar-adm.collapsed .sidebar-content-wrapper {
        padding-left: 0.6rem;
        padding-right: 0.6rem;
    }

    .sidebar-adm.collapsed .avatar-img {
        width: 40px;
        height: 40px;
    }

    .sidebar-adm.collapsed .logo {
        align-items: center;
    }

    .sidebar-adm a {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        color: #ffffff;
        text-decoration: none;
        padding: 0.7rem 1rem;
        border-radius: 0.5rem;
        margin-bottom: 0.35rem;
        transition: all 0.25s ease;
        font-size: 0.95rem;
        flex-shrink: 0; /* Prevent shrinking */
    }

    .sidebar-adm a:hover,
    .sidebar-adm a.active {
        background: var(--adm-dourado);
        color: var(--adm-petroleo);
        font-weight: 600;
        transform: translateX(3px);
    }

    .sidebar-adm.collapsed a {
        justify-content: center;
        padding: 0.7rem 0;
    }

    .sidebar-adm.collapsed .link-text {
        display: none;
    }

    /* Botão de Colapso Desktop - STATIC inside flex footer */
    .collapse-btn-adm {
        background: var(--adm-dourado);
        border: none;
        color: var(--adm-petroleo);
        font-weight: 600;
        padding: 0.45rem 0.9rem;
        border-radius: 999px;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        width: 100%; /* Fill footer in collapsed mode or auto */
        justify-content: center;
        max-width: 140px;
    }

    .sidebar-adm.collapsed .collapse-btn-adm {
        padding: 0.45rem;
        width: auto;
        border-radius: 50%;
        aspect-ratio: 1/1;
    }

    .sidebar-adm.collapsed .collapse-btn-adm .link-text {
        display: none;
    }

    .collapse-btn-adm:hover {
        background: #f5e2a0;
    }
    
    @media (max-width: 768px) { .collapse-btn-adm, .sidebar-footer { display: none; } }

    .main-adm {
        margin-left: var(--sidebar-width);
        padding: 28px;
        transition: margin-left 0.3s ease;
    }

    .main-adm.collapsed {
        margin-left: var(--sidebar-collapsed-width);
    }

    /* === MOBILE RESPONSIVE ADM === */
    /* Botão Hambúrguer Mobile */
    .ADM-mobile-toggle-btn {
        display: none;
        position: fixed; top: 15px; left: 15px; z-index: 1100;
        background: var(--adm-petroleo); color: var(--adm-dourado); border: none;
        padding: 10px; border-radius: 8px; cursor: pointer;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2); font-size: 1.2rem;
    }

    /* Overlay Escuro */
    .ADM-sidebar-overlay {
        display: none;
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.5); z-index: 999;
        opacity: 0; transition: opacity 0.3s;
    }

    @media (max-width: 768px) {
        /* Mostra botão hambúrguer */
        .ADM-mobile-toggle-btn { display: block; }

        /* Sidebar oculta por padrão (fora da tela) */
        .sidebar-adm {
            width: 250px;
            transform: translateX(-100%);
            box-shadow: none;
        }
        
        /* Quando aberta */
        .sidebar-adm.mobile-open {
            transform: translateX(0);
            box-shadow: 4px 0 15px rgba(0,0,0,0.3);
        }
        
        /* Ajuste de conteúdo principal */
        .main-adm, .main-adm.collapsed {
            margin-left: 0 !important;
            padding: 80px 16px 20px 16px !important; /* Mais padding top para o botão */
            width: 100% !important;
        }

        /* Esconde elementos do desktop */
        .sidebar-adm.collapsed { width: 250px; transform: translateX(-100%); }
        .sidebar-adm.collapsed.mobile-open { transform: translateX(0); }
        .sidebar-adm.collapsed .logo-text, .sidebar-adm.collapsed .link-text { display: block; }
        
        /* Overlay ativo */
        .ADM-sidebar-overlay.active { display: block; opacity: 1; }
    }
</style>

<!-- Botão Mobile -->
<button class="ADM-mobile-toggle-btn" id="admMobileToggle">
    <i class="fa-solid fa-bars"></i>
</button>

<!-- Overlay -->
<div class="ADM-sidebar-overlay" id="admSidebarOverlay"></div>

<div class="sidebar-adm" id="sidebarAdm">
    <div class="sidebar-content-wrapper">
        <div class="logo">
            <?php if ($adminFoto): ?>
                <img src="<?= $adminFoto ?>" alt="Foto do administrador" class="avatar-img">
            <?php else: ?>
                <div class="avatar">
                    <?php
                    $iniciais = '';
                    if (!empty($adminNome)) {
                        $partes = preg_split('/\s+/', trim($adminNome));
                        $iniciais = strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
                    }
                    echo $iniciais ?: 'AD';
                    ?>
                </div>
            <?php endif; ?>
            <span class="logo-text"><?= $adminNome ?></span>
            <small class="logo-text" style="font-size:0.75rem; opacity:0.8;">Administrador</small>
        </div>

        <a href="dashboard_adm.php" class="<?= $current_page === 'dashboard_adm.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i><span class="link-text">Dashboard</span>
        </a>
        <a href="gestao_produtos.php" class="<?= $current_page === 'gestao_produtos.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-boxes-stacked"></i><span class="link-text">Produtos</span>
        </a>
        <a href="gestao_vendedores.php" class="<?= $current_page === 'gestao_vendedores.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-tie"></i><span class="link-text">Vendedores</span>
        </a>
        <a href="gestao_clientes.php" class="<?= $current_page === 'gestao_clientes.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i><span class="link-text">Clientes</span>
        </a>
        <a href="gestao_pedidos.php" class="<?= $current_page === 'gestao_pedidos.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-receipt"></i><span class="link-text">Pedidos</span>
        </a>
        <a href="relatorios_adm.php" class="<?= $current_page === 'relatorios_adm.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i><span class="link-text">Relatórios</span>
        </a>
        <a href="backup_adm.php" class="<?= $current_page === 'backup_adm.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-database"></i><span class="link-text">Backups</span>
        </a>
        <a href="historico_logins.php" class="<?= $current_page === 'historico_logins.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i><span class="link-text">Histórico</span>
        </a>
        <a href="gestao_resets.php" class="<?= $current_page === 'gestao_resets.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-key"></i><span class="link-text">Senhas</span>
        </a>
        <a href="cadastro_adm.php" class="<?= $current_page === 'cadastro_adm.php' ? 'active' : '' ?>" style="background: rgba(212,175,55,0.15); border-left: 2px solid var(--adm-dourado);">
            <i class="fa-solid fa-user-shield"></i><span class="link-text">Novo Admin</span>
        </a>
        <a href="settings_adm.php" class="<?= $current_page === 'settings_adm.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gear"></i><span class="link-text">Definições</span>
        </a>
        <a href="logout_adm.php">
            <i class="fa-solid fa-right-from-bracket"></i><span class="link-text">Sair</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <button class="collapse-btn-adm" id="collapseBtnAdm">
            <i class="fa-solid fa-angles-left" id="collapseIconAdm"></i>
            <span class="link-text">Recolher</span>
        </button>
    </div>
</div>

<script>
    (function () {
        const sidebar = document.getElementById('sidebarAdm');
        const collapseBtn = document.getElementById('collapseBtnAdm');
        const collapseIcon = document.getElementById('collapseIconAdm');
        const mobileToggle = document.getElementById('admMobileToggle');
        const overlay = document.getElementById('admSidebarOverlay');
        const mainAdm = document.querySelector('.main-adm');

        // --- Desktop Collapse Logic ---
        if (collapseBtn && sidebar) {
            // carregar estado salvo
            const saved = localStorage.getItem('admSidebarCollapsed');
            if (saved === '1') {
                sidebar.classList.add('collapsed');
                if (mainAdm) mainAdm.classList.add('collapsed');
                if (collapseIcon) {
                    collapseIcon.classList.remove('fa-angles-left');
                    collapseIcon.classList.add('fa-angles-right');
                }
            }

            collapseBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                if (mainAdm) mainAdm.classList.toggle('collapsed');

                const collapsed = sidebar.classList.contains('collapsed');
                try { localStorage.setItem('admSidebarCollapsed', collapsed ? '1' : '0'); } catch(e){}

                if (collapseIcon) {
                    if (collapsed) {
                        collapseIcon.classList.replace('fa-angles-left', 'fa-angles-right');
                    } else {
                        collapseIcon.classList.replace('fa-angles-right', 'fa-angles-left');
                    }
                }
            });
        }

        // --- Mobile Logic ---
        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('active');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
            });
        }
        
        // Auto-fix layout se não tiver classe main-adm definida na página pai
        // Tenta encontrar o container principal e aplicar margens se necessário
        if (window.innerWidth <= 768) {
             const anyMain = document.querySelector('main') || document.querySelector('.main-content');
             if (anyMain) {
                 anyMain.style.marginLeft = '0';
                 anyMain.style.width = '100%';
                 anyMain.style.paddingTop = '70px';
             }
        }
        
    })();
</script>


