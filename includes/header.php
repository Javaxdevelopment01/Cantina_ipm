<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cantina IPM</title>
  <link href="../assets/css/style.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <script src="/assets/js/assistente-ia.js"></script>
</head>
<body>
  <div class="sidebar">
    <div class="logo"><i class="fa-solid fa-utensils"></i> Cantina IPM</div>
    <a href="index.php" class="<?= basename($_SERVER['PHP_SELF'])=='index.php' ? 'active' : '' ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="produtos.php" class="<?= basename($_SERVER['PHP_SELF'])=='produtos.php' ? 'active' : '' ?>"><i class="fa-solid fa-box"></i> Produtos</a>
    <a href="categorias.php" class="<?= basename($_SERVER['PHP_SELF'])=='categorias.php' ? 'active' : '' ?>"><i class="fa-solid fa-tags"></i> Categorias</a>
    <a href="vendas.php" class="<?= basename($_SERVER['PHP_SELF'])=='vendas.php' ? 'active' : '' ?>"><i class="fa-solid fa-cart-shopping"></i> Vendas</a>
    <a href="estoque.php" class="<?= basename($_SERVER['PHP_SELF'])=='estoque.php' ? 'active' : '' ?>"><i class="fa-solid fa-warehouse"></i> Estoque</a>
    <a href="relatorios.php" class="<?= basename($_SERVER['PHP_SELF'])=='relatorios.php' ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i> Relatórios</a>
    <a href="usuarios.php" class="<?= basename($_SERVER['PHP_SELF'])=='usuarios.php' ? 'active' : '' ?>"><i class="fa-solid fa-users"></i> Usuários</a>
    <a href="configuracoes.php" class="<?= basename($_SERVER['PHP_SELF'])=='configuracoes.php' ? 'active' : '' ?>"><i class="fa-solid fa-gear"></i> Configurações</a>
  </div>

  <div class="main">
    <div class="topbar">
      <h4>Painel Cantina IPM</h4>
      <span id="datetime"></span>
    </div>

<script>
function atualizarDataHora() {
  const agora = new Date();
  const dataHora = agora.toLocaleString('pt-PT', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
  document.getElementById('datetime').textContent = dataHora;
}
setInterval(atualizarDataHora, 1000);
atualizarDataHora();
</script>

<!-- Botão da IA -->
<button class="ia-btn" title="Falar com IA">
  <i class="fa-solid fa-microphone"></i>
</button>

<!-- Painel da IA -->
<aside id="assistantPanel" class="assistant-panel" style="display:none; position:fixed; right:0; top:0; bottom:0; width:420px; background:#fff; border-left:1px solid #e6e6e9; z-index:9998; padding:1rem; overflow-y:auto;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <strong>Assistente IA</strong>
    <button id="closeAssistant" class="btn btn-sm btn-outline-secondary">Fechar</button>
  </div>
  <div id="assistantMessages" style="height:400px; overflow-y:auto;" class="mb-3"></div>
  <div class="input-group">
    <input type="text" id="assistantInput" class="form-control" placeholder="Escreva sua mensagem...">
    <button id="assistantSend" class="btn btn-primary">Enviar</button>
  </div>
</aside>

<script>
const iaBtn = document.querySelector('.ia-btn');
const assistantPanel = document.getElementById('assistantPanel');
const closeAssistant = document.getElementById('closeAssistant');
const assistantSend = document.getElementById('assistantSend');
const assistantInput = document.getElementById('assistantInput');
const assistantMessages = document.getElementById('assistantMessages');

// Abrir/fechar painel
iaBtn.addEventListener('click', () => assistantPanel.style.display = 'block');
closeAssistant.addEventListener('click', () => assistantPanel.style.display = 'none');

// Enviar mensagem
assistantSend.addEventListener('click', sendMessage);
assistantInput.addEventListener('keypress', function(e){
    if(e.key === 'Enter') sendMessage();
});

async function sendMessage() {
    const msg = assistantInput.value.trim();
    if(!msg) return;

    const msgBox = document.createElement('div');
    msgBox.innerHTML = `<strong>Você:</strong> ${msg}`;
    assistantMessages.appendChild(msgBox);
    assistantInput.value = '';
    assistantMessages.scrollTop = assistantMessages.scrollHeight;

    try {
        const res = await fetch('../ia/responder.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({mensagem: msg})
        });
        const data = await res.json();

        const respBox = document.createElement('div');
        respBox.innerHTML = `<strong>IA:</strong> ${data.resposta}`;
        assistantMessages.appendChild(respBox);
        assistantMessages.scrollTop = assistantMessages.scrollHeight;

        // Falar a resposta
        if('speechSynthesis' in window){
            const utter = new SpeechSynthesisUtterance(data.resposta);
            utter.lang = 'pt-PT';
            window.speechSynthesis.speak(utter);
        }

    } catch(err){
        console.error(err);
        const errorBox = document.createElement('div');
        errorBox.innerHTML = `<strong>IA:</strong> Erro ao se comunicar com o servidor.`;
        assistantMessages.appendChild(errorBox);
    }
}
</script>

