// Gerenciamento de notificações para pedidos
const PedidoNotificacoes = {
  init() {
    this.checkNewOrders();
    // Verifica novos pedidos a cada 30 segundos
    setInterval(() => this.checkNewOrders(), 30000);
  },

  async checkNewOrders() {
    try {
      const response = await fetch("../Vendedor/check_new_orders.php");
      const data = await response.json();

      if (data.newOrders) {
        this.showNotification("Novo pedido recebido!");
        // Atualiza a lista de pedidos se estiver na página de pedidos
        if (window.location.pathname.includes("pedidos.php")) {
          location.reload();
        }
      }
    } catch (error) {
      console.error("Erro ao verificar novos pedidos:", error);
    }
  },

  showNotification(message) {
    // Verifica suporte a notificações do navegador
    if (!("Notification" in window)) {
      alert(message);
      return;
    }

    // Se já tem permissão, mostra a notificação
    if (Notification.permission === "granted") {
      this.createNotification(message);
    }
    // Se não foi pedida permissão ainda
    else if (Notification.permission !== "denied") {
      Notification.requestPermission().then((permission) => {
        if (permission === "granted") {
          this.createNotification(message);
        }
      });
    }

    // Também mostra notificação visual na interface
    const notification = document.getElementById("notification");
    if (notification) {
      const notificationText = document.getElementById("notification-text");
      notificationText.textContent = message;
      notification.style.display = "block";

      // Toca um som de notificação se existir
      const audio = new Audio("/assets/sounds/notification.mp3");
      audio.play().catch((e) => console.log("Erro ao tocar som:", e));

      setTimeout(() => {
        notification.style.display = "none";
      }, 5000);
    }
  },

  createNotification(message) {
    const notification = new Notification("Cantina IPM", {
      body: message,
      icon: "/assets/images/logo.png",
      tag: "pedido-novo",
    });

    notification.onclick = function () {
      window.focus();
      if (!window.location.pathname.includes("pedidos.php")) {
        window.location.href = "pedidos.php";
      }
      notification.close();
    };
  },
};

// Inicializa o sistema de notificações
document.addEventListener("DOMContentLoaded", () => {
  PedidoNotificacoes.init();
});
