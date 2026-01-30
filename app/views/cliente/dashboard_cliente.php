<?php
// dashboard_cliente.php - Versão limpa e funcional
// Requisitos: PHP + cURL. Define OPENAI_API_KEY via variável de ambiente:
//   export OPENAI_API_KEY="sk-..."
// Não coloques a chave no ficheiro em produção.

session_start();

// ---------------------------
// CONFIGURAÇÃO
// ---------------------------
// Determina o BASE_URL dinamicamente (localhost ou servidor)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Remove porta se for a padrão
if (strpos($host, ':80') === strlen($host) - 3 && $protocol === 'http') {
    $host = substr($host, 0, -3);
}
if (strpos($host, ':443') === strlen($host) - 4 && $protocol === 'https') {
    $host = substr($host, 0, -4);
}
define('BASE_URL', $protocol . '://' . $host);

// Obtém a chave da environment (mais seguro). Em dev, podes definir via servidor.
$OPENAI_API_KEY = getenv('OPENAI_API_KEY') ?: '';

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
    <!-- CSS Responsivo Global -->
    <link href="<?php echo rtrim(BASE_URL, '/'); ?>/assets/css/responsive.css" rel="stylesheet">
    <?php
    // Prefer local vendor files under /assets/vendor if present, otherwise fall back to CDN
    $localBootstrap = rtrim(BASE_URL, '/') . '/assets/vendor/bootstrap/css/bootstrap.min.css';
    $localFA = rtrim(BASE_URL, '/') . '/assets/vendor/fontawesome/css/all.min.css';
    $localBootstrapFile = __DIR__ . '/../../assets/vendor/bootstrap/css/bootstrap.min.css';
    $localFAFile = __DIR__ . '/../../assets/vendor/fontawesome/css/all.min.css';
    if (file_exists($localBootstrapFile)) {
        echo '<link href="' . $localBootstrap . '" rel="stylesheet">\n';
    } else {
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">\n';
    }
    if (file_exists($localFAFile)) {
        echo '<link href="' . $localFA . '" rel="stylesheet">\n';
    } else {
        echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">\n';
    }
    ?>
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
    /* YASMIN Widget Styles */
    #yasminWidget{ position:fixed; right:30px; bottom:30px; width:360px; background:#fff; border-radius:12px; box-shadow:0 12px 40px rgba(1,46,64,0.15); display:flex; flex-direction:column; z-index:9999; overflow:hidden; opacity:0; visibility:hidden; transform:translateY(20px); transition:all 0.3s ease-out; pointer-events:none; }
    #yasminWidget.active{ opacity:1; visibility:visible; transform:translateY(0); pointer-events:auto; animation:slideUpIn 0.3s ease-out; }
    .yasmin-header{ background:linear-gradient(135deg, var(--petroleo), #0d5c7a); color:var(--dourado); padding:14px; display:flex; align-items:center; justify-content:space-between; }
    .yasmin-title{ font-weight:700; font-size:1rem; display:flex; align-items:center; gap:8px; }
    .yasmin-body{ padding:16px; max-height:300px; overflow-y:auto; background:#f9fafb; }
    .yasmin-message{ padding:10px 12px; border-radius:8px; margin-bottom:10px; font-size:0.95rem; line-height:1.4; }
    .yasmin-message.yasmin-bot{ background:#e8f0f6; color:#1f2937; border-left:3px solid var(--petroleo); }
    .yasmin-message.yasmin-user{ background:var(--dourado); color:var(--petroleo); margin-left:20px; text-align:right; }
    .yasmin-footer{ padding:12px; border-top:1px solid #e5e7eb; background:#fff; }
    .yasmin-footer .input-group-sm .form-control{ font-size:0.9rem; }
    .yasmin-footer button{ font-size:0.9rem; }
    @keyframes slideUpIn{ from{ transform:translateY(20px); opacity:0; } to{ transform:translateY(0); opacity:1; } }
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

<!-- YASMIN Assistant Widget -->
<div id="yasminWidget">
    <div class="yasmin-header">
        <div class="yasmin-title">
            <i class="fa-solid fa-sparkles"></i> YASMIN
        </div>
        <button class="btn btn-sm btn-light" id="closeYasmin" title="Fechar"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="yasmin-body" id="yasminBody">
        <div class="yasmin-message yasmin-bot">
            Olá! Sou a YASMIN, tua assistente virtual.<br/>
            Estou aqui para ajudarte a escolher o melhor produto para ti!
        </div>
    </div>
    <div class="yasmin-footer">
        <div class="input-group input-group-sm">
            <button id="yasminMic" class="btn btn-outline-secondary" title="Falar" type="button" style="min-width:42px;"><i class="fa-solid fa-microphone"></i></button>
            <input id="yasminInput" class="form-control" placeholder="Ex: 'Quero algo saudável'..." />
            <button id="yasminSend" class="btn btn-success" title="Enviar"><i class="fa-solid fa-paper-plane"></i></button>
            <button id="yasminPlayToggle" class="btn btn-outline-primary" title="Ouvir respostas" type="button" style="min-width:42px; margin-left:6px;"><i class="fa-solid fa-volume-high"></i></button>
        </div>
    </div>
</div>

<button id="botaoYasmin" title="Abrir assistente YASMIN" 
    style="position:fixed; right:30px; bottom:30px; width:60px; height:60px; border-radius:50%; background:linear-gradient(135deg, var(--petroleo), #0d5c7a); color:var(--dourado); border:none; z-index:9998; box-shadow:0 8px 24px rgba(1,46,64,0.2); cursor:pointer; font-size:24px; transition:transform 0.2s, box-shadow 0.2s;">
    <i class="fa-solid fa-wand-magic-sparkles"></i>
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
    // YASMIN: Assistente Virtual para Escolha de Produtos
    // ---------------------------
    const yasminBtn = document.getElementById('botaoYasmin');
    const yasminWidget = document.getElementById('yasminWidget');
    const closeYasminBtn = document.getElementById('closeYasmin');
    const yasminBody = document.getElementById('yasminBody');
    const yasminInput = document.getElementById('yasminInput');
    const yasminSend = document.getElementById('yasminSend');
    
    let yasminAtivo = false;

    function abrirYasmin() {
        yasminWidget.classList.add('active');
        yasminAtivo = true;
        yasminInput.focus();
    }

    function fecharYasmin() {
        // Blur input first to avoid focused element being hidden (accessibility)
        try { yasminInput.blur(); } catch (e) {}
        yasminWidget.classList.remove('active');
        yasminAtivo = false;
    }

    function addYasminMessage(texto, isUser = false) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `yasmin-message ${isUser ? 'yasmin-user' : 'yasmin-bot'}`;
        msgDiv.innerHTML = texto; // Permite HTML (para bold, etc)
        yasminBody.appendChild(msgDiv);
        yasminBody.scrollTop = yasminBody.scrollHeight;
    }

    async function enviarYasminMessage() {
        const msg = yasminInput.value.trim();
        if (!msg) return;

        addYasminMessage(msg, true);
        yasminInput.value = '';
        yasminInput.focus();

        // Mostra "digitando..."
        const typingDiv = document.createElement('div');
        typingDiv.className = 'yasmin-message yasmin-bot';
        typingDiv.innerHTML = '<em>YASMIN está a escrever...</em>';
        yasminBody.appendChild(typingDiv);

        try {
            // SEMPRE pede áudio ao servidor - envia JSON (PHP lê php://input)
            let resp = await fetch(BASE_URL + '/app/api/yasmin_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json; charset=utf-8' },
                body: JSON.stringify({ mensagem: msg, audio: true })
            });

            // Remove mensagem "digitando" imediatamente
            typingDiv.remove();

            // Lê a resposta como texto primeiro (para debug)
            let textResp = await resp.text();
            console.log('[YASMIN] Resposta bruta:', textResp.substring(0, 200));

            // If server rejects JSON (some proxies/mod_security), retry as urlencoded form
            if (!resp.ok) {
                console.warn('[YASMIN] JSON POST failed, attempting urlencoded fallback (form)');
                try {
                    const form = new URLSearchParams();
                    form.append('mensagem', msg);
                    form.append('audio', '1');
                    const resp2 = await fetch(BASE_URL + '/app/api/yasmin_api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8' },
                        body: form.toString()
                    });
                    textResp = await resp2.text();
                    console.log('[YASMIN] Fallback resposta bruta:', textResp.substring(0,200));
                    if (!resp2.ok) {
                        addYasminMessage(`Erro HTTP ${resp2.status}: ${textResp}`, false);
                        console.error('HTTP Error fallback:', resp2.status, textResp);
                        return;
                    }
                    resp = resp2; // proceed with successful response
                } catch (e2) {
                    addYasminMessage(`Erro de comunicação (fallback): ${e2.message}`, false);
                    console.error('[YASMIN] Fallback error:', e2);
                    return;
                }
            }

            // Parse response text
            const textRespFinal = textResp;

            // Tenta parsear como JSON
            let data;
            try {
                data = JSON.parse(textRespFinal);
            } catch (e) {
                addYasminMessage(`Erro ao processar resposta: ${e.message}`, false);
                console.error('[YASMIN] JSON Parse error:', e, 'Raw:', textRespFinal);
                return;
            }

            if (data.success) {
                // Formata resposta com recomendações
                let resposta = data.mensagem || 'Desculpa, não consegui processar.';
                
                // Se há recomendações, adiciona botão "Adicionar" para cada produto
                if (data.recomendacoes && Array.isArray(data.recomendacoes)) {
                    resposta += '<div class="mt-2">';
                    data.recomendacoes.forEach(prod => {
                        resposta += `<div style="font-size:0.85rem; padding:8px; background:#fff; border-radius:6px; margin-bottom:6px; border-left:3px solid var(--dourado);">
                            <strong>${prod.nome}</strong><br/>
                            <small>Kz ${prod.preco.toFixed(2)}</small>
                            <button class="btn btn-xs btn-success ms-2" style="padding:2px 8px; font-size:0.75rem;" data-id="${prod.id}" data-name="${prod.nome}" data-price="${prod.preco}" data-img="${prod.imagem || ''}">
                                Adicionar
                            </button>
                        </div>`;
                    });
                    resposta += '</div>';
                }
                
                addYasminMessage(resposta, false);

                // se existir áudio em base64, reproduz
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
                        
                        // Guarda referência do áudio atual
                        currentAudio = audio;
                        isAudioPlaying = false;
                        
                        // Adiciona listeners para controlo
                        audio.addEventListener('play', () => {
                            isAudioPlaying = true;
                            yasminPlayToggle.classList.add('active');
                            yasminPlayToggle.style.background = 'var(--petroleo)';
                            yasminPlayToggle.style.color = 'var(--dourado)';
                        });
                        
                        audio.addEventListener('pause', () => {
                            isAudioPlaying = false;
                            yasminPlayToggle.classList.remove('active');
                            yasminPlayToggle.style.background = '';
                            yasminPlayToggle.style.color = '';
                        });
                        
                        audio.addEventListener('ended', () => {
                            isAudioPlaying = false;
                            yasminPlayToggle.classList.remove('active');
                            yasminPlayToggle.style.background = '';
                            yasminPlayToggle.style.color = '';
                        });
                        
                        // Garante que o áudio está pronto antes de reproduzir
                        audio.addEventListener('canplay', () => {
                            audio.play().catch(e => {
                                console.warn('[YASMIN Audio] Autoplay bloqueado:', e.message);
                            });
                        });
                        
                        // Define um timeout para reproducao forçada se o evento não disparar
                        setTimeout(() => {
                            if (audio.paused && currentAudio === audio) {
                                audio.play().catch(e => console.warn('[YASMIN Audio] Erro ao reproduzir:', e.message));
                            }
                        }, 500);
                        
                        console.log('[YASMIN Audio] Áudio pronto:', mimeType);
                    } catch (e) {
                        console.warn('[YASMIN Audio] Erro ao processar audio_base64:', e);
                    }
                }

                // Adiciona event listeners aos botões de "Adicionar"
                yasminBody.querySelectorAll('button[data-id]').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const id = btn.dataset.id;
                        const name = btn.dataset.name;
                        const price = parseFloat(btn.dataset.price) || 0;
                        const img = btn.dataset.img || '';
                        
                        const idx = cart.findIndex(c => c.id == id);
                        if (idx >= 0) cart[idx].qty++;
                        else cart.push({ id, name, price, qty: 1, img });
                        updateBadge();
                        
                        // Feedback visual
                        const oldHtml = btn.innerHTML;
                        btn.innerHTML = '<i class="fa-solid fa-check"></i> Adicionado!';
                        btn.disabled = true;
                        setTimeout(() => {
                            btn.innerHTML = oldHtml;
                            btn.disabled = false;
                        }, 1200);
                        
                        addYasminMessage(`Ótimo! Adicionaste "${name}" ao carrinho!`, true);
                    });
                });
            } else {
                const errorMsg = data.error || 'Falha desconhecida';
                console.error('YASMIN Error:', errorMsg, data);
                addYasminMessage(`Erro: ${errorMsg}`, false);
            }
        } catch (err) {
            typingDiv.remove();
            console.error('[YASMIN] Erro:', err.message);
            console.error('[YASMIN] URL tentada:', BASE_URL + '/app/api/yasmin_api.php');
            addYasminMessage('Erro de comunicação. Verifica a consola (F12) para mais detalhes.', false);
        }
    }

    // Event listeners YASMIN
    yasminBtn.addEventListener('click', abrirYasmin);
    closeYasminBtn.addEventListener('click', fecharYasmin);
    yasminSend.addEventListener('click', enviarYasminMessage);
    yasminInput.addEventListener('keypress', e => {
        if (e.key === 'Enter') enviarYasminMessage();
    });

    // Microfone (Web Speech API) - preenche input com transcrição
    const yasminMic = document.getElementById('yasminMic');
    const yasminPlayToggle = document.getElementById('yasminPlayToggle');
    
    // Guarda o áudio atual para controlo pelo toggle
    let currentAudio = null;
    let isAudioPlaying = false;
    
    let recognition = null;
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SR();
        recognition.lang = 'pt-PT';
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;

        recognition.addEventListener('result', (e) => {
            const text = e.results[0][0].transcript;
            yasminInput.value = text;
            yasminInput.focus();
        });
        
        recognition.addEventListener('error', (e) => {
            const errorMap = {
                'no-speech': 'Nenhuma voz detectada. Tenta novamente.',
                'audio-capture': 'Nenhum microfone disponível.',
                'network': 'Problema de conexão.',
                'not-allowed': 'Permissão de microfone negada. Verifica as definições do navegador.',
                'service-not-allowed': 'Serviço de reconhecimento de voz não disponível neste navegador.'
            };
            const msg = errorMap[e.error] || `Erro de voz: ${e.error}`;
            console.warn('[YASMIN Mic]', msg);
            // Silenciosa - não interrompe a experiência, apenas volta ao input manual
            yasminMic.style.opacity = '0.6';
            setTimeout(() => { yasminMic.style.opacity = '1'; }, 500);
        });
        
        recognition.addEventListener('end', () => {
            yasminMic.style.opacity = '1';
        });
    } else {
        yasminMic.style.display = 'none';
    }

    yasminMic.addEventListener('click', () => {
        if (!recognition) {
            console.warn('[YASMIN] Web Speech API não suportado neste navegador.');
            return;
        }
        try {
            yasminMic.style.opacity = '0.5';
            recognition.start();
        } catch (e) {
            console.warn('Speech recognition start failed', e);
            yasminMic.style.opacity = '1';
        }
    });

    // Toggle play audio - controla a reprodução do áudio atual
    yasminPlayToggle.addEventListener('click', () => {
        if (!currentAudio) {
            console.log('[YASMIN] Sem áudio para reproduzir');
            return;
        }
        
        if (isAudioPlaying) {
            // Está a tocar - pausa
            currentAudio.pause();
            console.log('[YASMIN] Áudio pausado');
        } else {
            // Não está a tocar - reproduz
            currentAudio.play().catch(e => {
                console.error('[YASMIN] Erro ao reproduzir:', e);
            });
            console.log('[YASMIN] Áudio a reproduzir');
        }
    });

    // Mostra YASMIN ao primeiro click
    yasminBtn.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.1)';
    });
    yasminBtn.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
    });

    // Função para inicializar badge e estado
    updateBadge();

})();
</script>
<!-- JavaScript Responsivo Global -->
<script src="<?php echo rtrim(BASE_URL, '/'); ?>/assets/js/responsive.js"></script>
</body>
</html>
