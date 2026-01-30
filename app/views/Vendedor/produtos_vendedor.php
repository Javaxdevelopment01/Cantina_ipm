<?php
// produtos_vendedor.php - Painel de gestão de produtos para vendedores
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Endpoint simples para retorno JSON dos produtos (usado pelo polling JS)
// MOVIDO PARA O TOPO para evitar que HTML do menu seja injetado
if (isset($_GET['ajax']) && $_GET['ajax'] === 'produtos') {
    // Busca produtos apenas para o AJAX
    $stmt = $conn->prepare("SELECT p.*, c.nome AS categoria_nome FROM produto p LEFT JOIN categoria c ON p.categoria_id = c.id ORDER BY p.id DESC");
    $stmt->execute();
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($produtos);
    exit;
}

// 1. Processamento de POST (deve ocorrer antes de qualquer output HTML/Headers)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao'] ?? '');
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $preco = floatval(str_replace(',', '.', $_POST['preco'] ?? 0));
    $categoria = intval($_POST['categoria'] ?? 0);
    $imagemDb = null;

    // Upload de imagem se existir
    if (!empty($_FILES['imagem']['name'])) {
        $uploadDir = __DIR__ . '/../../../uploads/produtos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $newName = time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $dest = $uploadDir . $newName;
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $dest)) {
            $imagemDb = 'uploads/produtos/' . $newName;
        }
    }

    if ($acao === 'cadastrar') {
        // Se já existe um produto com mesmo nome e categoria, apenas aumenta o estoque
        $check = $conn->prepare("SELECT id, quantidade FROM produto WHERE nome = :nome AND categoria_id = :cat LIMIT 1");
        $check->execute([':nome' => $nome, ':cat' => $categoria]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $sql = "UPDATE produto SET quantidade = quantidade + :qtd, preco = :preco, descricao = :descricao" . ($imagemDb ? ", imagem = :img" : "") . " WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $params = [
                ':qtd' => $quantidade,
                ':preco' => $preco,
                ':descricao' => $descricao,
                ':id' => $existing['id']
            ];
            if ($imagemDb) $params[':img'] = $imagemDb;
            $stmt->execute($params);
        } else {
            $stmt = $conn->prepare("INSERT INTO produto (nome, descricao, quantidade, preco, categoria_id, imagem) VALUES (:nome, :descricao, :qtd, :preco, :cat, :img)");
            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao,
                ':qtd' => $quantidade,
                ':preco' => $preco,
                ':cat' => $categoria,
                ':img' => $imagemDb
            ]);
        }
    }

    if ($acao === 'editar' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        if ($imagemDb) {
            $sql = "UPDATE produto SET nome=:nome, descricao=:descricao, quantidade=:qtd, preco=:preco, categoria_id=:cat, imagem=:img WHERE id=:id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nome'=>$nome,
                ':descricao'=>$descricao,
                ':qtd'=>$quantidade,
                ':preco'=>$preco,
                ':cat'=>$categoria,
                ':img'=>$imagemDb,
                ':id'=>$id
            ]);
        } else {
            $sql = "UPDATE produto SET nome=:nome, descricao=:descricao, quantidade=:qtd, preco=:preco, categoria_id=:cat WHERE id=:id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nome'=>$nome,
                ':descricao'=>$descricao,
                ':qtd'=>$quantidade,
                ':preco'=>$preco,
                ':cat'=>$categoria,
                ':id'=>$id
            ]);
        }
    }

    if ($acao === 'excluir' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM produto WHERE id=:id");
        $stmt->execute([':id'=>$id]);
    }

    // Após ação bem sucedida, retorna JSON se for AJAX ou redireciona se for post normal
    if (isset($_POST['is_ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => 'Operação realizada com sucesso!']);
        exit;
    }

    header('Location: produtos_vendedor.php');
    exit;
}

// 2. Includes e inicialização de visualização
include __DIR__ . '/includes/menu_vendedor.php';

// Função de segurança para texto
function safe($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}


// Buscar produtos e categorias
$stmt = $conn->prepare("SELECT p.*, c.nome AS categoria_nome FROM produto p LEFT JOIN categoria c ON p.categoria_id = c.id ORDER BY p.id DESC");
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT id, nome FROM categoria ORDER BY nome ASC");
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Limiar de alerta de estoque (ajustável)
$lowThreshold = 5;
?>

<div class="main container-fluid">
    <!-- Header específico da aba de produtos (sem navegação extra) -->
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 style="color:var(--petroleo); margin:0;">Gestão de Produtos</h2>
            <p class="text-muted" style="margin:0;">Aqui pode criar, editar e remover produtos</p>
        </div>
        <div>
            <!-- Botão Novo Produto -->
            <button type="button" class="btn btn-dourado btn-novo-floating" id="btnNovoProduto" title="Novo Produto">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>
    </div>

    <!-- (Removida navegação por abas; use 'Alertas de Estoque' no menu lateral) -->

    <div class="grade">
    <?php if (empty($produtos)): ?>
        <div class="alert alert-light text-center">Nenhum produto encontrado.</div>
    <?php else: ?>
        <?php foreach ($produtos as $p): ?>
            <div class="produto-card">
                <img src="<?php echo safe(!empty($p['imagem']) ? '/'.$p['imagem'] : 'https://via.placeholder.com/400x300?text=Sem+Imagem'); ?>" class="img-produto" alt="<?php echo safe($p['nome']); ?>">
                <div style="padding:12px;">
                    <h5><?php echo safe($p['nome']); ?></h5>
                    <p class="text-muted small mb-1"><?php echo safe($p['categoria_nome']); ?></p>
                    <p class="text-muted small">Qtd: <span class="qty-count" data-prod-id="<?php echo $p['id']; ?>"><?php echo intval($p['quantidade']); ?></span></p>
                    <strong>KZ <?php echo number_format($p['preco'], 2, ',', '.'); ?></strong>
                    <div class="acoes mt-3">
                        <!-- Botão Editar -->
                        <button type="button" class="btn btn-edit btn-editar"
                            data-id="<?php echo $p['id']; ?>"
                            data-nome="<?php echo safe($p['nome']); ?>"
                            data-descricao="<?php echo safe($p['descricao']); ?>"
                            data-quantidade="<?php echo $p['quantidade']; ?>"
                            data-preco="<?php echo $p['preco']; ?>"
                            data-categoria="<?php echo $p['categoria_id']; ?>"
                            data-img="<?php echo !empty($p['imagem']) ? '/'.htmlspecialchars($p['imagem'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                            title="Editar">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="post" class="form-excluir-ajax" style="display:inline;">
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="is_ajax" value="1">
                            <button type="submit" class="btn btn-delete" title="Excluir">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<!-- Pure CSS Custom Modal -->
<div id="customModal" class="custom-modal">
    <div class="custom-modal-content">
        <div class="modal-header-premium">
            <h5 id="modalProdutoLabel" style="color: #fff; font-weight: 700; font-size: 1.4rem; letter-spacing: 0.5px; display: flex; align-items: center; margin:0;">
                <i class="fa-solid fa-box-open me-3" style="color: var(--dourado);"></i>
                <span>Novo Produto</span>
            </h5>
            <button type="button" class="btn-close-custom" onclick="fecharModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        
        <div class="modal-body-custom">
            <form id="formProduto" method="post" enctype="multipart/form-data">
                <input type="hidden" name="acao" id="acao" value="cadastrar">
                <input type="hidden" name="id" id="edit-id">
                
                <!-- Nome do Produto -->
                <div class="mb-3">
                    <label class="form-label" style="font-weight: 700; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Nome do Produto</label>
                    <input type="text" class="form-control" name="nome" id="edit-nome" required 
                           placeholder="Ex: Bebida Energética 500ml" style="border-radius: 12px; padding: 12px 15px; border: 2px solid #f1f5f9; background: #f8fafc;">
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight: 700; color: #475569; font-size: 0.85rem; text-transform: uppercase;">Categoria</label>
                        <select class="form-select" name="categoria" id="edit-categoria" required style="border-radius: 12px; padding: 12px 15px; border: 2px solid #f1f5f9; background: #f8fafc;">
                            <option value="" selected disabled>Selecionar...</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo safe($c['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight: 700; color: #475569; font-size: 0.85rem; text-transform: uppercase;">Estoque Inicial</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="quantidade" id="edit-quantidade" required min="0" placeholder="0" style="border-radius: 12px 0 0 12px; padding: 12px 15px; border: 2px solid #f1f5f9; background: #f8fafc; border-right: none;">
                            <span class="input-group-text" style="background: #f1f5f9; border: 2px solid #f1f5f9; border-radius: 0 12px 12px 0; color: #64748b; font-weight: 600;">un</span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-weight: 700; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Preço de Venda (KZ)</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background: #f1f5f9; border: 2px solid #f1f5f9; border-radius: 12px 0 0 12px; color: var(--petroleo); font-weight: 800;">KZ</span>
                        <input type="text" class="form-control" name="preco" id="edit-preco" required placeholder="0,00" style="border-radius: 0 12px 12px 0; padding: 12px 15px; border: 2px solid #f1f5f9; background: #f8fafc; border-left: none; font-weight: 700; font-size: 1.1rem; color: var(--petroleo);">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-weight: 700; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">Descrição</label>
                    <textarea class="form-control" name="descricao" id="edit-descricao" placeholder="Breve nota sobre o produto..." rows="3" style="border-radius: 12px; padding: 12px 15px; border: 2px solid #f1f5f9; background: #f8fafc; resize: none;"></textarea>
                </div>

                <!-- Imagem Compacta -->
                <div class="image-upload-zone" id="dropZone" style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 20px; height: 160px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s; position: relative; overflow: hidden; margin-top: 10px;">
                    <input type="file" name="imagem" id="edit-imagem" hidden accept="image/*">
                    <div class="upload-placeholder" id="uploadPlaceholder" style="text-align: center;">
                        <div class="upload-icon" style="font-size: 2rem; color: #94a3b8; margin-bottom: 8px;">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                        <p style="margin: 0; font-weight: 600; color: #475569; font-size: 0.9rem;">Imagem do Produto</p>
                        <span style="font-size: 0.75rem; color: #94a3b8;">Arraste ou clique</span>
                    </div>
                    <div id="previewContainer" class="preview-active" style="display:none; width: 100%; height: 100%;">
                        <img id="previewImg" src="" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                        <div class="change-overlay" style="position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; color: #fff; opacity:0; transition:0.3s;">
                            <i class="fa-solid fa-camera me-2"></i> Alterar
                        </div>
                    </div>
                </div>
                
                <!-- Botão Salvar Compacto -->
                <div class="mt-4 pt-2">
                    <button type="submit" id="btnSalvarProduto" class="btn w-100 py-3" style="background: linear-gradient(135deg, var(--petroleo) 0%, #034c6a 100%); color: #fff; border: none; border-radius: 15px; font-weight: 800; font-size: 1.1rem; box-shadow: 0 8px 25px rgba(1, 46, 64, 0.2); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); display: flex; align-items: center; justify-content: center;">
                        <span class="btn-text d-flex align-items-center">
                            <i class="fa-solid fa-check-double me-2"></i>
                            Confirmar Cadastro
                        </span>
                        <span class="btn-loader d-none">
                            <i class="fa-solid fa-circle-notch fa-spin me-2"></i>
                            Gravando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .main { 
        min-height: 60vh; 
        margin-left: 270px; /* Increased from 250px for breathing room */
        padding: 30px; 
        transition: 0.3s;
    }
    @media (max-width: 1024px) {
        .main {
            margin-left: 0 !important;
            padding: 80px 20px 20px !important;
        }
    }
    .cabecalho h2 { font-weight:700; }
    .btn-dourado { background: #D4AF37; color: #012E40; font-weight:600; border:none; padding:10px 20px; border-radius:8px; }
    
    /* Grid Responsivo Inteligente */
    .grade { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
        gap: 20px; 
    }
    
    /* Ajustes específicos para mobile muito pequeno */
    @media (max-width: 480px) {
        .grade { grid-template-columns: 1fr; }
        .page-header { flex-direction: column; align-items: flex-start; gap: 10px; }
        .btn-novo-floating { 
            position: fixed; 
            bottom: 20px; 
            right: 20px; 
            z-index: 1000;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .img-produto { height: 200px; }
    }
    .produto-card { background:white; border-radius:12px; overflow:hidden; text-align:left; box-shadow:0 3px 8px rgba(0,0,0,0.08); transition:0.25s; display:flex; flex-direction:column; height:100%; }
    .produto-card:hover { transform:translateY(-6px); box-shadow:0 8px 24px rgba(0,0,0,0.12); }
    .img-produto { width:100%; height:180px; object-fit:cover; display:block; }
    /* Conteúdo do card flexível para alinhar ações ao fundo */
    .produto-card > div { display:flex; flex-direction:column; flex:1 1 auto; padding:12px; }
    .produto-card .acoes { margin-top:auto; }
    .produto-card h5 { margin:0 0 6px; color:var(--petroleo); padding-right:12px; }
    .acoes { display:flex; gap:8px; justify-content:flex-end; }

    /* Novo produto flutuante à direita */
    .btn-novo-floating { width:56px; height:56px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; box-shadow:0 8px 20px rgba(1,46,64,0.12); font-size:18px; }
    .btn-novo-floating i { margin:0; }

    /* Botões de ação nos cards */
    .btn-edit { background: var(--petroleo); color: var(--dourado); border:none; width:44px; height:44px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; }
    .btn-edit:hover { transform:scale(1.05); box-shadow:0 6px 18px rgba(1,46,64,0.12); }

    .btn-delete { background:#dc3545; color:#fff; border:none; width:44px; height:44px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; }
    .btn-delete:hover { transform:scale(1.05); box-shadow:0 6px 18px rgba(220,53,69,0.12); }

    /* --- ULTRA-PREMIUM MODAL DESIGN --- */
    .premium-modal {
        border-radius: 30px !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(20px) !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4) !important;
        overflow: hidden;
    }

    .modal-header-premium {
        background: linear-gradient(135deg, var(--petroleo) 0%, #034c6a 100%);
        padding: 30px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
    }

    .header-content {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .icon-box {
        width: 60px;
        height: 60px;
        background: rgba(212, 175, 55, 0.15);
        border: 2px solid var(--dourado);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--dourado);
        font-size: 24px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .title-box h5 {
        color: #fff;
        margin: 0;
        font-weight: 800;
        font-size: 1.6rem;
        letter-spacing: -0.5px;
    }

    .title-box p {
        color: rgba(255,255,255,0.6);
        margin: 0;
        font-size: 0.9rem;
    }

    .btn-close-premium {
        background: rgba(255,255,255,0.1);
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.3s;
    }

    .btn-close-premium:hover {
        background: rgba(255,255,255,0.2);
        transform: rotate(90deg);
    }

    .modal-body-premium {
        padding: 40px;
        max-height: 70vh;
        overflow-y: auto;
        /* Scrollbar elegante */
        scrollbar-width: thin;
        scrollbar-color: var(--petroleo) #f1f1f1;
    }

    .modal-body-premium::-webkit-scrollbar {
        width: 8px;
    }
    .modal-body-premium::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .modal-body-premium::-webkit-scrollbar-thumb {
        background: var(--petroleo);
        border-radius: 10px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1.6fr 1fr;
        gap: 30px;
    }

    @media (max-width: 991px) {
        .form-grid { grid-template-columns: 1fr; }
    }

    .premium-group {
        margin-bottom: 25px;
    }

    .premium-group label {
        display: block;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 10px;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .premium-group label i {
        color: var(--dourado);
        margin-right: 8px;
        width: 18px;
        text-align: center;
    }

    .premium-group input, 
    .premium-group select, 
    .premium-group textarea {
        width: 100%;
        border: 2px solid #e2e8f0;
        padding: 14px 20px;
        border-radius: 16px;
        background: #f8fafc;
        transition: all 0.3s;
        font-size: 0.95rem;
        color: #0f172a;
    }

    .premium-group input:focus, 
    .premium-group select:focus, 
    .premium-group textarea:focus {
        border-color: var(--petroleo);
        background: #fff;
        box-shadow: 0 8px 16px rgba(1, 46, 64, 0.08);
        outline: none;
        transform: translateY(-2px);
    }

    .input-with-label {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-with-label span {
        position: absolute;
        left: 20px;
        font-weight: 800;
        color: #64748b;
        pointer-events: none;
    }

    .input-with-label input {
        padding-left: 50px;
    }

    /* Upload Zone */
    .image-upload-zone {
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 24px;
        height: 300px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.3s;
        position: relative;
        overflow: hidden;
    }

    .image-upload-zone:hover {
        border-color: var(--petroleo);
        background: rgba(1, 46, 64, 0.02);
    }

    .upload-placeholder {
        text-align: center;
    }

    .upload-icon {
        font-size: 3rem;
        color: #94a3b8;
        margin-bottom: 15px;
        transition: 0.3s;
    }

    .image-upload-zone:hover .upload-icon {
        color: var(--petroleo);
        transform: scale(1.1);
    }

    .upload-placeholder p {
        font-weight: 700;
        margin: 0;
        color: #475569;
    }

    .upload-placeholder span {
        color: #94a3b8;
        font-size: 0.8rem;
    }

    .preview-active {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
    }

    .preview-active img {
        width: 100%; height: 100%; object-fit: cover;
    }

    .change-overlay {
        position: absolute;
        bottom: 20px; left: 50%;
        transform: translateX(-50%);
        background: rgba(1, 46, 64, 0.8);
        color: #fff;
        padding: 8px 20px;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 600;
        backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        gap: 8px;
        opacity: 0;
        transition: 0.3s;
    }

    .image-upload-zone:hover .change-overlay {
        opacity: 1;
        bottom: 25px;
    }

    .info-alert-premium {
        margin-top: 20px;
        background: rgba(212, 175, 55, 0.08);
        border: 1px solid rgba(212, 175, 55, 0.2);
        border-radius: 18px;
        padding: 15px 20px;
        display: flex;
        gap: 15px;
        align-items: flex-start;
    }

    .info-alert-premium i {
        color: var(--dourado);
        font-size: 1.2rem;
        margin-top: 2px;
    }

    .info-alert-premium h6 {
        margin: 0;
        font-weight: 700;
        color: #0f172a;
        font-size: 0.9rem;
    }

    .info-alert-premium p {
        margin: 5px 0 0;
        font-size: 0.8rem;
        color: #475569;
        line-height: 1.4;
    }

    /* Footer */
    .modal-footer-premium {
        padding: 30px 40px;
        background: #fff;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: flex-end;
        gap: 15px;
    }

    .btn-cancel {
        background: #f1f5f9;
        color: #475569;
        border: none;
        padding: 14px 30px;
        border-radius: 16px;
        font-weight: 700;
        transition: 0.3s;
    }

    .btn-cancel:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    .btn-save-premium {
        background: linear-gradient(135deg, var(--petroleo) 0%, #034c6a 100%);
        color: #fff;
        border: none;
        padding: 14px 40px;
        border-radius: 16px;
        font-weight: 800;
        box-shadow: 0 10px 20px rgba(1, 46, 64, 0.2);
        transition: 0.3s;
    }

    .btn-save-premium:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(1, 46, 64, 0.3);
    }

    .btn-save-premium:active {
        transform: translateY(-1px);
    }

    #btnSalvarProduto:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 12px 30px rgba(1, 46, 64, 0.3);
        filter: brightness(1.1);
    }
    #btnSalvarProduto:active {
        transform: translateY(-1px);
    }

    /* Grid layout smoothing */
    .grade {
        animation: fadeInUp 0.5s ease-out;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const modal = document.getElementById('modalProduto');
    const form = document.getElementById('formProduto');
    const acao = document.getElementById('acao');
    const label = document.getElementById('modalProdutoLabel');
    const previewContainer = document.getElementById('previewContainer');
    const previewImg = document.getElementById('previewImg');
    const btnNovo = document.getElementById('btnNovoProduto');
    const editarBtns = document.querySelectorAll('.btn-editar');

    // Drag and Drop Zone
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('edit-imagem');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');

    dropZone.onclick = () => fileInput.click();

    fileInput.onchange = function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                previewContainer.style.display = 'block';
                uploadPlaceholder.style.display = 'none';
            };
            reader.readAsDataURL(this.files[0]);
        }
    };

    dropZone.ondragover = (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'var(--petroleo)';
        dropZone.style.background = 'rgba(1, 46, 64, 0.05)';
    };

    dropZone.ondragleave = () => {
        dropZone.style.borderColor = '#cbd5e1';
        dropZone.style.background = '#f8fafc';
    };

    dropZone.ondrop = (e) => {
        e.preventDefault();
        fileInput.files = e.dataTransfer.files;
        fileInput.dispatchEvent(new Event('change'));
    };

    // Pure JS Custom Modal Logic
    const customModal = document.getElementById('customModal');

    function abrirModal() {
        customModal.classList.add('open');
    }

    function fecharModal() {
        customModal.classList.remove('open');
        // Wait for transition to finish before hiding (handled by CSS opacity only for visual)
    }

    // Close on outside click
    window.onclick = function(event) {
        if (event.target == customModal) {
            fecharModal();
        }
    }

    // Função para recarregar a grade de produtos via AJAX
    async function recarregarProdutos() {
        try {
            const response = await fetch('produtos_vendedor.php?ajax=produtos');
            const produtos = await response.json();
            const grade = document.querySelector('.grade');
            
            if (produtos.length === 0) {
                grade.innerHTML = '<div class="alert alert-light text-center">Nenhum produto encontrado.</div>';
                return;
            }

            let html = '';
            produtos.forEach(p => {
                const imagem = p.imagem ? ('/' + p.imagem.replace(/^\//, '')) : 'https://via.placeholder.com/400x300?text=Sem+Imagem';
                const preco = parseFloat(p.preco).toLocaleString('pt-PT', {minimumFractionDigits: 2});
                
                html += `
                    <div class="produto-card">
                        <img src="${imagem}" class="img-produto" alt="${p.nome}">
                        <div style="padding:12px;">
                            <h5>${p.nome}</h5>
                            <p class="text-muted small mb-1">${p.categoria_nome || 'Sem categoria'}</p>
                            <p class="text-muted small">Qtd: <span class="qty-count">${p.quantidade}</span></p>
                            <strong>KZ ${preco}</strong>
                            <div class="acoes mt-3">
                                <button type="button" class="btn btn-edit btn-editar"
                                    data-id="${p.id}"
                                    data-nome="${p.nome}"
                                    data-descricao="${p.descricao || ''}"
                                    data-quantidade="${p.quantidade}"
                                    data-preco="${p.preco}"
                                    data-categoria="${p.categoria_id}"
                                    data-img="${p.imagem ? '/' + p.imagem.replace(/^\//, '') : ''}"
                                    title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="post" class="form-excluir-ajax" style="display:inline;">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id" value="${p.id}">
                                    <input type="hidden" name="is_ajax" value="1">
                                    <button type="submit" class="btn btn-delete" title="Excluir">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                `;
            });
            grade.innerHTML = html;
            
            // Reatribuir eventos
            vincularEventos();
        } catch (err) {
            console.error('Erro ao recarregar produtos:', err);
        }
    }

    function vincularEventos() {
        // Editar
        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', function(e){
                e.preventDefault();
                label.textContent = 'Editar Produto';
                acao.value = 'editar';
                document.getElementById('edit-id').value = this.dataset.id;
                document.getElementById('edit-nome').value = this.dataset.nome;
                document.getElementById('edit-descricao').value = this.dataset.descricao;
                document.getElementById('edit-quantidade').value = this.dataset.quantidade;
                document.getElementById('edit-preco').value = this.dataset.preco;
                document.getElementById('edit-categoria').value = this.dataset.categoria;

                if (this.dataset.img) {
                    previewImg.src = this.dataset.img;
                    previewContainer.style.display = 'block';
                } else {
                    previewContainer.style.display = 'none';
                }
                abrirModal();
            });
        });

        // Excluir via AJAX
        document.querySelectorAll('.form-excluir-ajax').forEach(formEx => {
            formEx.onsubmit = async function(e) {
                e.preventDefault();
                if(!confirm('Deseja excluir este produto?')) return;
                
                const formData = new FormData(this);
                try {
                    const resp = await fetch('produtos_vendedor.php', {
                        method: 'POST',
                        body: formData
                    });
                    const res = await resp.json();
                    if(res.success) {
                        recarregarProdutos();
                    }
                } catch(err) {
                    alert('Erro ao excluir produto.');
                }
            };
        });
    }

    // Submissão do Formulário Principal via AJAX
    form.addEventListener('submit', async function(e){
        e.preventDefault();
        
        const btn = document.getElementById('btnSalvarProduto');
        const text = btn.querySelector('.btn-text');
        const loader = btn.querySelector('.btn-loader');
        
        text.classList.add('d-none');
        loader.classList.remove('d-none');
        btn.disabled = true;

        const formData = new FormData(this);
        formData.append('is_ajax', '1');

        try {
            const resp = await fetch('produtos_vendedor.php', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();
            
            if(res.success) {
                fecharModal();
                recarregarProdutos();
                // Opcional: Toast de sucesso
            } else {
                alert('Erro: ' + (res.message || 'Falha ao salvar.'));
            }
        } catch(err) {
            console.error(err);
            alert('Erro de conexão ao salvar.');
        } finally {
            text.classList.remove('d-none');
            loader.classList.add('d-none');
            btn.disabled = false;
        }
    });

    // Clique no botão Novo Produto
    btnNovo.addEventListener('click', function(e){
        e.preventDefault();
        label.textContent = 'Novo Produto';
        acao.value = 'cadastrar';
        form.reset();
        document.getElementById('edit-id').value = '';
        previewContainer.style.display = 'none';
        abrirModal();
    });

    // Pré-visualização da imagem
    document.getElementById('edit-imagem').addEventListener('change', function(){
        const f = this.files[0];
        if (f) {
            const url = URL.createObjectURL(f);
            previewImg.src = url;
            previewContainer.style.display = 'block';
        }
    });

    // Inicialização
    vincularEventos();
});
</script>

<style>
    /* Custom Modal CSS */
    .custom-modal {
        display: none; /* Hidden by default */
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        background-color: rgba(0,0,0,0.5); /* Black w/ opacity */
        backdrop-filter: blur(5px);
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .custom-modal.open {
        display: flex; /* Activate flex to center */
        opacity: 1;
    }

    .custom-modal-content {
        background-color: #fefefe;
        margin: auto;
        border-radius: 20px;
        width: 90%;
        max-width: 500px; /* Limit width */
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transform: scale(0.9);
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: flex;
        flex-direction: column;
        max-height: 90vh; /* Don't overflow screen height */
    }
    
    .custom-modal.open .custom-modal-content {
        transform: scale(1);
    }

    .modal-header-premium {
        background: linear-gradient(135deg, var(--petroleo) 0%, #034c6a 100%);
        padding: 20px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 20px 20px 0 0;
    }

    .modal-body-custom {
        padding: 25px 30px;
        overflow-y: auto;
    }

    .btn-close-custom {
        background: rgba(255,255,255,0.1);
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
    }
    .btn-close-custom:hover {
        background: rgba(255,255,255,0.3);
        transform: rotate(90deg);
    }
</style>

<?php
// Se houver ?edit=ID na URL, abrir o modal com os dados do produto solicitado
if (!empty($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $productToEdit = null;
    foreach ($produtos as $pp) {
        if (intval($pp['id']) === $editId) { $productToEdit = $pp; break; }
    }
    if ($productToEdit) {
        $jsNome = json_encode($productToEdit['nome']);
        $jsDescricao = json_encode($productToEdit['descricao']);
        $jsQuantidade = json_encode($productToEdit['quantidade']);
        $jsPreco = json_encode($productToEdit['preco']);
        $jsCategoria = json_encode($productToEdit['categoria_id']);
        $jsImg = json_encode(!empty($productToEdit['imagem']) ? (strpos($productToEdit['imagem'], '/') === 0 ? $productToEdit['imagem'] : '/'.$productToEdit['imagem']) : '');
        echo "<script>document.addEventListener('DOMContentLoaded', function(){\n" .
             "  document.getElementById('modalProdutoLabel').innerHTML = '<i class=\"fa-solid fa-pen me-3\" style=\"color: var(--dourado);\"></i><span>Editar Produto</span>';\n" .
             "  document.getElementById('acao').value = 'editar';\n" .
             "  document.getElementById('edit-id').value = '" . intval($productToEdit['id']) . "';\n" .
             "  document.getElementById('edit-nome').value = $jsNome;\n" .
             "  document.getElementById('edit-descricao').value = $jsDescricao;\n" .
             "  document.getElementById('edit-quantidade').value = $jsQuantidade;\n" .
             "  document.getElementById('edit-preco').value = $jsPreco;\n" .
             "  document.getElementById('edit-categoria').value = $jsCategoria;\n" .
             ($productToEdit['imagem'] ? "  document.getElementById('previewImg').src = $jsImg; document.getElementById('previewContainer').style.display = 'block';\n" : '') .
             "  abrirModal();\n" .
             "});</script>";
    }
}
?>

<!-- estilos de alertas movidos para a página dedicada de alertas -->

