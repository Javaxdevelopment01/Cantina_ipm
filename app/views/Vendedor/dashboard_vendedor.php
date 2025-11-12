<?php
session_start();
if(!isset($_SESSION['vendedor_id'])) {
    header('Location: login_vendedor.php');
    exit;
}
require_once __DIR__ . '/../../../config/database.php';

// Função de segurança
function safe($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// ======= CARDS PRINCIPAIS =======
$totalProdutos = $conn->query("SELECT COUNT(*) FROM produto")->fetchColumn();
$totalVendas = $conn->query("SELECT COUNT(*) FROM venda")->fetchColumn();
$totalClientes = $conn->query("SELECT COUNT(*) FROM cliente")->fetchColumn();
$quantidadeEstoque = $conn->query("SELECT SUM(quantidade) FROM produto")->fetchColumn();

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
    SELECT p.nome, SUM(pi.quantidade) AS total_vendido
    FROM pedido_itens pi
    JOIN produto p ON pi.id_produto = p.id
    JOIN pedido pd ON pi.id_pedido = pd.id
    WHERE pd.estado = 'atendido'
    GROUP BY pi.id_produto
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'Segoe UI', sans-serif; background:#f7f8fa; }

.main { margin-left:250px; padding:30px; transition:0.3s; }
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
// CHAT IA
const btnIA = document.getElementById('btnIA');
const chatIA = document.getElementById('chatIA');
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
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'pt-PT';
        speechSynthesis.cancel(); // Cancela fala anterior
        speechSynthesis.speak(utterance);
    }
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
        const response = await fetch('/app/ia/responder.php', {
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

// ALERTA AUTOMÁTICO DA IA SOBRE PEDIDOS
<?php if($novosPedidos > 0): ?>
setTimeout(() => {
    const msg = `Existem <?= $novosPedidos ?> novos pedidos pendentes aguardando atendimento.`;
    addMessage(msg);
    speak(msg);
    chatIA.style.display = 'flex';
}, 2000);
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
</body>
</html>
