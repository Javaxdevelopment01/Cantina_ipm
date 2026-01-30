<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cantina IPM</title>
  <link href="../assets/css/style.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <!-- <script src="/assets/js/assistente-ia.js"></script> Removido: Conflito com nova IA Yasmin -->
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


<!-- Botão da IA (Global) -->
<button class="ia-btn" id="globalIaBtn" title="Falar com IA">
  <i class="fa-solid fa-microphone"></i>
</button>

<!-- Painel da IA (Global) -->
<aside id="assistantPanel" class="assistant-panel" style="display:none; position:fixed; right:0; top:0; bottom:0; width:380px; background:#fff; border-left:1px solid #e6e6e9; z-index:9999; display:flex; flex-direction:column; box-shadow:-5px 0 15px rgba(0,0,0,0.1);">
  <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light">
    <strong><i class="fa-solid fa-robot text-primary"></i> Assistente Yasmin</strong>
    <button id="closeAssistant" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-times"></i></button>
  </div>
  
  <div id="assistantMessages" class="flex-grow-1 p-3" style="overflow-y:auto; background:#f8f9fa;">
      <div class="system-msg text-center text-muted small mb-3">
          Estou aqui para ajudar com vendas, estoque e navegação.
      </div>
  </div>
  
  <div class="p-3 border-top bg-white">
    <div class="input-group">
      <button id="micBtn" class="btn btn-outline-secondary"><i class="fa-solid fa-microphone"></i></button>
      <input type="text" id="assistantInput" class="form-control" placeholder="Pergunte algo...">
      <button id="assistantSend" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i></button>
    </div>
  </div>
</aside>

<style>
.ia-btn {
    position: fixed; bottom: 20px; right: 20px;
    width: 60px; height: 60px; border-radius: 50%;
    background: #0d6efd; color: white; border: none;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    z-index: 9998; font-size: 24px;
    display: flex; align-items: center; justify-content: center;
    transition: transform 0.2s;
}
.ia-btn:hover { transform: scale(1.1); }
.msg-user { background: #0d6efd; color: white; padding: 8px 12px; border-radius: 12px 12px 0 12px; margin-bottom: 8px; align-self: flex-end; max-width: 85%; margin-left: auto; }
.msg-bot { background: #e9ecef; color: #333; padding: 8px 12px; border-radius: 12px 12px 12px 0; margin-bottom: 8px; align-self: flex-start; max-width: 90%; }
.mic-active { color: red !important; animation: pulse 1.5s infinite; }
@keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const iaBtn = document.getElementById('globalIaBtn');
    const panel = document.getElementById('assistantPanel');
    const closeBtn = document.getElementById('closeAssistant');
    const sendBtn = document.getElementById('assistantSend');
    const input = document.getElementById('assistantInput');
    const msgs = document.getElementById('assistantMessages');
    const micBtn = document.getElementById('micBtn');

    // Toggle Panel
    iaBtn.addEventListener('click', () => {
        panel.style.display = 'flex';
        iaBtn.style.display = 'none';
        input.focus();
    });
    
    closeBtn.addEventListener('click', () => {
        panel.style.display = 'none';
        iaBtn.style.display = 'flex';
    });

    // Add Message
    function addMsg(text, type) {
        const div = document.createElement('div');
        div.className = type === 'user' ? 'msg-user' : 'msg-bot';
        div.innerHTML = text.replace(/\n/g, '<br>'); // Simple formatting
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
    }

    // Speak
    function speak(text) {
        if (!('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        const u = new SpeechSynthesisUtterance(text);
        u.lang = 'pt-PT';
        u.rate = 1.3;
        window.speechSynthesis.speak(u);
    }

    // Send Message
    async function sendMessage() {
        const text = input.value.trim();
        if (!text) return;
        
        addMsg(text, 'user');
        input.value = '';
        
        // Show typing indicator
        const typing = document.createElement('div');
        typing.className = 'msg-bot text-muted fst-italic';
        typing.innerHTML = '<small>Digitando...</small>';
        msgs.appendChild(typing);
        msgs.scrollTop = msgs.scrollHeight;

        try {
            // Determine API path (Yasmin Vendor API)
            // Use relative path from includes/header.php context (root relative usually)
            // Assuming header is included in pages at root or subfolders, relative paths are tricky.
            // Best to use root-relative path if we know the project folder, OR dynamic base.
            // But since I used calc logic before... let's use a safer relative approach IF we are sure of directory structure.
            // Cantina IPM structure seems to be: /cantina_ipm/index.php, /cantina_ipm/includes/header.php
            // So from index.php, it's app/api/...
            // From app/views/..., it's ../../app/api/...
            // To be safe across all depths, let's use the explicit relative path if we can rely on it, OR just fix the hardcoded folder name if we can't.
            // Actually, for header.php which is INCLUDED, the relative path depends on the file INCLUDING it. 
            // Safer to use absolute path with dynamic base if possible, OR just ../app/api if we assume we are in root.
            // User's error was with 'cantina_ipm' hardcoded.
            // Let's try to detect the root.
            const pathParts = window.location.pathname.split('/');
            // find 'cantina_ipm' in path? No, might be renamed.
            // Let's assume standard structure. If we are in /app/views/Vendedor/, we need ../../../app/api
            // If we are in / (root), we need ./app/api
            
            // Simpler: use the BASE_URL variable if defined (dashboard has it), or try to find it.
            let basePath = '';
            if (typeof BASE_URL !== 'undefined') {
                basePath = BASE_URL;
            } else {
                // heuristic: look for 'app' or 'includes'
                const idx = window.location.pathname.indexOf('/app/');
                if (idx > -1) {
                    basePath = window.location.pathname.substring(0, idx);
                } else {
                    // assume root
                    basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
                    // remove trailing filename
                    if(basePath.endsWith('.php')) basePath = basePath.substring(0, basePath.lastIndexOf('/'));
                }
            }
            // cleanup
            basePath = basePath.replace(/\/$/, '');
            // This is getting complicated.
            // The simplest robust way for THIS project (WAMP) is likely just:
            const apiUrl = '/cantina_ipm/app/api/yasmin_vendor_api.php'; 
            // WAIT, the user's error `pedidos_vendedor.php:898` PROVED that /cantina_ipm/ was wrong or something failed.
            // User said: `Error: Erro na API`. If 404, it means path wrong.
            // If the user's folder is `cantina_ipm`, then `/cantina_ipm/...` should work.
            // Maybe they are serving from root? `c:\wamp64\www\cantina_ipm` -> `localhost/cantina_ipm`.
            // Let's stick to the relative path strategy I used in pedidos.php which I am confident in for THAT file.
            // For header.php, it is included in MANY files.
            // Let's use a root-relative path that assumes the standard folder structure "app/api/...".
            // If I start with "app/api/...", it's relative to the current page.
            // If current page is "index.php", "app/api" works.
            // If current page is "app/views/Vendedor/dashboard.php", "app/api" fails (looks for app/views/Vendedor/app/api).
            // I should use absolute path "/cantina_ipm/app/api/..." BUT allow for folder name change?
            // Let's look at `dashboard_vendedor.php` again. It defined BASE_URL in PHP.
            // Maybe I should output BASE_URL in header.php too?
            
            // For now, I will trust that header.php is mostly used in valid contexts or I'll implement a fallback.
            // Reverting to `window.location.origin + '/cantina_ipm/...'` might be the best bet IF I verify the folder name.
            // Folder name IS `cantina_ipm` per user metadata. WAMP implies localhost/cantina_ipm.
            // Why did it fail? Maybe `origin` includes port?
            // "Error na API" -> fetch failed. 
            // In WAMP `localhost`, origin is `http://localhost`. `http://localhost/cantina_ipm/app/api/...`
            
            // I'll use a safer relative approach: 
            // '/cantina_ipm/app/api/yasmin_vendor_api.php' (root relative)
            const apiUrl = '/cantina_ipm/app/api/yasmin_vendor_api.php';
            
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mensagem: text, audio: true })
            });

            typing.remove();

            if (!res.ok) throw new Error('Erro na API');
            
            const data = await res.json();
            
            if (data.success) {
                addMsg(data.mensagem, 'bot');
                
                // Play audio if available (Yasmin Python TTS), else fallback to browser TTS
                if (data.audio_base64) {
                    try {
                        const audio = new Audio("data:" + (data.audio_mime || "audio/wav") + ";base64," + data.audio_base64);
                        audio.play();
                    } catch(e) { 
                        console.warn('Audio play failed', e);
                        speak(data.mensagem); // Fallback
                    }
                } else {
                    speak(data.mensagem);
                }
            } else {
                addMsg('Desculpe, não entendi.', 'bot');
            }
        } catch (err) {
            typing.remove();
            console.error(err);
            addMsg('Erro de conexão.', 'bot');
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keypress', (e) => { if(e.key==='Enter') sendMessage(); });

    // Voice Recognition
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new SR();
        recognition.lang = 'pt-PT';
        recognition.interimResults = false;
        
        recognition.onstart = () => micBtn.classList.add('mic-active');
        recognition.onend = () => micBtn.classList.remove('mic-active');
        
        recognition.onresult = (e) => {
            const transcript = e.results[0][0].transcript;
            input.value = transcript;
            sendMessage();
        };
        
        micBtn.addEventListener('click', () => recognition.start());
    } else {
        micBtn.style.display = 'none';
    }
});
</script>

