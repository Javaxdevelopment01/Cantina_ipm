<?php
session_start();
if(!isset($_SESSION['vendedor_id'])) {
    header('Location: login_vendedor.php');
    exit;
}
require_once __DIR__ . '/../../../config/database.php';

// Determina o BASE_URL dinamicamente
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (strpos($host, ':') === false && $protocol === 'https') {
    $host .= ':443';
} elseif (strpos($host, ':') === false && $protocol === 'http') {
    $host .= ':80';
}
define('BASE_URL', $protocol . '://' . $host);

// Função de segurança
function safe($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// ======= CARDS PRINCIPAIS =======
$totalProdutos = $conn->query("SELECT COUNT(*) FROM produto")->fetchColumn();
$totalVendas = $conn->query("SELECT COUNT(*) FROM venda")->fetchColumn();
$totalClientes = $conn->query("SELECT COUNT(*) FROM cliente")->fetchColumn();
$quantidadeEstoque = $conn->query("SELECT SUM(quantidade) FROM produto")->fetchColumn();

// ======= TOTAL DE PEDIDOS (para YASMIN) =======
$totalPedidos = $conn->query("SELECT COUNT(*) FROM pedido")->fetchColumn();

// ======= ALERTAS DE ESTOQUE =======
$stmt = $conn->prepare("SELECT nome, quantidade FROM produto WHERE quantidade <= 5 ORDER BY quantidade ASC");
$stmt->execute();
$produtosAlerta = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======= PEDIDOS PENDENTES =======
$stmt = $conn->prepare("SELECT COUNT(*) FROM pedido WHERE estado = 'pendente'");
$stmt->execute();
$novosPedidos = $stmt->fetchColumn();

// ======= PRODUTOS MAIS VENDIDOS =======
$stmt = $conn->prepare("
    SELECT p.nome, SUM(pi.quantidade) AS total_vendido, (SUM(pi.quantidade) * p.preco) AS receita
    FROM pedido_itens pi
    JOIN produto p ON pi.id_produto = p.id
    JOIN pedido pd ON pi.id_pedido = pd.id
    WHERE pd.estado = 'atendido'
    GROUP BY pi.id_produto, p.preco
    ORDER BY total_vendido DESC
    LIMIT 5
");
$stmt->execute();
$produtosMaisVendidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======= PRODUTOS COM MENOR ESTOQUE =======
$stmt = $conn->prepare("
    SELECT nome, quantidade
    FROM produto
    ORDER BY quantidade ASC
    LIMIT 5
");
$stmt->execute();
$produtosMenorEstoque = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Vendedor</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/responsive.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'Segoe UI', sans-serif; background:#f7f8fa; }

.main { margin-left:250px; padding:30px; transition:0.3s; }
@media (max-width: 768px) {
    .main { margin-left:0 !important; padding-top:80px; }
}
.cards { display:flex; flex-wrap:wrap; gap:20px; margin-bottom:20px; }
.card-stat {
    flex:1; min-width:200px; background:white; border-radius:12px; padding:20px;
    text-align:center; box-shadow:0 3px 10px rgba(0,0,0,0.1); transition:0.3s;
}
.card-stat:hover { transform:translateY(-3px); }
.card-stat h3 { color:#012E40; font-size:2rem; margin:10px 0; }
.card-stat p { color:#555; }

.btn-ia {
    background:#D4AF37; color:#012E40; font-weight:bold; border:none; padding:10px 20px;
    border-radius:8px; cursor:pointer; margin-bottom:20px;
    display: flex; align-items: center; gap: 8px;
}
.btn-ia:hover { background:#e0c44f; }

/* Estilo do Chat IA */
#chatIA .input-chat {
    display: flex;
    border-top: 1px solid #eee;
    background: white;
}

#chatIA .input-chat input {
    flex: 1;
    border: none;
    padding: 12px;
    font-size: 14px;
}

#chatIA .input-chat button {
    border: none;
    padding: 0 15px;
    cursor: pointer;
    transition: all 0.2s;
}

#chatIA .input-chat button:hover {
    opacity: 0.8;
}

#chatIA .input-chat #sendMsg {
    background: #D4AF37;
    color: #012E40;
}

#chatIA .input-chat #voiceBtn {
    color: #012E40;
}

#chatIA .input-chat #voiceBtn.recording {
    color: #D9534F;
    animation: pulse 1.5s infinite;
}

#chatIA .messages {
    padding: 15px;
    height: 300px;
    overflow-y: auto;
}

#chatIA .messages div {
    margin-bottom: 10px;
    line-height: 1.4;
}

#chatIA .messages div.user-message {
    text-align: right;
    color: #012E40;
}

#chatIA .messages div.ai-message {
    text-align: left;
    color: #555;
}

#chatIA .messages div.system-message {
    text-align: center;
    color: #888;
    font-style: italic;
    font-size: 0.9em;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.alert-low-stock {
    background:#ffe5e0; border-left:5px solid #D9534F; padding:15px; border-radius:8px; margin-top:20px;
}
.alert-low-stock h5 { color:#D9534F; font-weight:bold; margin-bottom:10px; }

.charts { display:flex; flex-wrap:wrap; gap:20px; }
.chart-card {
    flex:1; min-width:300px; background:white; padding:20px; border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.1);
}

.card-pedidos {
    background:#fff3cd;
    border-left:5px solid #ffb700;
    padding:20px;
    border-radius:10px;
    margin-bottom:20px;
    color:#856404;
    font-weight:bold;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

/* CHAT IA */
#chatIA {
    position:fixed; bottom:20px; right:20px; width:300px; max-height:400px;
    background:white; border-radius:12px; box-shadow:0 3px 15px rgba(0,0,0,0.2);
    display:none; flex-direction:column; overflow:hidden; z-index:1000;
}
#chatIA header { background:#012E40; color:white; padding:10px; text-align:center; }
#chatIA .messages { flex:1; padding:10px; overflow-y:auto; }
#chatIA .input-chat { display:flex; border-top:1px solid #ccc; }
#chatIA .input-chat input { flex:1; border:none; padding:10px; }
#chatIA .input-chat button { border:none; background:#D4AF37; padding:10px 15px; cursor:pointer; }
#chatIA .input-chat button:hover { background:#e0c44f; }

/* YASMIN Vendor Widget */
:root { --petroleo: #012E40; --dourado: #D4AF37; }
#yasminWidget { position:fixed; right:30px; bottom:30px; width:360px; background:#fff; border-radius:12px; box-shadow:0 12px 40px rgba(1,46,64,0.15); display:flex; flex-direction:column; z-index:9999; overflow:hidden; opacity:0; visibility:hidden; transform:translateY(20px); transition:all 0.3s ease-out; pointer-events:none; }
#yasminWidget.active { opacity:1; visibility:visible; transform:translateY(0); pointer-events:auto; animation:slideUpIn 0.3s ease-out; }
@keyframes slideUpIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
.yasmin-header { background:linear-gradient(135deg, var(--petroleo), #0d5c7a); color:var(--dourado); padding:14px; display:flex; align-items:center; justify-content:space-between; }
.yasmin-title { font-weight:700; font-size:1rem; display:flex; align-items:center; gap:8px; }
.yasmin-body { padding:16px; max-height:300px; overflow-y:auto; background:#f9fafb; }
.yasmin-message { padding:10px 12px; border-radius:8px; margin-bottom:10px; font-size:0.95rem; line-height:1.4; }
.yasmin-message.yasmin-bot { background:#e8f0f6; color:#1f2937; border-left:3px solid var(--petroleo); }
.yasmin-message.yasmin-user { background:var(--dourado); color:var(--petroleo); margin-left:20px; text-align:right; }
.yasmin-footer { padding:12px; border-top:1px solid #e5e7eb; background:#fff; }
.yasmin-footer .input-group-sm .form-control { font-size:0.9rem; }
.yasmin-footer button { font-size:0.9rem; }
#botaoYasmin { position:fixed; right:30px; bottom:30px; width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg, var(--petroleo), #0d5c7a); color:var(--dourado); border:none; z-index:9998; box-shadow:0 8px 24px rgba(1,46,64,0.2); cursor:pointer; font-size:24px; transition:transform 0.2s, box-shadow 0.2s; }
#botaoYasmin:hover { transform:scale(1.1); box-shadow:0 12px 32px rgba(1,46,64,0.3); }
</style>
</head>
<body>

<?php include 'includes/menu_vendedor.php'; ?>

<div class="main">
    <h2 style="color:#012E40; margin-bottom:20px;">Dashboard</h2>

    <button class="btn-ia" id="btnIA"><i class="fa-solid fa-robot"></i> Assistente IA</button>

    <!-- CARD DE NOVOS PEDIDOS -->
    <?php if($novosPedidos > 0): ?>
        <div class="card-pedidos" id="cardPedidos">
            <span><i class="fa-solid fa-bell"></i> Existem <?= $novosPedidos ?> novo(s) pedido(s) pendente(s)</span>
            <div>
                <button onclick="atenderPedidos()" style="background:#012E40;color:white;border:none;padding:8px 12px;border-radius:5px;cursor:pointer;">Atender</button>
                <button onclick="ignorarPedidos()" style="background:#D9534F;color:white;border:none;padding:8px 12px;border-radius:5px;cursor:pointer;">Ignorar</button>
            </div>
        </div>
    <?php endif; ?>

    <!-- CARDS ESTATÍSTICOS -->
    <div class="cards">
        <div class="card-stat"><i class="fa-solid fa-box fa-2x" style="color:#D4AF37;"></i><h3><?= $totalProdutos ?></h3><p>Produtos</p></div>
        <div class="card-stat"><i class="fa-solid fa-layer-group fa-2x" style="color:#D4AF37;"></i><h3><?= $quantidadeEstoque ?></h3><p>Em Estoque</p></div>
        <div class="card-stat"><i class="fa-solid fa-cart-shopping fa-2x" style="color:#D4AF37;"></i><h3><?= $totalVendas ?></h3><p>Vendas</p></div>
        <div class="card-stat"><i class="fa-solid fa-users fa-2x" style="color:#D4AF37;"></i><h3><?= $totalClientes ?></h3><p>Clientes</p></div>
    </div>

    <!-- GRÁFICOS -->
    <div class="charts">
        <div class="chart-card">
            <h5>Produtos Mais Vendidos</h5>
            <canvas id="graficoPizza"></canvas>
        </div>
        <div class="chart-card">
            <h5>Produtos com Menor Estoque</h5>
            <canvas id="graficoBarra"></canvas>
        </div>
    </div>

    <!-- ALERTA DE ESTOQUE -->
    <?php if(!empty($produtosAlerta)): ?>
    <div class="alert-low-stock">
        <h5>Produtos com Estoque Crítico</h5>
        <ul>
            <?php foreach($produtosAlerta as $p): ?>
                <li><?= safe($p['nome']) ?> - <?= intval($p['quantidade']) ?> unidades</li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<!-- CHAT IA -->
<div id="chatIA">
    <header>
        <span>Assistente IA</span>
        <button id="closeIA" style="background:none;border:none;color:white;float:right;cursor:pointer">
            <i class="fa-solid fa-times"></i>
        </button>
    </header>
    <div class="messages"></div>
    <div class="input-chat">
        <button id="voiceBtn" style="background:none;border:none;padding:10px;cursor:pointer">
            <i class="fa-solid fa-microphone"></i>
        </button>
        <input type="text" id="msgInput" placeholder="Escreva uma mensagem...">
        <button id="sendMsg"><i class="fa-solid fa-paper-plane"></i></button>
    </div>
</div>

<script>
// Base URL para chamadas AJAX (garante que funciona em subpastas)
const BASE_URL = '<?php echo rtrim(BASE_URL, "/"); ?>';

// CHAT IA
const btnIA = document.getElementById('btnIA');
const chatIA = document.getElementById('chatIA');
// Hide Global Button if exists
const globalBtn = document.getElementById('globalIaBtn');
if(globalBtn) globalBtn.style.display = 'none';

const msgInput = document.getElementById('msgInput');
const sendMsg = document.getElementById('sendMsg');
const messagesDiv = chatIA.querySelector('.messages');
const voiceBtn = document.getElementById('voiceBtn');
const closeIA = document.getElementById('closeIA');

// Inicializa reconhecimento de voz se disponível
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
let recognition = null;
if (SpeechRecognition) {
    recognition = new SpeechRecognition();
    recognition.lang = 'pt-PT';
    recognition.continuous = false;
    recognition.interimResults = false;

    recognition.onstart = () => {
        addMessage('Ouvindo...', 'system');
    };

    recognition.onresult = (event) => {
        const text = event.results[0][0].transcript;
        msgInput.value = text;
        // Remover a mensagem "Ouvindo..."
        const messages = messagesDiv.children;
        if (messages.length > 0 && messages[messages.length - 1].classList.contains('system-message')) {
            messages[messages.length - 1].remove();
        }
        sendMessage();
    };

    recognition.onerror = (event) => {
        console.error('Erro no reconhecimento de voz:', event.error);
        voiceBtn.classList.remove('recording');
        // Remover a mensagem "Ouvindo..."
        const messages = messagesDiv.children;
        if (messages.length > 0 && messages[messages.length - 1].classList.contains('system-message')) {
            messages[messages.length - 1].remove();
        }
        addMessage('Não foi possível reconhecer o áudio. Tente novamente.', 'system');
    };

    recognition.onend = () => {
        voiceBtn.classList.remove('recording');
        const messages = messagesDiv.children;
        if (messages.length > 0 && messages[messages.length - 1].classList.contains('system-message') && 
            messages[messages.length - 1].textContent === 'Ouvindo...') {
            messages[messages.length - 1].remove();
        }
    };
} else {
    voiceBtn.style.display = 'none';
    console.log('Reconhecimento de voz não suportado neste navegador.');
}

// Função para sintetizar voz
function speak(text) {
    // A função real de fala é definida mais abaixo para suportar desbloqueio por interação do utilizador.
    if (typeof window.__yasminSpeak === 'function') {
        window.__yasminSpeak(text);
        return;
    }
    // Fallback simples caso a função ainda não exista
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'pt-PT';
        speechSynthesis.cancel();
        speechSynthesis.speak(utterance);
    }
}

// --- Preparar desbloqueio de síntese de voz por gesto do utilizador ---
if (typeof window.__yasminSpeak !== 'function') {
    (function(){
        let unlocked = false;
        const pending = [];

        function realSpeak(text) {
            if (!('speechSynthesis' in window)) return;
            const u = new SpeechSynthesisUtterance(text);
            u.lang = 'pt-PT';
            try { window.speechSynthesis.cancel(); } catch(e){}
            try { window.speechSynthesis.speak(u); } catch(e){ console.warn('speak error', e); }
        }

        function flushPending(){
            while(pending.length){
                const t = pending.shift();
                // pequeno atraso entre falas
                setTimeout(() => realSpeak(t), 250);
            }
        }

        function unlock() {
            if (unlocked) return;
            unlocked = true;
            if ('speechSynthesis' in window) {
                try { window.speechSynthesis.getVoices(); } catch(e){}
                // tenta lançar uma utterance silenciosa para desbloquear
                try {
                    const s = new SpeechSynthesisUtterance('');
                    s.volume = 0;
                    window.speechSynthesis.speak(s);
                } catch(e){}
            }
            flushPending();
            document.removeEventListener('click', unlock);
        }

        // expõe função global usada acima
        window.__yasminSpeak = function(text){
            if (unlocked) return realSpeak(text);
            pending.push(text);
        };

        // desbloqueia à primeira interação do utilizador
        document.addEventListener('click', unlock, { once: true });
        // também desbloqueia por toque (mobile)
        document.addEventListener('touchstart', unlock, { once: true });
    })();
}

// Função para adicionar mensagem no chat
function addMessage(text, type = 'ai') {
    const div = document.createElement('div');
    div.className = type === 'user' ? 'user-message' : 
                   type === 'system' ? 'system-message' : 
                   'ai-message';
    div.innerHTML = `<strong>${type === 'user' ? 'Você' : 
                            type === 'system' ? 'Sistema' : 
                            'IA'}:</strong> ${text}`;
    messagesDiv.appendChild(div);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

// Função para enviar mensagem para a IA
async function sendMessage() {
    const text = msgInput.value.trim();
    if (!text) return;

    addMessage(text, true);
    msgInput.value = '';

    try {
        const response = await fetch((typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/app/ia/responder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                mensagem: text,
                pagina: 'dashboard_vendedor'
            })
        });

        const data = await response.json();
        if (data.texto) {
            addMessage(data.texto);
            if (data.audio) speak(data.texto);
        } else {
            addMessage('Desculpe, não consegui processar sua mensagem.');
        }
    } catch (error) {
        console.error('Erro ao enviar mensagem:', error);
        addMessage('Desculpe, ocorreu um erro ao processar sua mensagem.');
    }
}

// Event listeners
btnIA.addEventListener('click', () => {
    chatIA.style.display = 'flex';
    if (messagesDiv.children.length === 0) {
        // Mensagem de boas-vindas ao abrir o chat pela primeira vez
        setTimeout(() => {
            addMessage('Olá! Sou a assistente do dashboard. Posso ajudar com:\n- Informações sobre vendas e estoque\n- Alertas e notificações\n- Relatórios e análises\n- Gestão de produtos');
            speak('Olá! Sou a assistente do dashboard. Como posso ajudar?');
        }, 500);
    }
});

closeIA.addEventListener('click', () => {
    chatIA.style.display = 'none';
    if (recognition) {
        recognition.abort();
        voiceBtn.classList.remove('recording');
    }
});

// Botão de voz
if (voiceBtn) {
    let isRecording = false;
    
    voiceBtn.addEventListener('click', () => {
        if (!recognition) return;
        
        if (!isRecording) {
            recognition.start();
            voiceBtn.classList.add('recording');
            isRecording = true;
        } else {
            recognition.stop();
            voiceBtn.classList.remove('recording');
            isRecording = false;
        }
    });
}

sendMsg.addEventListener('click', sendMessage);
msgInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') sendMessage();
});

// ALERTA AUTOMÁTICO DA IA SOBRE PEDIDOS E ESTOQUE
<?php 
$msgsIA = [];
if($novosPedidos > 0) {
    $msgsIA[] = "Existem $novosPedidos novos pedidos pendentes aguardando atendimento.";
}
if(!empty($produtosAlerta)) {
    $qtdBaixo = count($produtosAlerta);
    $msgsIA[] = "Atenção: $qtdBaixo produtos estão com estoque crítico.";
    // Opcional: listar o primeiro para dar exemplo
    $primeiro = $produtosAlerta[0];
    $msgsIA[] = "Por exemplo, {$primeiro['nome']} tem apenas {$primeiro['quantidade']} unidades.";
}

if(!empty($msgsIA)): 
    $fullMsg = implode(' ', $msgsIA);
?>
setTimeout(() => {
    const msg = `<?= $fullMsg ?>`;
    addMessage(msg);
    speak(msg);
    chatIA.style.display = 'flex';
}, 500);
<?php endif; ?>

// Funções Atender/Ignorar Pedido
function atenderPedidos(){
    fetch('processar_pedidos.php?action=atender').then(()=>{ document.getElementById('cardPedidos').remove(); });
}
function ignorarPedidos(){
    fetch('processar_pedidos.php?action=ignorar').then(()=>{ document.getElementById('cardPedidos').remove(); });
}

// Gráficos
const labelsPizza = <?= json_encode(array_column($produtosMaisVendidos,'nome')) ?>;
const dataPizza = <?= json_encode(array_column($produtosMaisVendidos,'total_vendido')) ?>;
const labelsBarra = <?= json_encode(array_column($produtosMenorEstoque,'nome')) ?>;
const dataBarra = <?= json_encode(array_column($produtosMenorEstoque,'quantidade')) ?>;

new Chart(document.getElementById('graficoPizza'), {
    type:'pie',
    data:{ labels:labelsPizza, datasets:[{ data:dataPizza, backgroundColor:['#D4AF37','#012E40','#FFA500','#008080','#FF6347'] }] },
    options:{ responsive:true, plugins:{ legend:{ position:'bottom' } } }
});
new Chart(document.getElementById('graficoBarra'), {
    type:'bar',
    data:{ labels:labelsBarra, datasets:[{ label:'Quantidade em Estoque', data:dataBarra, backgroundColor:'#D4AF37' }] },
    options:{ responsive:true, scales:{ y:{ beginAtZero:true } }, plugins:{ legend:{ display:false } } }
});
</script>

<!-- YASMIN Vendor Widget -->
<div id="yasminWidget">
    <div class="yasmin-header">
        <div class="yasmin-title">
            <i class="fa-solid fa-sparkles"></i> YASMIN
        </div>
        <button class="btn btn-sm btn-light" id="closeYasmin" title="Fechar"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="yasmin-body" id="yasminBody">
        <div class="yasmin-message yasmin-bot">
            Olá! Sou a YASMIN, tua assistente.<br/>
            Estou aqui para ajudarte com pedidos, clientes, vendas e estoque!
        </div>
    </div>
    <div class="yasmin-footer">
        <div class="input-group input-group-sm">
            <button id="yasminMic" class="btn btn-outline-secondary" title="Falar" type="button" style="min-width:42px;"><i class="fa-solid fa-microphone"></i></button>
            <input id="yasminInput" class="form-control" placeholder="Ex: 'Quantos pedidos?' ou 'Estoque?'..." />
            <button id="yasminSend" class="btn btn-success" title="Enviar"><i class="fa-solid fa-paper-plane"></i></button>
            <button id="yasminPlayToggle" class="btn btn-outline-primary" title="Ouvir respostas" type="button" style="min-width:42px; margin-left:6px;"><i class="fa-solid fa-volume-high"></i></button>
        </div>
    </div>
</div>

<button id="botaoYasmin" title="Abrir assistente YASMIN" 
    style="position:fixed; right:30px; bottom:30px; width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg, var(--petroleo), #0d5c7a); color:var(--dourado); border:none; z-index:9998; box-shadow:0 8px 24px rgba(1,46,64,0.2); cursor:pointer; font-size:24px; transition:transform 0.2s, box-shadow 0.2s;">
    <i class="fa-solid fa-wand-magic-sparkles"></i>
</button>

<script>
(() => {
    // YASMIN Vendor Mode
    const BASE_URL = '<?php echo rtrim(BASE_URL, "/"); ?>';
    const yasminBtn = document.getElementById('botaoYasmin');
    const yasminWidget = document.getElementById('yasminWidget');
    const yasminBody = document.getElementById('yasminBody');
    const yasminInput = document.getElementById('yasminInput');
    const yasminSend = document.getElementById('yasminSend');
    const closeYasminBtn = document.getElementById('closeYasmin');
    
    let yasminAtivo = false;
    let currentAudio = null;
    let isAudioPlaying = false;
    
    function abrirYasmin() {
        console.log('[YASMIN Vendor] Abrindo widget...');
        yasminWidget.classList.add('active');
        yasminAtivo = true;
        yasminInput.focus();
        yasminBtn.classList.add('hidden');
    }
    
    function fecharYasmin() {
        try { yasminInput.blur(); } catch (e) {}
        yasminWidget.classList.remove('active');
        yasminAtivo = false;
        yasminBtn.classList.remove('hidden');
    }
    
    function addYasminMessage(texto, isUser = false) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `yasmin-message ${isUser ? 'yasmin-user' : 'yasmin-bot'}`;
        msgDiv.innerHTML = texto;
        yasminBody.appendChild(msgDiv);
        yasminBody.scrollTop = yasminBody.scrollHeight;
    }
    
    async function enviarYasminMessage() {
        const msg = yasminInput.value.trim();
        if (!msg) {
            console.log('[YASMIN Vendor] Mensagem vazia');
            return;
        }
        
        console.log('[YASMIN Vendor] Enviando mensagem:', msg);
        addYasminMessage(msg, true);
        yasminInput.value = '';
        yasminInput.focus();
        
        const typingDiv = document.createElement('div');
        typingDiv.className = 'yasmin-message yasmin-bot';
        typingDiv.innerHTML = '<em>YASMIN está a escrever...</em>';
        yasminBody.appendChild(typingDiv);
        
        try {
            // Carrega contexto do vendor (dados da BD já no PHP)
            const vendorContext = {
                totalPedidos: <?= (int)$totalPedidos ?>,
                pedidosPendentes: <?= (int)$novosPedidos ?>,
                totalClientes: <?= (int)$totalClientes ?>,
                quantidadeEstoque: <?= (int)$quantidadeEstoque ?>,
                produtosAlerta: <?= json_encode($produtosAlerta) ?>,
                produtosMaisVendidos: <?= json_encode($produtosMaisVendidos) ?>
            };
            console.log('[YASMIN Vendor] Contexto carregado:', vendorContext);
            
            // POST para endpoint vendor
            let resp = await fetch(BASE_URL + '/app/api/yasmin_vendor_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json; charset=utf-8' },
                body: JSON.stringify({ mensagem: msg, audio: true, vendor_context: vendorContext })
            });
            
            typingDiv.remove();
            
            let textResp = await resp.text();
            console.log('[YASMIN Vendor] Resposta bruta:', textResp.substring(0, 200));
            
            if (!resp.ok) {
                console.warn('[YASMIN Vendor] JSON POST failed, attempting urlencoded fallback');
                try {
                    const form = new URLSearchParams();
                    form.append('mensagem', msg);
                    form.append('audio', '1');
                    form.append('vendor_context', JSON.stringify(vendorContext));
                    const resp2 = await fetch(BASE_URL + '/app/api/yasmin_vendor_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8' },
                        body: form.toString()
                    });
                    textResp = await resp2.text();
                    if (!resp2.ok) {
                        addYasminMessage(`Erro HTTP ${resp2.status}`, false);
                        return;
                    }
                    resp = resp2;
                } catch (e2) {
                    addYasminMessage(`Erro de comunicação: ${e2.message}`, false);
                    return;
                }
            }
            
            let data;
            try {
                data = JSON.parse(textResp);
            } catch (e) {
                addYasminMessage(`Erro ao processar resposta: ${e.message}`, false);
                console.error('[YASMIN Vendor] JSON Parse error:', e);
                return;
            }
            
            if (data.success) {
                let resposta = data.mensagem || 'Desculpa, não consegui processar.';
                addYasminMessage(resposta, false);
                
                // Reproduz áudio se disponível
                if (data.audio_base64) {
                    try {
                        const audioBytes = atob(data.audio_base64);
                        const len = audioBytes.length;
                        const bytes = new Uint8Array(len);
                        for (let i = 0; i < len; i++) bytes[i] = audioBytes.charCodeAt(i);
                        const mimeType = data.audio_mime || 'audio/mpeg';
                        const blob = new Blob([bytes], { type: mimeType });
                        const url = URL.createObjectURL(blob);
                        const audio = new Audio(url);
                        
                        currentAudio = audio;
                        isAudioPlaying = false;
                        
                        audio.addEventListener('play', () => {
                            isAudioPlaying = true;
                            document.getElementById('yasminPlayToggle').style.background = 'var(--petroleo)';
                            document.getElementById('yasminPlayToggle').style.color = 'var(--dourado)';
                        });
                        
                        audio.addEventListener('pause', () => {
                            isAudioPlaying = false;
                            document.getElementById('yasminPlayToggle').style.background = '';
                            document.getElementById('yasminPlayToggle').style.color = '';
                        });
                        
                        audio.addEventListener('canplay', () => {
                            audio.play().catch(e => console.warn('[YASMIN Audio] Autoplay bloqueado:', e.message));
                        });
                        
                        setTimeout(() => {
                            if (audio.paused && currentAudio === audio) {
                                audio.play().catch(e => console.warn('[YASMIN Audio] Erro:', e.message));
                            }
                        }, 500);
                        
                        console.log('[YASMIN Audio] Áudio pronto:', mimeType);
                    } catch (e) {
                        console.warn('[YASMIN Audio] Erro:', e);
                    }
                }
            } else {
                const errorMsg = data.error || 'Falha desconhecida';
                console.error('[YASMIN Vendor] Error:', errorMsg);
                addYasminMessage(`Erro: ${errorMsg}`, false);
            }
        } catch (err) {
            typingDiv.remove();
            console.error('[YASMIN Vendor] Erro:', err.message);
            addYasminMessage('Erro de comunicação. Verifica a consola (F12).', false);
        }
    }
    
    // Event listeners
    yasminBtn.addEventListener('click', abrirYasmin);
    closeYasminBtn.addEventListener('click', fecharYasmin);
    yasminSend.addEventListener('click', enviarYasminMessage);
    yasminInput.addEventListener('keypress', e => {
        if (e.key === 'Enter') enviarYasminMessage();
    });
    
    // Web Speech API
    let recognition = null;
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SR();
        recognition.lang = 'pt-PT';
        recognition.interimResults = false;
        
        recognition.addEventListener('result', (e) => {
            const text = e.results[0][0].transcript;
            yasminInput.value = text;
            yasminInput.focus();
        });
        
        recognition.addEventListener('error', (e) => {
            console.warn('[YASMIN Mic]', e.error);
            document.getElementById('yasminMic').style.opacity = '0.6';
            setTimeout(() => { document.getElementById('yasminMic').style.opacity = '1'; }, 500);
        });
    } else {
        document.getElementById('yasminMic').style.display = 'none';
    }
    
    document.getElementById('yasminMic').addEventListener('click', () => {
        if (!recognition) return;
        try {
            document.getElementById('yasminMic').style.opacity = '0.5';
            recognition.start();
        } catch (e) {
            console.warn('Speech recognition error', e);
            document.getElementById('yasminMic').style.opacity = '1';
        }
    });
    
    // Audio play toggle
    document.getElementById('yasminPlayToggle').addEventListener('click', () => {
        if (!currentAudio) {
            console.log('[YASMIN] Sem áudio');
            return;
        }
        if (isAudioPlaying) {
            currentAudio.pause();
        } else {
            currentAudio.play().catch(e => console.error('[YASMIN] Erro:', e));
        }
    });
    
    // Hover effect
    yasminBtn.addEventListener('mouseenter', function() { this.style.transform = 'scale(1.1)'; });
    yasminBtn.addEventListener('mouseleave', function() { this.style.transform = 'scale(1)'; });
    
})();
</script>
<!-- JavaScript Responsivo Global -->
<script src="../../assets/js/responsive.js"></script>

</body>
</html>
