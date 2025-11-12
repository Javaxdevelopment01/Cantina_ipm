// Monitora alertas de estoque em tempo real
class MonitorEstoque {
  constructor() {
    this.assistente = window.assistente;
    this.iniciarMonitoramento();
  }

  async verificarEstoqueBaixo() {
    const response = await fetch(
      "/app/views/Vendedor/produtos_vendedor.php?ajax=produtos"
    );
    const produtos = await response.json();

    const produtosBaixos = produtos.filter((p) => p.quantidade <= 5);

    if (produtosBaixos.length > 0) {
      const mensagem = this.formatarAlertaEstoque(produtosBaixos);
      this.assistente.enviarMensagem("verificar estoque");

      // Atualiza visual dos produtos com estoque baixo
      produtosBaixos.forEach((p) => {
        const elemento = document.querySelector(`[data-prod-id="${p.id}"]`);
        if (elemento) {
          elemento.classList.add("alert-low-stock");
          const contador = elemento.querySelector(".qty-count");
          if (contador) {
            contador.textContent = p.quantidade;
          }
        }
      });
    }
  }

  formatarAlertaEstoque(produtos) {
    const mensagens = produtos.map(
      (p) => `${p.nome} tem apenas ${p.quantidade} unidades`
    );
    return `Atenção! ${mensagens.join(". ")}`;
  }

  iniciarMonitoramento() {
    // Verifica a cada 30 segundos
    setInterval(() => this.verificarEstoqueBaixo(), 30000);
    // Verificação inicial
    this.verificarEstoqueBaixo();
  }
}

// Inicia o monitoramento quando a página carregar
document.addEventListener("DOMContentLoaded", () => {
  const monitor = new MonitorEstoque();
});
