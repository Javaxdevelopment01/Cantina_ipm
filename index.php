<?php
// Homepage elegante - Cantina do IPM
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Cantina do IPM — Sistema de Vendas</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --petroleo: #012E40;
      --petroleo-light: #1a3a4d;
      --dourado: #D4AF37;
      --muted: #6b7b7b;
      --bg-light: rgba(1,46,64,0.06);
      --card: #ffffff;
      --success: #2fb36a;
      --text-dark: #0b2a2a;
    }
    
    * {
      box-sizing: border-box;
    }
    
    html, body {
      margin: 0;
      padding: 0;
      font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      color: var(--text-dark);
      -webkit-font-smoothing: antialiased;
    }
    
    body {
      background: #ffffff;
    }

    h1, h2, h3, h4, h5, h6 {
      font-family: 'Poppins', sans-serif;
    }

    /* =====================
       HEADER / NAVBAR
       ===================== */
    header {
      background: linear-gradient(135deg, var(--petroleo) 0%, var(--petroleo-light) 100%);
      color: white;
      padding: 16px 0;
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 1000;
      box-shadow: 0 4px 12px rgba(1,46,64,0.15);
    }

    .navbar {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      height: 64px;
    }

    .logo-brand {
      font-size: 18px;
      font-weight: 700;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-family: 'Poppins', sans-serif;
      min-height: 44px;
    }

    .logo-brand img {
      height: 44px;
      width: auto;
      object-fit: contain;
      display: block;
    }

    .logo-brand span {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
    }

    .logo-brand .school-name {
      font-size: 16px;
      font-weight: 800;
      letter-spacing: -0.5px;
    }

    .logo-brand .app-name {
      font-size: 12px;
      font-weight: 500;
      opacity: 0.9;
      letter-spacing: 1px;
    }

    nav.nav-links {
      display: flex;
      gap: 32px;
      align-items: center;
    }

    nav.nav-links a {
      color: rgba(255,255,255,0.85);
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: color 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }

    nav.nav-links a:hover {
      color: var(--dourado);
    }

    /* Menu Hamburger */
    .menu-toggle {
      display: none;
      background: none;
      border: none;
      color: white;
      font-size: 24px;
      cursor: pointer;
      padding: 8px;
    }

    .menu-toggle i {
      font-size: 24px;
    }

    /* Mobile Menu Dropdown */
    nav.nav-links.mobile-open {
      display: flex !important;
    }

    @media (max-width: 768px) {
      .menu-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
      }

      nav.nav-links {
        display: none;
        position: absolute;
        top: 64px;
        left: 0;
        right: 0;
        flex-direction: column;
        gap: 0;
        background: linear-gradient(135deg, var(--petroleo) 0%, var(--petroleo-light) 100%);
        padding: 16px 0;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        border-top: 1px solid rgba(255,255,255,0.1);
      }

      nav.nav-links.mobile-open {
        display: flex;
      }

      nav.nav-links a {
        padding: 12px 24px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
      }

      nav.nav-links a:last-child {
        border-bottom: none;
      }

      .navbar {
        padding: 0 16px;
        height: 56px;
      }

      .logo-brand {
        gap: 8px;
        font-size: 14px;
      }

      .logo-brand img {
        height: 36px;
      }

      .logo-brand .school-name {
        font-size: 14px;
      }

      .logo-brand .app-name {
        font-size: 10px;
      }

      .hero {
        padding: 100px 16px 60px;
        margin-top: 56px;
      }

      .hero h1 {
        font-size: 32px;
      }

      .hero p {
        font-size: 16px;
      }

      .cta-buttons {
        flex-direction: column;
      }

      .cta-buttons .btn {
        width: 100%;
        justify-content: center;
      }

      .hero-icon {
        font-size: 56px;
      }

      .section-header h2 {
        font-size: 28px;
      }

      .profiles-grid, .features-grid {
        grid-template-columns: 1fr;
      }
    }

    /* =====================
       HERO SECTION
       ===================== */
    .hero {
      background: linear-gradient(135deg, var(--petroleo) 0%, var(--petroleo-light) 50%, rgba(1,46,64,0.9) 100%);
      color: white;
      padding: 120px 24px 80px;
      text-align: center;
      margin-top: 64px;
      position: relative;
      overflow: hidden;
    }

    .hero::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -10%;
      width: 500px;
      height: 500px;
      background: radial-gradient(circle, rgba(212,175,55,0.1) 0%, transparent 70%);
      border-radius: 50%;
      pointer-events: none;
    }

    .hero-content {
      max-width: 900px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }

    .hero-icon {
      font-size: 72px;
      margin-bottom: 24px;
      display: inline-block;
      animation: float 3s ease-in-out infinite;
      color: var(--dourado);
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }

    .hero h1 {
      font-size: 48px;
      margin: 0 0 16px;
      font-weight: 800;
      line-height: 1.2;
      font-family: 'Poppins', sans-serif;
    }

    .hero p {
      font-size: 18px;
      color: rgba(255,255,255,0.9);
      margin: 0 0 32px;
      line-height: 1.6;
      font-family: 'Inter', sans-serif;
    }

    .cta-buttons {
      display: flex;
      gap: 16px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn {
      padding: 14px 32px;
      border-radius: 10px;
      border: none;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }

    .btn-primary {
      background: var(--dourado);
      color: var(--petroleo);
      box-shadow: 0 8px 20px rgba(212,175,55,0.3);
    }

    .btn-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 28px rgba(212,175,55,0.4);
    }

    .btn-secondary {
      background: rgba(255,255,255,0.15);
      color: white;
      border: 2px solid rgba(255,255,255,0.3);
    }

    .btn-secondary:hover {
      background: rgba(255,255,255,0.25);
      border-color: rgba(255,255,255,0.5);
    }

    /* =====================
       FEATURES SECTION
       ===================== */
    .features {
      padding: 80px 24px;
      background: #f8fafb;
    }

    .section-container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .section-header {
      text-align: center;
      margin-bottom: 60px;
    }

    .section-header h2 {
      font-size: 36px;
      margin: 0 0 12px;
      color: var(--petroleo);
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
    }

    .section-header p {
      font-size: 16px;
      color: var(--muted);
      margin: 0;
      font-family: 'Inter', sans-serif;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 28px;
    }

    .feature-card {
      background: white;
      border-radius: 14px;
      padding: 32px 24px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      text-align: center;
      transition: all 0.3s ease;
      border: 1px solid rgba(0,0,0,0.03);
    }

    .feature-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 28px rgba(0,0,0,0.1);
    }

    .feature-icon {
      font-size: 48px;
      margin-bottom: 16px;
      color: var(--dourado);
    }

    .feature-card h3 {
      font-size: 18px;
      margin: 0 0 12px;
      color: var(--petroleo);
      font-weight: 700;
      font-family: 'Poppins', sans-serif;
    }

    .feature-card p {
      font-size: 14px;
      color: var(--muted);
      margin: 0;
      line-height: 1.6;
      font-family: 'Inter', sans-serif;
    }

    /* =====================
       PROFILE SECTION
       ===================== */
    .profiles {
      padding: 80px 24px;
      background: white;
    }

    .profiles-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 32px;
    }

    .profile-card {
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      background: white;
      border: 1px solid rgba(0,0,0,0.04);
    }

    .profile-card:hover {
      transform: translateY(-12px);
      box-shadow: 0 16px 40px rgba(0,0,0,0.12);
    }

    .profile-header {
      padding: 32px 24px;
      text-align: center;
    }

    .profile-card.client .profile-header {
      background: linear-gradient(135deg, rgba(47,179,106,0.1), rgba(47,179,106,0.05));
    }

    .profile-card.vendedor .profile-header {
      background: linear-gradient(135deg, rgba(212,175,55,0.1), rgba(212,175,55,0.05));
    }

    .profile-card.admin .profile-header {
      background: linear-gradient(135deg, rgba(1,46,64,0.1), rgba(1,46,64,0.05));
    }

    .profile-emoji {
      font-size: 56px;
      margin-bottom: 12px;
      color: var(--dourado);
    }

    .profile-card h3 {
      font-size: 20px;
      margin: 0 0 8px;
      color: var(--petroleo);
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
    }

    .profile-card .subtitle {
      font-size: 13px;
      color: var(--muted);
      margin: 0 0 20px;
      font-weight: 600;
      font-family: 'Inter', sans-serif;
    }

    .profile-card ul {
      list-style: none;
      padding: 0 24px;
      margin: 0;
      text-align: left;
    }

    .profile-card li {
      font-size: 14px;
      color: var(--text-dark);
      padding: 8px 0;
      border-bottom: 1px solid rgba(0,0,0,0.05);
      font-family: 'Inter', sans-serif;
    }

    .profile-card li:last-child {
      border-bottom: none;
    }

    .profile-card li::before {
      content: '✓ ';
      color: var(--success);
      font-weight: 700;
      margin-right: 8px;
    }

    .profile-footer {
      padding: 24px;
      background: #f8fafb;
      display: flex;
      gap: 12px;
    }

    .profile-footer a {
      flex: 1;
      padding: 12px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      text-align: center;
      transition: all 0.3s ease;
    }

    .profile-card.client .profile-footer a {
      background: var(--success);
      color: white;
    }

    .profile-card.client .profile-footer a:hover {
      opacity: 0.9;
      transform: scale(1.02);
    }

    .profile-card.vendedor .profile-footer a {
      background: var(--dourado);
      color: var(--petroleo);
    }

    .profile-card.vendedor .profile-footer a:hover {
      opacity: 0.9;
      transform: scale(1.02);
    }

    .profile-card.admin .profile-footer a {
      background: var(--petroleo);
      color: white;
    }

    .profile-card.admin .profile-footer a:hover {
      opacity: 0.9;
      transform: scale(1.02);
    }

    /* =====================
       FOOTER
       ===================== */
    footer {
      background: var(--petroleo);
      color: rgba(255,255,255,0.8);
      padding: 48px 24px;
      text-align: center;
      border-top: 1px solid rgba(255,255,255,0.1);
    }

    footer p {
      max-width: 700px;
      margin: 0 auto 16px;
      font-size: 14px;
      line-height: 1.6;
    }

    footer .footer-meta {
      font-size: 12px;
      color: rgba(255,255,255,0.6);
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="navbar">
      <div class="logo-brand">
        <!-- Logo da escola -->
        <img src="assets/images/ipm_logo.png" alt="Logo IPM" onerror="this.style.display='none'" title="Instituto Politécnico do Mondego">
        <span>
          <div class="school-name">IPM Cantina</div>
          <div class="app-name">Gestão de Vendas</div>
        </span>
      </div>
      <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
      </button>
      <nav class="nav-links" id="navMenu">
        <a href="#features">Características</a>
        <a href="#profiles">Acesso</a>
        <a href="#footer">Contacto</a>
      </nav>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-content">
      <div class="hero-icon">
        <i class="fas fa-store"></i>
      </div>
      <h1>Bem-vindo à Cantina do IPM</h1>
      <p>Sistema de Gestão de Vendas integrado. Compre, venda e gerencie de forma simples e eficiente.</p>
      <div class="cta-buttons">
        <a href="#profiles" class="btn btn-primary">
          <i class="fas fa-sign-in-alt"></i> Entrar no Sistema
        </a>
        <a href="#features" class="btn btn-secondary">
          <i class="fas fa-info-circle"></i> Saiba Mais
        </a>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="features" id="features">
    <div class="section-container">
      <div class="section-header">
        <h2>Por que escolher a Cantina IPM?</h2>
        <p>Plataforma moderna com recursos pensados para clientes, vendedores e administradores</p>
      </div>
      
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
          <h3>Interface Intuitiva</h3>
          <p>Design moderno e responsivo, acessível de qualquer dispositivo com facilidade</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon"><i class="fas fa-shopping-cart"></i></div>
          <h3>Catálogo Completo</h3>
          <p>Cardápio atualizado em tempo real com imagens, descrições e preços</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon"><i class="fas fa-lock"></i></div>
          <h3>Pedidos Seguros</h3>
          <p>Sistema seguro de pagamento e rastreamento de encomendas</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
          <h3>Relatórios Avançados</h3>
          <p>Dashboards com análises detalhadas de vendas e desempenho</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon"><i class="fas fa-users"></i></div>
          <h3>Gestão de Utilizadores</h3>
          <p>Controle total sobre clientes, vendedores e permissões de acesso</p>
        </div>

        <div class="feature-card">
          <div class="feature-icon"><i class="fas fa-bolt"></i></div>
          <h3>Performance</h3>
          <p>Plataforma rápida e otimizada para oferecer a melhor experiência</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Profiles Section -->
  <section class="profiles" id="profiles">
    <div class="section-container">
      <div class="section-header">
        <h2>Escolha o seu Perfil</h2>
        <p>Acesso personalizado conforme o seu papel no sistema</p>
      </div>

      <div class="profiles-grid">
        <!-- Cliente -->
        <div class="profile-card client">
          <div class="profile-header">
            <div class="profile-emoji"><i class="fas fa-shopping-bag"></i></div>
            <h3>Cliente</h3>
            <p class="subtitle">Comprador</p>
          </div>
          <ul>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Acesso ao catálogo de produtos</li>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Realizar pedidos online</li>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Consultar histórico de compras</li>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Gerir carrinho de compras</li>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Seguir status dos pedidos</li>
          </ul>
          <div class="profile-footer">
            <a href="app/views/cliente/dashboard_cliente.php">Entrar como Cliente</a>
          </div>
        </div>

        <!-- Vendedor -->
        <div class="profile-card vendedor">
          <div class="profile-header">
            <div class="profile-emoji"><i class="fas fa-user-tie"></i></div>
            <h3>Vendedor</h3>
            <p class="subtitle">Gestor de Vendas</p>
          </div>
          <ul>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Aceitar e gerir pedidos</li>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Registar vendas e recibos</li>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Consultar relatórios</li>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Gerir inventário pessoal</li>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Acompanhamento de comissões</li>
          </ul>
          <div class="profile-footer">
            <a href="app/views/Vendedor/login_vendedor.php">Entrar como Vendedor</a>
          </div>
        </div>

        <!-- Administrador -->
        <div class="profile-card admin">
          <div class="profile-header">
            <div class="profile-emoji"><i class="fas fa-cog"></i></div>
            <h3>Administrador</h3>
            <p class="subtitle">Gestor do Sistema</p>
          </div>
          <ul>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Gerir utilizadores e permissões</li>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Controlar produtos e preços</li>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Visualizar relatórios globais</li>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Configurar sistema</li>
            <li><i class="fas fa-check-circle" style="color: var(--success); margin-right: 6px;"></i> Monitorar todas as vendas</li>
          </ul>
          <div class="profile-footer">
            <a href="app/views/adm/login_adm.php">Entrar como Admin</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer id="footer">
    <p>Sistema de Gestão de Vendas para Cantina do IPM</p>
    <p style="font-size: 13px; color: rgba(255,255,255,0.6);">Trabalho apresentado pelo Grupo nº05</p>
    <div class="footer-meta">
      <p style="margin: 0;">© 2025 Cantina do IPM. Todos os direitos reservados.</p>
    </div>
  </footer>
</body>
</html>
      </div>
      
      </div>
    </div>
  </div>

  <script>
    // Menu Hamburger Toggle
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');

    menuToggle.addEventListener('click', function() {
      navMenu.classList.toggle('mobile-open');
    });

    // Fechar menu ao clicar em um link
    document.querySelectorAll('nav.nav-links a').forEach(link => {
      link.addEventListener('click', function() {
        navMenu.classList.remove('mobile-open');
      });
    });

    // Fechar menu ao clicar fora
    document.addEventListener('click', function(event) {
      if (!event.target.closest('header')) {
        navMenu.classList.remove('mobile-open');
      }
    });
  </script>
</body>
</html>
