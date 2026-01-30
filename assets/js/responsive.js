/**
 * Responsividade e Menu Mobile - Script Compartilhado
 * Use este arquivo em todas as views
 */

document.addEventListener("DOMContentLoaded", function () {
  // =====================
  // MENU HAMBURGER
  // =====================
  const menuToggle = document.getElementById("menuToggle");
  const navMenu = document.getElementById("navMenu");

  if (menuToggle && navMenu) {
    menuToggle.addEventListener("click", function (e) {
      e.stopPropagation();
      navMenu.classList.toggle("mobile-open");
    });

    // Fechar menu ao clicar em um link
    document.querySelectorAll("nav.nav-links a").forEach((link) => {
      link.addEventListener("click", function () {
        navMenu.classList.remove("mobile-open");
      });
    });

    // Fechar menu ao clicar fora
    document.addEventListener("click", function (event) {
      if (!event.target.closest("header")) {
        navMenu.classList.remove("mobile-open");
      }
    });
  }

  // =====================
  // SIDEBAR MÓVEL (para admin/vendedor)
  // =====================
  const sidebarToggle = document.getElementById("sidebarToggle");
  const sidebar = document.querySelector(".sidebar-responsive");

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener("click", function () {
      sidebar.classList.toggle("active");
    });

    // Fechar ao clicar fora
    document.addEventListener("click", function (event) {
      if (
        sidebar.classList.contains("active") &&
        !event.target.closest(".sidebar-responsive") &&
        !event.target.closest("#sidebarToggle")
      ) {
        sidebar.classList.remove("active");
      }
    });
  }

  // =====================
  // TABELAS RESPONSIVAS
  // =====================
  // Adiciona data-label a células de tabela para modo mobile
  const responsiveTables = document.querySelectorAll(
    ".table-responsive-wrapper table"
  );

  responsiveTables.forEach((table) => {
    const headers = Array.from(table.querySelectorAll("thead th")).map((th) =>
      th.textContent.trim()
    );

    table.querySelectorAll("tbody td").forEach((td, index) => {
      const cellIndex = index % headers.length;
      if (headers[cellIndex] && !td.hasAttribute("data-label")) {
        td.setAttribute("data-label", headers[cellIndex] + ":");
      }
    });
  });

  // =====================
  // MODAIS RESPONSIVOS
  // =====================
  // Se usar Bootstrap modals, ajusta automaticamente
  const modals = document.querySelectorAll(".modal");

  if (window.innerWidth <= 768) {
    modals.forEach((modal) => {
      modal.addEventListener("show.bs.modal", function () {
        document.body.style.overflow = "hidden";
      });

      modal.addEventListener("hidden.bs.modal", function () {
        document.body.style.overflow = "auto";
      });
    });
  }

  // =====================
  // DETECTA VIEWPORT CHANGES
  // =====================
  let isTablet = window.innerWidth <= 992;

  window.addEventListener("resize", function () {
    const wasTablet = isTablet;
    isTablet = window.innerWidth <= 992;

    // Se mudou de mobile para desktop ou vice-versa
    if (wasTablet !== isTablet) {
      // Fecha menus abertos
      if (navMenu && navMenu.classList.contains("mobile-open")) {
        navMenu.classList.remove("mobile-open");
      }

      if (sidebar && sidebar.classList.contains("active")) {
        sidebar.classList.remove("active");
      }
    }
  });

  // =====================
  // OTIMIZAÇÃO PARA TOUCH
  // =====================
  // Remove hover delays em dispositivos touch
  if (window.matchMedia("(hover: none)").matches) {
    document.documentElement.style.setProperty("--hover-delay", "0ms");
  }
});

/**
 * Função auxiliar para abrir/fechar sidebar
 */
function toggleSidebar() {
  const sidebar = document.querySelector(".sidebar-responsive");
  if (sidebar) {
    sidebar.classList.toggle("active");
  }
}

/**
 * Função auxiliar para detectar se está em mobile
 */
function isMobileViewport() {
  return window.innerWidth <= 768;
}

/**
 * Função para scroll suave
 */
function smoothScroll(elementId) {
  const element = document.getElementById(elementId);
  if (element) {
    element.scrollIntoView({ behavior: "smooth", block: "start" });
  }
}

/**
 * Ajusta altura de elemento para viewport
 */
function adjustHeightToViewport(elementSelector, offset = 0) {
  const element = document.querySelector(elementSelector);
  if (element) {
    const viewportHeight = window.innerHeight;
    const elementHeight = viewportHeight - offset;
    element.style.maxHeight = elementHeight + "px";
  }
}
