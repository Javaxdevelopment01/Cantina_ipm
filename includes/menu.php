<?php
// includes/menu.php

// Captura o nome da página atual (ex: produtos.php, vendas.php, etc.)
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Importa ícones do Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root {
    --petroleo: #012E40;
    --dourado: #D4AF37;
    --bg: #f8f9fa;
}

body {
    font-family: "Segoe UI", sans-serif;
    margin: 0;
    background: var(--bg);
}

/* ===== SIDEBAR ===== */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 250px;
    background: var(--petroleo);
    color: white;
    padding: 1.5rem 1rem;
    overflow-y: auto;
    transition: all 0.3s ease;
    z-index: 100;
}

.sidebar.collapsed {
    width: 80px;
}

/* ===== LOGO ===== */
.sidebar .logo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: bold;
    font-size: 1.4rem;
    margin-bottom: 2rem;
    text-align: center;
    color: var(--dourado);
    letter-spacing: 1px;
    transition: all 0.3s ease;
}

.sidebar.collapsed .logo-text {
    display: none;
}

.sidebar .logo i {
    font-size: 1.8rem;
    vertical-align: middle;
}

/* ===== LINKS ===== */
.sidebar a {
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
    text-decoration: none;
    padding: 0.7rem 1rem;
    border-radius: 0.4rem;
    margin-bottom: 0.3rem;
    transition: all 0.3s ease;
    font-size: 15px;
}

.sidebar a:hover,
.sidebar a.active {
    background: var(--dourado);
    color: var(--petroleo);
    font-weight: 600;
    transform: translateX(3px);
}

.sidebar.collapsed a {
    justify-content: center;
    padding: 0.7rem 0;
}

.sidebar.collapsed .link-text {
    display: none;
}

/* ===== CONTEÚDO PRINCIPAL ===== */
.main {
    margin-left: 250px;
    padding: 2rem;
    min-height: 100vh;
    transition: all 0.3s ease;
}

.main.collapsed {
    margin-left: 80px;
}

/* ===== BOTÃO COLAPSAR ===== */
.collapse-btn {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--dourado);
    border: none;
    color: var(--petroleo);
    font-weight: bold;
    padding: 8px 14px;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.collapse-btn:hover {
    background: var(--petroleo);
    color: var(--dourado);
}
</style>

<div class="sidebar" id="sidebar">
    <div class="logo">
        <i class="fa-solid fa-utensils"></i>
        <span class="logo-text">IPM Cantina</span>
    </div>

    <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-gauge-high"></i><span class="link-text">Dashboard</span>
    </a>

    <a href="produtos.php" class="<?= $current_page == 'produtos.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-box"></i><span class="link-text">Produtos</span>
    </a>

    <a href="categorias.php" class="<?= $current_page == 'categorias.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-tags"></i><span class="link-text">Categorias</span>
    </a>

    <a href="vendas.php" class="<?= $current_page == 'vendas.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-cart-shopping"></i><span class="link-text">Vendas</span>
    </a>

    <a href="relatorios.php" class="<?= $current_page == 'relatorios.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-line"></i><span class="link-text">Relatórios</span>
    </a>

    <a href="usuarios.php" class="<?= $current_page == 'usuarios.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-users"></i><span class="link-text">Usuários</span>
    </a>

     <a href="configuracoes.php" class="<?= $current_page == 'configuracoes.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-gear"></i><span class="link-text">Definições</span>
    </a>
<button class="collapse-btn" id="collapseBtn">
        <i class="fa-solid fa-angles-left" id="collapseIcon"></i>
        <span class="link-text">Colapsar</span>
    </button>
</div>

<script>
// Elementos principais
const sidebar = document.getElementById('sidebar');
const collapseBtn = document.getElementById('collapseBtn');
const collapseIcon = document.getElementById('collapseIcon');
const main = document.querySelector('.main');

// Alterna entre colapsado e expandido
collapseBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    main.classList.toggle('collapsed');

    // Muda o ícone do botão
    if (sidebar.classList.contains('collapsed')) {
        collapseIcon.classList.replace('fa-angles-left', 'fa-angles-right');
    } else {
        collapseIcon.classList.replace('fa-angles-right', 'fa-angles-left');
    }
});
</script>
