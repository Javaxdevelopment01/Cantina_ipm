<?php
/**
 * Header Responsivo com Menu Hamburger
 * Use este arquivo em views com: include(__DIR__ . '/../includes/header_responsive.php');
 */
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

    body {
      margin: 0;
      font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      color: var(--text-dark);
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
        display: block;
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
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="navbar">
      <div class="logo-brand">
        <img src="<?php echo (strpos($_SERVER['PHP_SELF'], '/app/views/') !== false ? '../../' : ''); ?>assets/images/ipm_logo.png" alt="Logo IPM" onerror="this.style.display='none'" title="Instituto Politécnico do Mondego">
        <span>
          <div class="school-name">IPM Cantina</div>
          <div class="app-name">Gestão de Vendas</div>
        </span>
      </div>
      <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
      </button>
      <nav class="nav-links" id="navMenu">
        <a href="#home">Home</a>
        <a href="#features">Características</a>
        <a href="#profiles">Acesso</a>
        <a href="#footer">Contacto</a>
      </nav>
    </div>
  </header>

  <!-- Espaçador para evitar sobreposição com conteúdo fixo -->
  <div style="height: 64px;"></div>

  <script>
    // Menu Hamburger Toggle
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');

    if (menuToggle && navMenu) {
      menuToggle.addEventListener('click', function(e) {
        e.stopPropagation();
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
    }
  </script>
</body>
</html>
