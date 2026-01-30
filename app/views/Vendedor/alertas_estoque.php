<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Processar atualização de estoque via AJAX/POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'repor_estoque') {
    $id = intval($_POST['id']);
    $qtdAdicionar = intval($_POST['qtd_adicionar']);

    if ($id > 0 && $qtdAdicionar > 0) {
        $stmt = $conn->prepare("UPDATE produto SET quantidade = quantidade + :qtd WHERE id = :id");
        $exec = $stmt->execute([':qtd' => $qtdAdicionar, ':id' => $id]);

        if (isset($_POST['is_ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => $exec, 'message' => $exec ? 'Estoque atualizado!' : 'Erro ao atualizar.']);
            exit;
        }
    }
    header('Location: alertas_estoque.php');
    exit;
}

include __DIR__ . '/includes/menu_vendedor.php';
// include __DIR__ . '/../../../includes/assistente_virtual.php'; // Removido para usar o widget Yasmin nativo

// Adiciona scripts necessários
echo '<script src="/assets/js/monitor-estoque.js"></script>';

function safe($str){ return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

// Busca produtos
$stmt = $conn->prepare("SELECT p.*, c.nome AS categoria_nome FROM produto p LEFT JOIN categoria c ON p.categoria_id = c.id ORDER BY p.id DESC");
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$lowThreshold = 5; // mesmo limiar usado em produtos


// (listagem completa — itens críticos serão destacados quando necessário)
?>

<div class="main container-fluid">
    <div class="page-header mb-4">
        <div>
            <h2 style="color:var(--petroleo); margin:0;">Alertas de Estoque</h2>
            <p class="text-muted">Lista completa de produtos com suas quantidades — itens críticos estão destacados.</p>
        </div>
    </div>

    <div class="alerts-list">
        <?php if (empty($produtos)): ?>
            <div class="alert alert-light">Nenhum produto encontrado.</div>
        <?php else: ?>
            <?php foreach ($produtos as $li):
                $isLow = intval($li['quantidade']) <= $lowThreshold;
            ?>
                <div class="alert-item d-flex align-items-center mb-3 p-3 <?php echo $isLow ? 'alert-low-stock' : ''; ?>" data-prod-id="<?php echo $li['id']; ?>">
                    <img src="<?php echo safe(!empty($li['imagem']) ? '/'.$li['imagem'] : 'https://via.placeholder.com/100x80?text=Sem+Img'); ?>" alt="" class="item-thumb">
                    <div style="flex:1;">
                        <div class="alert-content-row">
                            <div>
                                <strong class="item-name"><?php echo safe($li['nome']); ?></strong>
                                <div class="text-muted small"><?php echo safe($li['categoria_nome']); ?></div>
                            </div>
                            <div class="alert-meta-col">
                                <?php if ($isLow): ?>
                                    <div class="badge badge-critical">Crítico</div>
                                <?php endif; ?>
                                <div class="qty-display" style="font-weight:700; font-size:1.1rem; margin-top:6px;">Qtd: <span class="alert-qty"><?php echo intval($li['quantidade']); ?></span></div>
                                <?php if (!$isLow): ?>
                                    <div class="small text-muted">Em estoque</div>
                                <?php else: ?>
                                    <div class="small text-muted">Limiar: <?php echo $lowThreshold; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mt-2">
                            <?php // Botão rápido para repor estoque ?>
                            <button type="button" class="btn btn-repor" onclick="abrirModalRepor(<?php echo $li['id']; ?>, '<?php echo safe($li['nome']); ?>', <?php echo intval($li['quantidade']); ?>)">
                                <i class="fas fa-boxes-packing"></i> Repor Estoque
                            </button>
                            <?php if (intval($li['quantidade']) <= 0): ?>
                                <span class="ms-2 badge bg-danger">Esgotado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Repor Estoque (Custom Pure CSS) -->
<div id="modalRepor" class="custom-modal">
    <div class="custom-modal-content">
        <div class="modal-header-premium">
            <h5 style="color: #fff; margin:0; font-size:1.2rem;">
                <i class="fa-solid fa-boxes-stacked me-2"></i> Repor Estoque
            </h5>
            <button type="button" class="btn-close-custom" onclick="fecharModalRepor()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body-custom">
            <form id="formRepor" method="post">
                <input type="hidden" name="acao" value="repor_estoque">
                <input type="hidden" name="id" id="repor-id">
                <input type="hidden" name="is_ajax" value="1">
                
                <p style="color:#64748b; margin-bottom:15px;">
                    Adicionar unidades ao produto: <br>
                    <strong id="repor-nome" style="color:var(--petroleo); font-size:1.1rem;"></strong>
                </p>

                <div class="mb-4">
                    <label class="form-label text-muted small text-uppercase fw-bold">Quantidade a Adicionar</label>
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary" onclick="ajustarQtd(-1)">-</button>
                        <input type="number" class="form-control text-center fs-5 fw-bold" name="qtd_adicionar" id="repor-qtd" value="1" min="1" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="ajustarQtd(1)">+</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-repor w-100 py-3" style="justify-content:center; font-size:1.1rem;">
                    Confirmar Reposição
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.main { 
    margin-left: 270px; 
    padding: 30px; 
    transition: 0.3s;
}
.btn-repor {
    background: linear-gradient(135deg, #012E40 0%, #024b69 100%);
    color: #fff !important;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 5px rgba(1,46,64,0.2);
}
.btn-repor:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(1,46,64,0.3);
    background: linear-gradient(135deg, #024b69 0%, #012E40 100%);
}
.btn-repor i { font-size: 0.9em; }

/* Base Layout (Desktop) */
.alert-content-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}
.alert-meta-col {
    text-align: right;
    min-width: 120px;
}

/* Mobile Layout */
@media (max-width: 1024px) {
    .main {
        margin-left: 0 !important;
        padding: 80px 15px 15px !important;
    }
    .alert-item {
        flex-direction: column;
        align-items: flex-start !important;
    }
    .item-thumb {
        width: 100%;
        height: 150px;
        margin-right: 0;
        margin-bottom: 12px;
        object-fit: cover;
    }
    .alert-item > div { width: 100%; }
    
    /* Layout Vertical no Mobile/Tablet */
    .alert-content-row {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 8px;
    }
    
    /* Meta Info (Qtd/Badge) à esquerda */
    .alert-meta-col {
        text-align: left !important;
        min-width: 0 !important;
        margin-top: 5px;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .alert-item .text-muted.small {
        margin-top: 4px;
    }
    .qty-display {
        margin-top: 5px;
        text-align: left !important;
    }
    
    /* Botão full width */
    /* Botão full width */
    .btn.btn-repor { 
        width: auto; /* Melhor que 100% para evitar overflow com padding */
        display: block; 
        text-align: center;
        padding: 12px;
        margin-top: 10px;
    }
}

.alerts-list { display:flex; flex-direction:column; gap:12px; }
.alert-item { background:#fff; border-radius:12px; box-shadow:0 6px 18px rgba(1,46,64,0.06); }
.item-thumb { width:100px; height:70px; object-fit:cover; border-radius:8px; margin-right:16px; }
.alert-low-stock { background:#fff5f5; border-left:5px solid #D9534F; }
.badge-critical { display:inline-block; background:#D9534F; color:#fff; padding:6px 8px; border-radius:8px; font-weight:700; }
.item-name { color:var(--petroleo); }
.item-name { color:var(--petroleo); }
.qty-display { color:#012E40; }

/* Custom Modal CSS (Same as Products) */
.custom-modal {
    display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%;
    overflow: hidden; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px);
    align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease;
}
.custom-modal.open { display: flex; opacity: 1; }
.custom-modal-content {
    background-color: #fefefe; margin: auto; border-radius: 20px; width: 90%; max-width: 400px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); transform: scale(0.9);
    transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    display: flex; flex-direction: column;
}
.custom-modal.open .custom-modal-content { transform: scale(1); }
.modal-header-premium {
    background: linear-gradient(135deg, var(--petroleo) 0%, #034c6a 100%);
    padding: 15px 25px; display: flex; justify-content: space-between; align-items: center;
    border-radius: 20px 20px 0 0;
}
.modal-body-custom { padding: 25px 30px; }
.btn-close-custom {
    background: rgba(255,255,255,0.1); border: none; color: white; width: 30px; height: 30px;
    border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: 0.2s;
}
.btn-close-custom:hover { background: rgba(255,255,255,0.3); transform: rotate(90deg); }
</style>

<script>
// Polling para atualizar a lista dinamicamente a cada 10s
(function(){
    const lowThreshold = <?php echo json_encode($lowThreshold); ?>;
    const container = document.querySelector('.alerts-list');
    if (!container) return;

    function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]; }); }

    function buildList(items){
        if (!items.length) { container.innerHTML = '<div class="alert alert-light">Nenhum produto encontrado.</div>'; return; }
        container.innerHTML = items.map(function(p){
            const img = p.imagem ? (p.imagem.startsWith('/')? p.imagem : ('/'+p.imagem)) : 'https://via.placeholder.com/100x80?text=Sem+Img';
            const isLow = parseInt(p.quantidade,10) <= parseInt(lowThreshold,10);
            return `\
                <div class="alert-item d-flex align-items-center mb-3 p-3 ${isLow ? 'alert-low-stock' : ''}" data-prod-id="${p.id}">\
                    <img src="${img}" alt="" class="item-thumb">\
                    <div style="flex:1;">\
                        <div class="alert-content-row">\
                            <div>\
                                <strong class="item-name">${escapeHtml(p.nome)}</strong>\
                                <div class="text-muted small">${escapeHtml(p.categoria_nome||'')}</div>\
                            </div>\
                            <div class="alert-meta-col">\
                                ${isLow ? '<div class="badge badge-critical">Crítico</div>' : ''}\
                                <div class="qty-display" style="font-weight:700; font-size:1.1rem; margin-top:6px;">Qtd: <span class="alert-qty">${p.quantidade}</span></div>\
                                ${isLow ? '<div class="small text-muted">Limiar: ' + lowThreshold + '</div>' : '<div class="small text-muted">Em estoque</div>' }\
                            </div>\
                        </div>\
                        <div class="mt-2">\
                            <button type="button" class="btn btn-repor" onclick="abrirModalRepor(${p.id}, '${escapeHtml(p.nome).replace(/'/g, "\\'")}', ${p.quantidade})">\
                                <i class="fas fa-boxes-packing"></i> Repor Estoque\
                            </button>\
                            ${parseInt(p.quantidade,10) <= 0 ? '<span class="ms-2 badge bg-danger">Esgotado</span>' : '' }\
                        </div>\
                    </div>\
                </div>\
            `;
        }).join('');
    }

    function fetchAndUpdate(){
        fetch('produtos_vendedor.php?ajax=produtos')
            .then(r=>r.json())
            .then(function(data){
                // build full list; low-stock styling applied in buildList
                buildList(data);
            })
            .catch(console.error);
    }

    fetchAndUpdate();
    setInterval(fetchAndUpdate,10000);
    fetchAndUpdate();
    setInterval(fetchAndUpdate,10000);
})();

// Modal Repor Logic
const modalRepor = document.getElementById('modalRepor');
const formRepor = document.getElementById('formRepor');

function abrirModalRepor(id, nome) {
    document.getElementById('repor-id').value = id;
    document.getElementById('repor-nome').textContent = nome;
    document.getElementById('repor-qtd').value = 1;
    modalRepor.classList.add('open');
}

function fecharModalRepor() {
    modalRepor.classList.remove('open');
}

function ajustarQtd(delta) {
    const input = document.getElementById('repor-qtd');
    let v = parseInt(input.value) || 0;
    v += delta;
    if(v < 1) v = 1;
    input.value = v;
}

window.onclick = function(e) {
    if(e.target == modalRepor) fecharModalRepor();
}

formRepor.addEventListener('submit', async function(e){
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Salvando...';
    btn.disabled = true;

    try {
        const formData = new FormData(this);
        const resp = await fetch('alertas_estoque.php', {
            method: 'POST',
            body: formData
        });
        const res = await resp.json();
        if(res.success) {
            fecharModalRepor();
            // Force refresh of the list
            // (Note: The interval polling will pick it up, but we can trigger it manually if exposing fetchAndUpdate)
            // For now, simpler to just reload or wait for poll. The user sees it closed.
            // Let's reload to be instant visible feedback if polling is slow
             window.location.reload(); 
        } else {
            alert('Erro: ' + res.message);
        }
    } catch(err) {
        console.error(err);
        alert('Erro de conexão.');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

// Reconciliação: confirmação antes de enviar
document.addEventListener('DOMContentLoaded', function(){
    const btn = document.getElementById('btnRecon');
    if (!btn) return;
    btn.addEventListener('click', function(){
        if (!confirm('Tem certeza? Esta operação irá reduzir o estoque com base em vendas historicas e não pode ser desfeita.')) return;
        document.getElementById('reconForm').submit();
    });
});
</script>

<!-- YASMIN Vendor Widget -->
<style>
:root { --petroleo: #012E40; --dourado: #D4AF37; }
#yasminWidget { position:fixed; right:30px; bottom:30px; width:360px; background:#fff; border-radius:12px; box-shadow:0 12px 40px rgba(1,46,64,0.15); display:flex; flex-direction:column; z-index:9999; overflow:hidden; opacity:0; visibility:hidden; transform:translateY(20px); transition:all 0.3s ease-out; pointer-events:none; font-family:'Segoe UI', sans-serif; }
#yasminWidget.active { opacity:1; visibility:visible; transform:translateY(0); pointer-events:auto; animation:slideUpIn 0.3s ease-out; }
@keyframes slideUpIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
.yasmin-header { background:linear-gradient(135deg, var(--petroleo), #0d5c7a); color:var(--dourado); padding:14px; display:flex; align-items:center; justify-content:space-between; }
.yasmin-title { font-weight:700; font-size:1rem; display:flex; align-items:center; gap:8px; }
.yasmin-body { padding:16px; max-height:300px; overflow-y:auto; background:#f9fafb; min-height:200px; }
.yasmin-message { padding:10px 12px; border-radius:8px; margin-bottom:10px; font-size:0.95rem; line-height:1.4; }
.yasmin-message.yasmin-bot { background:#e8f0f6; color:#1f2937; border-left:3px solid var(--petroleo); }
.yasmin-message.yasmin-user { background:var(--dourado); color:var(--petroleo); margin-left:20px; text-align:right; }
.yasmin-footer { padding:12px; border-top:1px solid #e5e7eb; background:#fff; }
.yasmin-footer .input-group-sm { display:flex; gap:5px; }
.yasmin-footer .form-control { flex:1; padding:8px; border:1px solid #ccc; border-radius:4px; font-size:0.9rem; }
#botaoYasmin { position:fixed; right:30px; bottom:30px; width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg, var(--petroleo), #0d5c7a); color:var(--dourado); border:none; z-index:9998; box-shadow:0 8px 24px rgba(1,46,64,0.2); cursor:pointer; font-size:24px; transition:transform 0.2s, box-shadow 0.2s; display:flex; align-items:center; justify-content:center; }
#botaoYasmin:hover { transform:scale(1.1); box-shadow:0 12px 32px rgba(1,46,64,0.3); }
#botaoYasmin.hidden { display:none; }
#yasminMic.recording { color: red; animation: pulse 1s infinite; }
</style>

<div id="yasminWidget">
    <div class="yasmin-header">
        <div class="yasmin-title">
            <i class="fa-solid fa-sparkles"></i> YASMIN
        </div>
        <button class="btn btn-sm btn-light" id="closeYasmin" title="Fechar" style="background:transparent; border:none; color:white;"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="yasmin-body" id="yasminBody">
        <div class="yasmin-message yasmin-bot">
            Olá! Sou a YASMIN, tua assistente.<br/>
            Posso mostrar o teu estoque e recomendar reposições.
        </div>
    </div>
    <div class="yasmin-footer">
        <div class="input-group input-group-sm">
            <button id="yasminMic" class="btn btn-outline-secondary" title="Falar" type="button" style="min-width:42px;"><i class="fa-solid fa-microphone"></i></button>
            <input id="yasminInput" class="form-control" placeholder="Pergunte 'Qual estoque mais baixo?'..." />
            <button id="yasminSend" class="btn btn-success" title="Enviar" style="background:var(--petroleo); color:var(--dourado); border:none;"><i class="fa-solid fa-paper-plane"></i></button>
            <button id="yasminPlayToggle" class="btn btn-outline-primary" title="Ouvir respostas" type="button" style="min-width:42px; margin-left:6px;"><i class="fa-solid fa-volume-high"></i></button>
        </div>
    </div>
</div>

<button id="botaoYasmin" title="Abrir assistente YASMIN">
    <i class="fa-solid fa-wand-magic-sparkles"></i>
</button>

<script>
(() => {
    // Hide Global Button if exists (avoid duplicate)
    const globalBtn = document.getElementById('globalIaBtn');
    if(globalBtn) globalBtn.style.display = 'none';

    // YASMIN Vendor Logic for Order/Stock Page
    // Usando caminho relativo correto para API
    const apiUrl = '../../api/yasmin_vendor_api.php';
    
    const yasminBtn = document.getElementById('botaoYasmin');
    const yasminWidget = document.getElementById('yasminWidget');
    const yasminBody = document.getElementById('yasminBody');
    const yasminInput = document.getElementById('yasminInput');
    const yasminSend = document.getElementById('yasminSend');
    const closeYasminBtn = document.getElementById('closeYasmin');
    const yasminMic = document.getElementById('yasminMic');
    
    let yasminAtivo = false;
    let currentAudio = null;
    let isAudioPlaying = false;
    
    function abrirYasmin() {
        yasminWidget.classList.add('active');
        yasminAtivo = true;
        yasminInput.focus();
        yasminBtn.classList.add('hidden');
    }
    
    function fecharYasmin() {
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
        if (!msg) return;
        
        addYasminMessage(msg, true);
        yasminInput.value = '';
        yasminInput.focus();
        
        const typingDiv = document.createElement('div');
        typingDiv.className = 'yasmin-message yasmin-bot';
        typingDiv.innerHTML = '<em>YASMIN está a escrever...</em>';
        yasminBody.appendChild(typingDiv);
        yasminBody.scrollTop = yasminBody.scrollHeight;
        
        try {
            const vendorContext = { pagina: 'alertas_estoque' };
            
            let resp = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mensagem: msg, audio: true, vendor_context: vendorContext })
            });
            
            typingDiv.remove();
            
            if (!resp.ok) throw new Error('Erro na API');
            
            const data = await resp.json();
            
            if (data.success) {
                addYasminMessage(data.mensagem || 'Sem resposta.');
                
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
                        audio.play().catch(e => console.warn('Autoplay blocked', e));
                    } catch(e) { console.error(e); }
                }
            } else {
                addYasminMessage('Desculpa, não consegui entender.', false);
            }
        } catch (err) {
            typingDiv.remove();
            console.error(err);
            addYasminMessage('Erro de conexão com YASMIN.', false);
        }
    }
    
    yasminBtn.addEventListener('click', abrirYasmin);
    closeYasminBtn.addEventListener('click', fecharYasmin);
    yasminSend.addEventListener('click', enviarYasminMessage);
    yasminInput.addEventListener('keypress', e => { if(e.key==='Enter') enviarYasminMessage(); });
    
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new SR();
        recognition.lang = 'pt-PT';
        
        recognition.onstart = () => yasminMic.classList.add('recording');
        recognition.onend = () => yasminMic.classList.remove('recording');
        recognition.onresult = (event) => {
            yasminInput.value = event.results[0][0].transcript;
            enviarYasminMessage();
        };
        
        yasminMic.addEventListener('click', () => recognition.start());
    } else {
        yasminMic.style.display = 'none';
    }
})();
</script>
