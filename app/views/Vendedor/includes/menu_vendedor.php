<?php
// includes/menu_vendedor.php
// Não chamar session_start() aqui, pois já foi iniciado no dashboard

// Tenta incluir o ficheiro de configuração da base de dados a partir de vários caminhos possíveis
$dbIncluded = false;
$dbCandidates = [
    __DIR__ . '/../../../../config/database.php',
    __DIR__ . '/../../../config/database.php',
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/../../../../../config/database.php'
];
foreach ($dbCandidates as $candidate) {
    if (file_exists($candidate)) {
        require_once $candidate;
        $dbIncluded = true;
        break;
    }
}

// Se não existir $conn, evita erro fatal e define valores por omissão
$vendedor = [];
if (isset($conn) && $conn instanceof PDO) {
    try {
        $stmt = $conn->prepare("SELECT nome, imagem FROM vendedor WHERE id = ?");
        $stmt->execute([$_SESSION['vendedor_id'] ?? 0]);
        $vendedor = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        // Não interrompe a página: usamos valores por omissão
        $vendedor = [];
    }
} else {
    // $conn não disponível — provavelmente a página que incluiu o header não inicializou a base de dados
    $vendedor = [];
}

// Nome e imagem segura
$vendedorNome = htmlspecialchars($vendedor['nome'] ?? 'Vendedor', ENT_QUOTES, 'UTF-8');
$vendedorImagemRaw = $vendedor['imagem'] ?? null;

// Normalizar caminho da imagem (verifica o ficheiro no DOCUMENT_ROOT e faz fallback para session se necessário)
$vendedorImagem = null;
if (!empty($vendedorImagemRaw)) {
    $raw = trim($vendedorImagemRaw);
    $img = $raw;
    // garante que URLs relativos fiquem com / na frente para usar como URL pública
    if (!empty($img) && $img[0] !== '/' && strpos($img, 'http') !== 0) {
        $img = '/' . ltrim($img, '/');
    }

    // 1) se for URL absoluta a partir de http(s), aceita-a
    if (strpos($img, 'http') === 0) {
        $vendedorImagem = htmlspecialchars($img, ENT_QUOTES, 'UTF-8');
    } else {
        // 2) verifica o ficheiro no DOCUMENT_ROOT (caminho real do servidor)
        $fsPath = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__ . '/../../..', DIRECTORY_SEPARATOR) . str_replace('/', DIRECTORY_SEPARATOR, $img);
        if (file_exists($fsPath)) {
            $vendedorImagem = htmlspecialchars($img, ENT_QUOTES, 'UTF-8');
        }
    }
}

// Se não encontrou na BD, tenta pela sessão (login pode ter guardado o caminho)
if (!$vendedorImagem && !empty($_SESSION['vendedor_foto'])) {
    $sess = trim($_SESSION['vendedor_foto']);
    $simg = $sess;
    if ($simg && $simg[0] !== '/' && strpos($simg, 'http') !== 0) $simg = '/' . ltrim($simg, '/');
    if (strpos($simg, 'http') === 0) {
        $vendedorImagem = htmlspecialchars($simg, ENT_QUOTES, 'UTF-8');
    } else {
        $fsPath2 = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__ . '/../../..', DIRECTORY_SEPARATOR) . str_replace('/', DIRECTORY_SEPARATOR, $simg);
        if (file_exists($fsPath2)) {
            $vendedorImagem = htmlspecialchars($simg, ENT_QUOTES, 'UTF-8');
        }
    }
}

// Se ainda não encontrou, tenta caminhos comuns (por exemplo DB guarda só o filename)
// IMPORTANTE: usa direto o caminho do BD mesmo que o ficheiro não exista localmente
// (deixa o browser tentar via HTTP, para vermos o erro real se houver)
if (!$vendedorImagem && !empty($vendedorImagemRaw)) {
    $raw = trim($vendedorImagemRaw);
    
    // Se não começar com /, prefixamos
    if ($raw && $raw[0] !== '/' && strpos($raw, 'http') !== 0) {
        $raw = '/' . $raw;
    }
    
    // Usa direto o caminho (o browser vai tentar carregar via HTTP)
    $vendedorImagem = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
}

// Se não tiver imagem ou for vazio, gera avatar com iniciais
if (!$vendedorImagem) {
    $iniciais = '';
    if (!empty($vendedorNome)) {
        $partes = preg_split('/\s+/', trim($vendedorNome));
        $iniciais = strtoupper(
            mb_substr($partes[0] ?? '', 0, 1) . 
            mb_substr($partes[1] ?? '', 0, 1)
        );
    }
    $vendedorIniciais = $iniciais ?: 'VD';
} else {
    $vendedorIniciais = null;
}

// Captura o nome da página atual
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
/* CSS Reset básico e variáveis */
:root {
    --petroleo: #012E40;
    --dourado: #D4AF37;
    --bg: #f8f9fa;
    --sidebar-width: 250px;
    --sidebar-collapsed-width: 80px;
    --header-height: 60px; /* Ajuste conforme altura real do header */
}

body { font-family:"Segoe UI", sans-serif; margin:0; background:var(--bg); overflow-x: hidden; }

/* Sidebar Base */
.sidebar {
    position: fixed; left:0; top:0; bottom:0; width:var(--sidebar-width);
    background: var(--petroleo); color:white; padding:1.5rem 1rem;
    overflow-y:auto; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); z-index:1000;
    box-shadow: 4px 0 10px rgba(0,0,0,0.1);
}

/* Estado Colapsado (Desktop) */
.sidebar.collapsed { width:var(--sidebar-collapsed-width); }
.sidebar.collapsed .logo-text,
.sidebar.collapsed .link-text { display:none; }
.sidebar.collapsed a { justify-content:center; padding:0.7rem 0; }
.sidebar.collapsed .logo { margin-bottom: 2rem; }

/* Links */
.sidebar a {
    display:flex; align-items:center; gap:12px; color:rgba(255,255,255,0.85); text-decoration:none;
    padding:0.75rem 1rem; border-radius:8px; margin-bottom:0.5rem;
    transition: all 0.2s ease; font-size:15px;
}
.sidebar a:hover, .sidebar a.active {
    background: var(--dourado); color: var(--petroleo); font-weight:600; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.sidebar a i { width: 24px; text-align: center; }

/* Logo */
.sidebar .logo {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:10px; margin-bottom:2.5rem; text-align:center;
}
.profile-img {
    width: 60px; height: 60px; border-radius: 50%; object-fit: cover;
    border: 3px solid var(--dourado); box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
.logo-text { font-weight:700; font-size:1.1rem; color: var(--dourado); letter-spacing:0.5px; }

/* Collapse Button (Desktop) */
.collapse-btn {
    position:absolute; bottom:20px; left:50%; transform:translateX(-50%);
    background: rgba(255,255,255,0.1); border:none; color: white;
    width: 40px; height: 40px; border-radius:50%; cursor:pointer; 
    transition:all 0.3s; display:flex; align-items:center; justify-content:center;
}
.collapse-btn:hover { background: var(--dourado); color: var(--petroleo); }
@media (max-width: 1024px) { .collapse-btn { display: none; } }

/* === MOBILE RESPONSIVE === */
/* Botão Hambúrguer Mobile */
.mobile-toggle-btn {
    display: none;
    position: fixed; top: 15px; left: 15px; z-index: 1100;
    background: var(--petroleo); color: var(--dourado); border: none;
    padding: 10px; border-radius: 8px; cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2); font-size: 1.2rem;
}

/* Overlay Escuro */
.sidebar-overlay {
    display: none;
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 999;
    opacity: 0; transition: opacity 0.3s;
}

/* Mobile Styles */
@media (max-width: 1024px) {
    /* Mostra botão hambúrguer */
    .mobile-toggle-btn { display: block; }

    /* Sidebar oculta por padrão (fora da tela) */
    .sidebar {
        width: 250px; /* Sempre largura total em mobile */
        transform: translateX(-100%);
        box-shadow: none;
    }
    
    /* Quando aberta */
    .sidebar.mobile-open {
        transform: translateX(0);
        box-shadow: 4px 0 15px rgba(0,0,0,0.3);
    }

    /* Ajuste de conteúdo principal */
    .main-page, .main-content, main, .main {
        margin-left: 0 !important;
        padding-top: 70px !important; /* Espaço para o botão hambúrguer */
        width: 100% !important;
    }
    
    /* Esconde elementos do desktop */
    .sidebar.collapsed { width: 250px; } /* Reseta width */
    .sidebar.collapsed .logo-text, .sidebar.collapsed .link-text { display: block; } /* Mostra texto */

    /* Overlay ativo */
    .sidebar-overlay.active {
        display: block; opacity: 1;
    }
}
</style>

<!-- Botão Mobile -->
<button class="mobile-toggle-btn" id="mobileToggle">
    <i class="fa-solid fa-bars"></i>
</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="sidebar">
    <div class="logo">
         <?php if ($vendedorImagem): ?>
             <img src="<?= $vendedorImagem ?>" alt="Foto Vendedor" class="profile-img">
         <?php else: ?>
             <div class="profile-img" style="background:linear-gradient(135deg, var(--dourado), #e0c44f); display:flex; align-items:center; justify-content:center; font-weight:700; color:var(--petroleo); font-size:20px;">
                 <?= $vendedorIniciais ?: 'VD' ?>
             </div>
         <?php endif; ?>
         <span class="logo-text"><?= $vendedorNome ?></span>
     </div>
 
     <a href="dashboard_vendedor.php" class="<?= $current_page == 'dashboard_vendedor.php' ? 'active' : '' ?>">
         <i class="fa-solid fa-chart-line"></i><span class="link-text">Dashboard</span>
     </a>
 
     <a href="produtos_vendedor.php" class="<?= $current_page == 'produtos_vendedor.php' ? 'active' : '' ?>">
         <i class="fa-solid fa-box"></i><span class="link-text">Produtos</span>
     </a>
 
     <a href="clientes_vendedor.php" class="<?= $current_page == 'clientes_vendedor.php' ? 'active' : '' ?>">
         <i class="fa-solid fa-users"></i><span class="link-text">Clientes</span>
     </a>
 
     <a href="vendas_vendedor.php" class="<?= $current_page == 'vendas_vendedor.php' ? 'active' : '' ?>">
         <i class="fa-solid fa-shopping-cart"></i><span class="link-text">Vendas</span>
     </a>
 
     <a href="pedidos_vendedor.php" class="<?= $current_page == 'pedidos_vendedor.php' ? 'active' : '' ?>">
         <i class="fa-solid fa-receipt"></i><span class="link-text">Pedidos</span>
     </a>
 
     <a href="alertas_estoque.php" class="<?= $current_page == 'alertas_estoque.php' ? 'active' : '' ?>">
         <i class="fa-solid fa-triangle-exclamation"></i><span class="link-text">Alertas de Estoque</span>
     </a>
 
     <a href="relatorios_vendedor.php" class="<?= $current_page == 'relatorios_vendedor.php' ? 'active' : '' ?>">
         <i class="fa-solid fa-chart-bar"></i><span class="link-text">Relatórios</span>
     </a>
 
     <a href="definicoes_vendedor.php" class="<?= $current_page == 'definicoes_vendedor.php' ? 'active' : '' ?>">
         <i class="fa-solid fa-gears"></i><span class="link-text">Definições</span>
     </a>
 
     <button class="collapse-btn" id="collapseBtn">
         <i class="fa-solid fa-angle-left" id="collapseIcon"></i>
     </button>
 </div>
 
<script>
    const sidebar = document.getElementById('sidebar');
    const collapseBtn = document.getElementById('collapseBtn');
    const collapseIcon = document.getElementById('collapseIcon');
    const mobileToggle = document.getElementById('mobileToggle');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Toggle Desktop (Colapsar)
    if (collapseBtn) {
        collapseBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            if(sidebar.classList.contains('collapsed')) {
                collapseIcon.className = 'fa-solid fa-angle-right';
                // Salvar preferência se possível
            } else {
                collapseIcon.className = 'fa-solid fa-angle-left';
            }
        });
    }

    // Toggle Mobile (Abrir/Fechar)
    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        });
    }

    // Fechar ao clicar no overlay
    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        });
    }
    
    // Auto-ajuste de layout
    const mainContent = document.querySelector('.main-page') || document.querySelector('main');
    if (mainContent && window.innerWidth <= 768) {
        mainContent.style.marginLeft = '0';
    }
</script>
