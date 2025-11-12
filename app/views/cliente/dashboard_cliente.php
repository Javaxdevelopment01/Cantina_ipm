<?php
// dashboard_cliente.php - Versão limpa e funcional
// Requisitos: PHP + cURL. Define OPENAI_API_KEY via variável de ambiente:
//   export OPENAI_API_KEY="sk-..."
// Não coloques a chave no ficheiro em produção.

session_start();

// ---------------------------
// CONFIGURAÇÃO
// ---------------------------
define('BASE_URL', 'http://cantina-ipm'); // ajusta se necessário

// Obtém a chave da environment (mais seguro). Em dev, podes definir via servidor.


// INCLUDES (ajusta caminhos conforme a tua estrutura)
$produtoModel = __DIR__ . '/../../../app/Models/Produto.php';
$produtoController = __DIR__ . '/../../../app/controllers/ProdutoController.php';
if (file_exists($produtoModel)) require_once $produtoModel;
if (file_exists($produtoController)) require_once $produtoController;

// Instancia controller se existir
$controller = null;
if (class_exists('ProdutoController')) {
    $controller = new ProdutoController();
}

// ---------------------------
// HANDLERS AJAX / IA / CARRINHO
// Todos os handlers processam e fazem exit para evitar imprimir HTML depois
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Normaliza action (pode vir por application/x-www-form-urlencoded)
    $action = $_POST['action'] ?? null;

    // Se o request vier com JSON raw (ex: fetch JSON), tenta decodificar
    if (!$action) {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $j = json_decode($raw, true);
            if (is_array($j) && isset($j['action'])) $action = $j['action'];
            // Copiar message para $_POST quando for ia_chat via JSON
            if (is_array($j) && isset($j['message'])) $_POST['message'] = $j['message'];
        }
    }

    // ---------------------------
    // Handler: IA Chat (POST action=ia_chat)
    // Pode receber application/x-www-form-urlencoded (form) ou JSON { action:'ia_chat', message:'...' }
    // ---------------------------
    if ($action === 'ia_chat') {
        header('Content-Type: application/json; charset=utf-8');
        $message = trim((string)($_POST['message'] ?? ''));

        if ($message === '') {
            echo json_encode(['success' => false, 'error' => 'Mensagem vazia']);
            exit;
        }

        if (empty($OPENAI_API_KEY) || $OPENAI_API_KEY === 'COLOCA_A_TUA_CHAVE_AQUI') {
            echo json_encode(['success' => false, 'error' => 'Chave OpenAI não configurada no servidor.']);
            exit;
        }

        $payload = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'És a GIA, assistente de uma cantina universitária. Responde em Português de Angola, educado e prático.'],
                ['role' => 'user', 'content' => $message]
            ],
            'temperature' => 0.7,
            'max_tokens' => 600
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $OPENAI_API_KEY
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            echo json_encode(['success' => false, 'error' => 'Erro CURL: ' . $err]);
            exit;
        }

        $json = json_decode($resp, true);
        if (!is_array($json)) {
            echo json_encode(['success' => false, 'error' => 'Resposta inválida da OpenAI', 'raw' => $resp]);
            exit;
        }

        $reply = null;
        if (isset($json['choices'][0]['message']['content'])) {
            $reply = trim($json['choices'][0]['message']['content']);
        } elseif (isset($json['choices'][0]['text'])) {
            $reply = trim($json['choices'][0]['text']);
        }

        if ($reply === null) {
            echo json_encode(['success' => false, 'error' => 'Sem resposta da OpenAI', 'openai' => $json]);
            exit;
        }

        echo json_encode(['success' => true, 'reply' => $reply]);
        exit;
    }

    // ---------------------------
    // Handler: Carrinho (adicionar / atualizar / remover)
    // as chamadas client-side fazem fetch para sincronizar no servidor (opcional)
    // ---------------------------
    if (in_array($action, ['add','update','remove'])) {
        header('Content-Type: application/json; charset=utf-8');
        // Simples resposta OK (podes inserir lógica de persistência)
        echo json_encode(['success' => true, 'action' => $action]);
        exit;
    }

    // ---------------------------
    // Handler: Checkout (action=checkout)
    // Recebe payload (items, total) e methodData JSON
    // ---------------------------
    if ($action === 'checkout') {
        header('Content-Type: application/json; charset=utf-8');

        // Suporta tanto application/x-www-form-urlencoded (POST) como raw JSON
        $payloadRaw = $_POST['payload'] ?? null;
        $j = null;
        if (!$payloadRaw) {
            $raw = file_get_contents('php://input');
            $j = json_decode($raw, true);
            $payloadRaw = $j['payload'] ?? null;
        }

        $payload = json_decode($payloadRaw ?? '[]', true);
        $method = $_POST['method'] ?? ($j['method'] ?? 'mao');
        $methodDataRaw = $_POST['methodData'] ?? ($j['methodData'] ?? '{}');
        $methodData = json_decode($methodDataRaw ?? '{}', true);

        if (!$payload || !is_array($payload['items'] ?? null)) {
            echo json_encode(['success' => false, 'error' => 'Carrinho inválido']);
            exit;
        }

        // Valida e calcula total (server-side)
        $total = 0;
        foreach ($payload['items'] as $item) {
            $price = floatval($item['price'] ?? 0);
            $qty = intval($item['qty'] ?? 0);
            $total += $price * $qty;
        }

        // Tenta gravar no banco usando o PedidoController
        try {
            // Captura qualquer output/avisos para garantir que sempre devolvemos JSON válido
            ini_set('display_errors', '1');
            ob_start();
            require_once __DIR__ . '/../../controllers/pedidoController.php';
            $pedidoController = new PedidoController();

            // Se existir um cliente em sessão, usa-o; senão grava como NULL (guest)
            $id_cliente = $_SESSION['cliente_id'] ?? null;

            $dados = [
                'id_cliente' => $id_cliente,
                'forma_pagamento' => $method,
                'total' => $total,
                'items' => $payload['items'],
                'methodData' => $methodData
            ];

            $resultado = $pedidoController->criarPedido($dados);
            $buffer = ob_get_clean();

            if ($resultado['success']) {
                $resp = [ 'success' => true, 'orderId' => $resultado['pedido_id'] ];
                if (!empty($buffer)) $resp['debug'] = $buffer;
                echo json_encode($resp);
            } else {
                $resp = [ 'success' => false, 'error' => $resultado['error'] ?? 'Erro desconhecido ao criar pedido' ];
                if (!empty($buffer)) $resp['debug'] = $buffer;
                echo json_encode($resp);
            }
        } catch (Exception $e) {
            $buffer = ob_get_clean();
            $resp = [ 'success' => false, 'error' => 'Exceção: ' . $e->getMessage() ];
            if (!empty($buffer)) $resp['debug'] = $buffer;
            echo json_encode($resp);
        }

        exit;
    }
}
// ---------------------------
// FIM HANDLERS
// ---------------------------

// ---------------------------
// CARREGA PRODUTOS (apenas leitura para mostrar no frontend)
// ---------------------------
$produtos = [];
if ($controller && method_exists($controller, 'listarProdutos')) {
    try {
        $produtos = $controller->listarProdutos();
    } catch (Throwable $e) {
        $produtos = [];
    }
}

// ---------------------------
// HELPERS PHP
// ---------------------------
function safe($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
function publicImageUrl($imgField) {
    $imgField = trim((string)$imgField);
    if ($imgField === '') return 'https://via.placeholder.com/400x300?text=Sem+Imagem';
    if (preg_match('#^https?://#i', $imgField)) return $imgField;
    return rtrim(BASE_URL, '/') . '/' . ltrim($imgField, '/');
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <title>Cardápio - Cantina IPM</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
    :root { --petroleo:#012E40; --dourado:#D4AF37; --bg:#f7f8fa; }
    html,body{ height:100%; }
    body{ background:var(--bg); font-family: system-ui, "Segoe UI", Roboto, "Helvetica Neue", Arial; color:#222; margin:0; padding:0; }
    .container-cardapio{ padding:28px 16px; max-width:1200px; margin:0 auto;}
    h2.section-title{ color:var(--petroleo); font-weight:700; margin-bottom:4px; }
    .produto-card{ border-radius:12px; overflow:hidden; background:#fff; box-shadow:0 6px 18px rgba(1,46,64,0.06); transition:transform .18s; height:100%; display:flex; flex-direction:column; }
    .produto-card:hover{ transform:translateY(-6px); }
    .produto-card .thumb{ height:180px; background:#f5f5f5; display:flex; align-items:center; justify-content:center; overflow:hidden; }
    .produto-card .thumb img{ width:100%; height:100%; object-fit:cover; }
    .produto-card .card-body{ text-align:center; padding:12px; flex:1 1 auto; display:flex; flex-direction:column; justify-content:space-between; }
    .produto-card h5{ color:var(--petroleo); font-weight:700; margin-bottom:6px; font-size:1rem; }
    .produto-card p.desc{ color:#6b7280; font-size:.95rem; min-height:40px; margin-bottom:8px; }
    .price{ color:var(--dourado); font-weight:800; font-size:1.05rem; }
    .btn-add{ background:var(--petroleo); color:white; border:0; border-radius:8px; padding:6px 10px; }
    .btn-add:hover{ opacity:.95; transform:translateY(-2px); }
    #carrinhoBtn{ position:fixed; right:30px; bottom:120px; z-index:9999; background:var(--dourado); color:var(--petroleo); border-radius:50%; width:62px; height:62px; display:flex; align-items:center; justify-content:center; box-shadow:0 8px 24px rgba(1,46,64,0.12); border:none; }
    #carrinhoBadge{ position:absolute; top:-6px; right:-6px; background:#dc3545; color:#fff; width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; }
    #iaAssistente{ position:fixed; right:30px; bottom:30px; width:340px; background:#fff; border-radius:10px; box-shadow:0 12px 30px rgba(0,0,0,.12); display:none; z-index:9999; overflow:hidden; }
    #iaAssistente .header{ background:var(--petroleo); color:var(--dourado); padding:10px 12px; display:flex; align-items:center; justify-content:space-between; }
    #iaAssistente .body{ padding:12px; max-height:240px; overflow:auto; background:#fff; }
    #iaAssistente .footer{ padding:8px; border-top:1px solid #eee; background:#fff; }
    .modal-header.bg-accent{ background:var(--petroleo); color:var(--dourado); border-bottom:0; }
    .payment-method{ cursor:pointer; border-radius:8px; padding:10px; border:1px solid #eee; text-align:center; user-select:none; }
    .payment-method.active{ box-shadow:0 6px 18px rgba(1,46,64,0.06); border-color:var(--petroleo); }
    @media (max-width:480px){
        #iaAssistente{ right:12px; left:12px; width:auto; bottom:12px; }
        #carrinhoBtn{ right:12px; bottom:86px; }
    }
    </style>
</head>
<body>

<div class="container-cardapio">
    <div class="text-center mb-4">
        <h2 class="section-title">🍽️ Cardápio Digital - Cantina IPM</h2>
        <p class="text-muted">Escolhe os teus produtos. Adiciona ao carrinho e finaliza com facilidade.</p>
    </div>

    <div class="row g-3">
        <?php if (!empty($produtos)): ?>
        <?php foreach ($produtos as $produto):
            // Se produto estiver esgotado não exibir para o cliente
            if (intval($produto['quantidade'] ?? 0) <= 0) continue;
            $publicImg = publicImageUrl($produto['imagem'] ?? '');
                $nome = safe($produto['nome'] ?? 'Produto sem nome');
                $descricao = safe($produto['descricao'] ?? 'Sem descrição disponível');
                $preco = number_format(floatval($produto['preco'] ?? 0), 2, ',', '.');
                $id = intval($produto['id'] ?? 0);
            ?>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex">
                <div class="produto-card card h-100 border-0">
                    <div class="thumb">
                        <img src="<?php echo safe($publicImg); ?>" alt="<?php echo $nome; ?>">
                    </div>
                    <div class="card-body">
                        <div>
                            <h5><?php echo $nome; ?></h5>
                            <p class="desc"><?php echo $descricao; ?></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="price">Kz <?php echo $preco; ?></div>
                            <button class="btn btn-add btn-sm btn-adicionar"
                                data-id="<?php echo $id; ?>"
                                data-name="<?php echo safe($nome); ?>"
                                data-price="<?php echo floatval($produto['preco'] ?? 0); ?>"
                                data-img="<?php echo safe($publicImg); ?>">
                                <i class="fa-solid fa-cart-plus me-1"></i> Adicionar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12"><div class="alert alert-light text-center">Nenhum produto disponível no momento.</div></div>
        <?php endif; ?>
    </div>
</div>

<button id="carrinhoBtn" title="Abrir carrinho">
    <i class="fa-solid fa-shopping-cart fa-lg"></i>
    <div id="carrinhoBadge" style="display:none">0</div>
</button>

<!-- Modal Carrinho -->
<div class="modal fade" id="modalCarrinho" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-accent">
        <h5 class="modal-title">Resumo do Carrinho</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="cartItems"></div>
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <strong>Total:</strong>
            <h4 id="cartTotal">Kz 0,00</h4>
        </div>

        <div class="mb-3">
            <label class="form-label">Escolhe o método de pagamento</label>
            <div class="d-flex gap-2">
                <div class="payment-method flex-fill" data-method="mao">À Mão</div>
                <div class="payment-method flex-fill" data-method="multicaixa">Multicaixa Express</div>
                <div class="payment-method flex-fill" data-method="cartao">Cartão</div>
            </div>
        </div>

        <div id="paymentForms">
            <div class="method-form d-none" data-for="mao">
                <p>Pagamento em dinheiro. Confirma para registar pedido e notificar o vendedor.</p>
                <div class="mb-3"><label>Nome do Comprador (opcional)</label><input type="text" id="buyer_name_mao" class="form-control"></div>
            </div>

            <div class="method-form d-none" data-for="multicaixa">
                <p>Multicaixa Express - preenche referência/telefone.</p>
                <div class="mb-3"><label>Nº Referência / Telefone</label><input type="text" id="ref_multicaixa" class="form-control"></div>
                <div class="mb-3"><label>Nome do Comprador</label><input type="text" id="buyer_name_multicaixa" class="form-control"></div>
            </div>

            <div class="method-form d-none" data-for="cartao">
                <p>Cartão: insere dados mínimos.</p>
                <div class="row g-2">
                    <div class="col-12 mb-2"><label>Portador</label><input id="card_name" class="form-control"></div>
                    <div class="col-6"><label>Últimos 4 dígitos</label><input id="card_last4" maxlength="4" class="form-control"></div>
                    <div class="col-6"><label>Referência</label><input id="card_ref" class="form-control"></div>
                </div>
            </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Continuar a comprar</button>
        <button id="btnConfirmarCompra" type="button" class="btn btn-primary">Confirmar e Pagar</button>
      </div>
    </div>
  </div>
</div>

<!-- IA bubble -->
<div id="iaAssistente" aria-hidden="true">
    <div class="header">
        <div>Assistente GIA</div>
        <button class="btn btn-sm btn-light" id="closeIA"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="body" id="iaBody">
        <div class="text-muted small">Olá! Posso ajudar a escolher produtos ou finalizar pedidos.</div>
    </div>
    <div class="footer">
        <div class="input-group">
            <input id="iaInput" class="form-control form-control-sm" placeholder="Pergunta à assistente...">
            <button id="iaSend" class="btn btn-sm btn-success">Enviar</button>
            <button id="iaVoice" class="btn btn-sm btn-outline-primary" title="Falar com a assistente"><i class="fa-solid fa-microphone"></i></button>
        </div>
    </div>
</div>

<button id="botaoIA" title="Abrir assistente" style="position:fixed; right:30px; bottom:30px; width:56px; height:56px; border-radius:50%; background:var(--petroleo); color:var(--dourado); border:none; z-index:9998">
    <i class="fa-solid fa-comments"></i>
</button>

<?php
$footerPath = __DIR__ . '/../../../includes/footer.php';
if (file_exists($footerPath)) include_once $footerPath;
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    // Base URL (usado para chamadas API)
    const BASE_URL = '<?php echo rtrim(BASE_URL, "/"); ?>';

    // Utils e estado
    const fmtKz = v => 'Kz ' + Number(v).toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let cart = JSON.parse(localStorage.getItem('cantina_cart') || '[]');

    const badge = document.getElementById('carrinhoBadge');
    const carrinhoBtn = document.getElementById('carrinhoBtn');
    const modalElement = document.getElementById('modalCarrinho');
    const modalCarrinho = new bootstrap.Modal(modalElement);
    const cartItemsEl = document.getElementById('cartItems');
    const cartTotalEl = document.getElementById('cartTotal');

    function updateBadge(){
        const total = cart.reduce((s,i)=> s + i.qty, 0);
        if (total > 0) { badge.style.display='flex'; badge.textContent = total; }
        else { badge.style.display='none'; }
        localStorage.setItem('cantina_cart', JSON.stringify(cart));
    }

    function renderCart(){
        cartItemsEl.innerHTML = '';
        if (!cart.length) {
            cartItemsEl.innerHTML = '<div class="alert alert-light">Carrinho vazio.</div>';
            cartTotalEl.textContent = fmtKz(0);
            return;
        }
        let total = 0;
        const list = document.createElement('div'); list.className = 'list-group';
        cart.forEach((it, idx) => {
            const subtotal = it.qty * it.price; total += subtotal;
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex gap-3 align-items-center';
            item.innerHTML = `
                <img src="${it.img}" style="width:64px;height:48px;object-fit:cover;border-radius:6px;">
                <div class="flex-grow-1">
                    <div class="fw-bold">${it.name}</div>
                    <div class="text-muted small">${fmtKz(it.price)} x ${it.qty} = ${fmtKz(subtotal)}</div>
                </div>
                <div class="d-flex flex-column gap-1">
                    <div class="btn-group" role="group" aria-label="qty">
                        <button class="btn btn-sm btn-outline-secondary btn-decrease" data-idx="${idx}">-</button>
                        <button class="btn btn-sm btn-outline-secondary btn-increase" data-idx="${idx}">+</button>
                    </div>
                    <button class="btn btn-sm btn-outline-danger btn-remove mt-1" data-idx="${idx}"><i class="fa-solid fa-trash"></i></button>
                </div>`;
            list.appendChild(item);
        });
        cartItemsEl.appendChild(list);
        cartTotalEl.textContent = fmtKz(total);

        // event handlers
        cartItemsEl.querySelectorAll('.btn-increase').forEach(b=>b.addEventListener('click', e=>{
            const i = parseInt(e.currentTarget.dataset.idx,10); cart[i].qty++; updateBadge(); renderCart();
            // opcional: sincroniza com servidor
            fetch(location.href, { method:'POST', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action:'update', idx:i, qty:cart[i].qty })});
        }));
        cartItemsEl.querySelectorAll('.btn-decrease').forEach(b=>b.addEventListener('click', e=>{
            const i = parseInt(e.currentTarget.dataset.idx,10);
            if (cart[i].qty > 1) cart[i].qty--; else cart.splice(i,1);
            updateBadge(); renderCart();
            fetch(location.href, { method:'POST', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action:'update', idx:i, qty: cart[i]?.qty ?? 0 })});
        }));
        cartItemsEl.querySelectorAll('.btn-remove').forEach(b=>b.addEventListener('click', e=>{
            const i = parseInt(e.currentTarget.dataset.idx,10); cart.splice(i,1); updateBadge(); renderCart();
            fetch(location.href, { method:'POST', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action:'remove', idx:i })});
        }));
    }

    // Init badge
    updateBadge();

    // Adicionar produto (botões gerados pelo PHP)
    document.querySelectorAll('.btn-adicionar').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id, name = btn.dataset.name, price = parseFloat(btn.dataset.price)||0, img = btn.dataset.img||'';
            const idx = cart.findIndex(c=>c.id==id);
            if (idx >= 0) cart[idx].qty++; else cart.push({ id, name, price, qty:1, img });
            updateBadge();
            // opcional: sincroniza com servidor
            fetch(location.href, { method:'POST', headers:{ 'Content-Type':'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action:'add', id, name, price, img })});
            const old = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-check"></i> Adicionado'; setTimeout(()=> btn.innerHTML = old, 900);
        });
    });

    // abrir carrinho
    carrinhoBtn.addEventListener('click', ()=>{ renderCart(); modalCarrinho.show(); });

    // Métodos de pagamento UI
    document.querySelectorAll('.payment-method').forEach(el=> el.addEventListener('click', ()=>{
        document.querySelectorAll('.payment-method').forEach(x=>x.classList.remove('active'));
        el.classList.add('active');
        const method = el.dataset.method;
        document.querySelectorAll('.method-form').forEach(f=> f.classList.add('d-none'));
        const form = document.querySelector('.method-form[data-for="'+method+'"]');
        if (form) form.classList.remove('d-none');
        document.getElementById('modalCarrinho').dataset.method = method;
    }));

    // Confirmar e pagar
    document.getElementById('btnConfirmarCompra').addEventListener('click', async ()=>{
        if (!cart.length) return alert('Carrinho vazio.');

        const method = document.getElementById('modalCarrinho').dataset.method || 'mao';
        let methodData = {};
        if(method === 'mao'){
            methodData.buyer_name = document.getElementById('buyer_name_mao').value.trim();
        } else if(method === 'multicaixa'){
            methodData.ref = document.getElementById('ref_multicaixa').value.trim();
            methodData.buyer_name = document.getElementById('buyer_name_multicaixa').value.trim();
            
            // Validação básica para Multicaixa
            if (!methodData.ref) {
                alert('Por favor, insira a referência do Multicaixa');
                return;
            }
        } else if(method === 'cartao'){
            methodData.card_name = document.getElementById('card_name').value.trim();
            methodData.card_last4 = document.getElementById('card_last4').value.trim();
            methodData.card_ref = document.getElementById('card_ref').value.trim();
            
            // Validação básica para Cartão
            if (!methodData.card_name || !methodData.card_last4) {
                alert('Por favor, preencha os dados do cartão');
                return;
            }
        }

        const payload = {
            items: cart.map(i=>({ id:i.id, name:i.name, price:i.price, qty:i.qty })),
            total: cart.reduce((s,i)=>s+i.price*i.qty,0)
        };

        const btn = document.getElementById('btnConfirmarCompra');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processando...';

        try {
            const resp = await fetch(BASE_URL + '/app/api/checkout.php', {
                method:'POST',
                headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action:'checkout',
                    payload: JSON.stringify(payload),
                    method,
                    methodData: JSON.stringify(methodData)
                })
            });
            const json = await resp.json();
            if (json.success) {
                cart = [];
                updateBadge();
                renderCart();
                modalCarrinho.hide();
                
                    // Mensagem simples de sucesso (evita criar modal dinâmico que pode causar problemas de foco/aria)
                    alert('Pedido realizado com sucesso! Nº: ' + (json.orderId || '—'));
                    // Limpa formulários
                    document.querySelectorAll('.method-form input').forEach(input => input.value = '');
            } else {
                // Se o servidor devolveu problemas de stock, mostra informação detalhada
                if (Array.isArray(json.problems) && json.problems.length) {
                    const lines = json.problems.map(p => {
                        if (p.nome) return `${p.nome}: ${p.message} (stock atual: ${p.stock}, solicitado: ${p.requested})`;
                        return `${p.id || '—'}: ${p.message}`;
                    });
                    alert((json.error || 'Problemas com o stock:') + '\n' + lines.join('\n'));
                } else {
                    alert('Erro no pedido: ' + (json.error || 'Por favor, tente novamente'));
                }
            }
        } catch (err) {
            console.error('Erro no checkout:', err);
            alert('Erro ao processar o pedido. Por favor, tente novamente.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check me-2"></i>Confirmar e Pagar';
        }
    });

    // ---------------------------
    // IA: Painel, envio de mensagens e voz
    // ---------------------------
    const iaBtn = document.getElementById('botaoIA');
    const iaPanel = document.getElementById('iaAssistente');
    const closeIA = document.getElementById('closeIA');
    const iaBody = document.getElementById('iaBody');
    const iaInput = document.getElementById('iaInput');
    const iaSend = document.getElementById('iaSend');
    const iaVoiceBtn = document.getElementById('iaVoice');

    function appendIaMsg(txt, mine=false){
        const d = document.createElement('div');
        d.className = (mine ? 'text-end text-primary small mb-2' : 'text-start text-muted small mb-2');
        d.textContent = txt;
        iaBody.appendChild(d);
        iaBody.scrollTop = iaBody.scrollHeight;
    }

    function speak(txt){
        if ('speechSynthesis' in window) {
            const u = new SpeechSynthesisUtterance(txt);
            u.lang = 'pt-PT';
            window.speechSynthesis.cancel();
            window.speechSynthesis.speak(u);
        }
    }

    async function sendIaMessage(){
        const msg = iaInput.value.trim();
        if (!msg) return;
        appendIaMsg(msg, true);
        iaInput.value = '';
        try {
            const resp = await fetch(location.href, {
                method: 'POST',
                headers: { 'Content-Type':'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action:'ia_chat', message: msg })
            });
            const data = await resp.json();
            if (data.success) {
                appendIaMsg(data.reply, false);
                speak(data.reply);
            } else {
                appendIaMsg('Erro: ' + (data.error || 'sem resposta'), false);
            }
        } catch (err) {
            appendIaMsg('Erro de comunicação com a IA.', false);
            console.error('Erro IA:', err);
        }
    }

    iaSend.addEventListener('click', sendIaMessage);
    iaInput.addEventListener('keypress', e => { if (e.key === 'Enter') sendIaMessage(); });

    // Reconhecimento de voz (SpeechRecognition)
    function initSpeechRecognition(){
        if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
            appendIaMsg('Reconhecimento de voz não suportado neste navegador.', false);
            return;
        }
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        const rec = new SR();
        rec.lang = 'pt-PT';
        rec.interimResults = false;
        rec.maxAlternatives = 1;

        iaVoiceBtn.addEventListener('click', () => {
            try {
                rec.start();
                iaVoiceBtn.classList.add('active');
                iaVoiceBtn.innerHTML = '<i class="fa-solid fa-microphone-lines"></i>';
            } catch (e) { console.error(e); }
        });

        rec.onresult = (e) => {
            const text = e.results[0][0].transcript;
            iaInput.value = text;
            sendIaMessage();
            iaVoiceBtn.classList.remove('active');
            iaVoiceBtn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
        };

        rec.onerror = (ev) => {
            iaVoiceBtn.classList.remove('active');
            iaVoiceBtn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
            appendIaMsg('Erro no reconhecimento de voz.', false);
            console.error('Speech recognition error', ev);
        };
    }

    iaBtn.addEventListener('click', async ()=>{
        iaPanel.style.display = 'block';
        const saudacao = "Olá, seja bem-vindo ao Cardápio digital da Cantina. Eu sou a GIA, a tua assistente.";
        appendIaMsg(saudacao, false);
        speak(saudacao);

        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            try {
                await navigator.mediaDevices.getUserMedia({ audio: true });
                initSpeechRecognition();
            } catch (err) {
                appendIaMsg('Permissão de microfone negada. Podes usar o chat de texto.', false);
            }
        } else {
            appendIaMsg('Reconhecimento de voz não suportado neste navegador.', false);
        }
    });

    closeIA.addEventListener('click', ()=> iaPanel.style.display = 'none');

    // Função para inicializar badge e estado
    updateBadge();

})();
</script>
</body>
</html>
