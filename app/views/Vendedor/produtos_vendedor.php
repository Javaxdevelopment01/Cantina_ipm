<?php
// produtos_vendedor.php - Painel de gestão de produtos para vendedores
session_start();
require_once __DIR__ . '/../../../config/database.php';
// includes do vendedor (sidebar)
include __DIR__ . '/includes/menu_vendedor.php';



// Função de segurança para texto
function safe($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

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
        // opcional: remover ficheiro antigo (não implementado para segurança)
        $stmt = $conn->prepare("DELETE FROM produto WHERE id=:id");
        $stmt->execute([':id'=>$id]);
    }

    // após ação, redireciona para evitar re-submissão
    header('Location: produtos_vendedor.php');
    exit;
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

// Endpoint simples para retorno JSON dos produtos (usado pelo polling JS)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'produtos') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($produtos);
    exit;
}
?>

<div class="main container-fluid" style="margin-left:270px; padding:30px;">
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
                        <form method="post" style="display:inline;" onsubmit="return confirm('Deseja excluir este produto?');">
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
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

<!-- Modal Novo/Editar Produto -->
<div class="modal fade" id="modalProduto" tabindex="-1" aria-labelledby="modalProdutoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalProdutoLabel">Novo Produto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-nav">
        <ul class="nav nav-pills" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#info-basica" type="button">
                    <i class="fa-solid fa-info-circle me-2"></i>Informações Básicas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#detalhes" type="button">
                    <i class="fa-solid fa-list me-2"></i>Detalhes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#imagem-produto" type="button">
                    <i class="fa-solid fa-image me-2"></i>Imagem
                </button>
            </li>
        </ul>
      </div>
      <div class="modal-body">
        <form id="formProduto" method="post" enctype="multipart/form-data">
            <input type="hidden" name="acao" id="acao" value="cadastrar">
            <input type="hidden" name="id" id="edit-id">
            
            <div class="tab-content">
                <!-- Informações Básicas -->
                <div class="tab-pane fade show active" id="info-basica">
                    <div class="form-section">
                        <h6 class="form-section-title">Informações Básicas do Produto</h6>
                        
                        <!-- Nome do Produto -->
                        <div class="mb-4">
                            <label class="form-label">Nome do Produto</label>
                            <input type="text" class="form-control" name="nome" id="edit-nome" required 
                                   placeholder="Digite o nome do produto">
                        </div>
                        
                        <!-- Categoria -->
                        <div class="mb-4">
                            <label class="form-label">Categoria</label>
                            <select class="form-select" name="categoria" id="edit-categoria" required>
                                <option value="" selected disabled>Selecionar categoria...</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo safe($c['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Quantidade e Preço -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Quantidade</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="quantidade" 
                                           id="edit-quantidade" required min="0" placeholder="0">
                                    <span class="input-group-text">un</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preço</label>
                                <div class="input-group">
                                    <span class="input-group-text">KZ</span>
                                    <input type="text" class="form-control" name="preco" 
                                           id="edit-preco" required placeholder="0,00">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detalhes -->
                <div class="tab-pane fade" id="detalhes">
                    <div class="form-section">
                        <h6 class="form-section-title">Detalhes do Produto</h6>
                        
                        <!-- Descrição -->
                        <div class="mb-4">
                            <label class="form-label">Descrição Detalhada</label>
                            <textarea class="form-control" name="descricao" id="edit-descricao" 
                                      placeholder="Descreva o produto detalhadamente..." rows="6"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Imagem -->
                <div class="tab-pane fade" id="imagem-produto">
                    <div class="form-section">
                        <h6 class="form-section-title">Imagem do Produto</h6>
                        
                        <!-- Upload de Imagem -->
                        <div class="mb-4">
                            <label class="form-label">Upload de Imagem</label>
                            <input type="file" class="form-control" name="imagem" id="edit-imagem" accept="image/*">
                            <small class="text-muted d-block mt-2">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                Formatos aceitos: JPG, PNG. Tamanho máximo: 2MB
                            </small>
                        </div>
                        
                        <div id="previewContainer" class="mt-3" style="display:none;">
                            <small class="text-muted d-block mb-2">Visualização da Imagem</small>
                            <div class="preview-box">
                                <img id="previewImg" src="" alt="Preview" class="img-fluid">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Botão Salvar -->
            <div class="form-section mt-4" style="border-top: 1px solid #e9ecef; padding-top: 20px;">
                <button type="submit" class="btn w-100 d-flex align-items-center justify-content-center">
                    <i class="fa-solid fa-check me-2"></i>
                    Salvar Produto
                </button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
    .main { min-height: 60vh; }
    .cabecalho h2 { font-weight:700; }
    .btn-dourado { background: #D4AF37; color: #012E40; font-weight:600; border:none; padding:10px 20px; border-radius:8px; }
    /* Força no máximo 3 colunas por linha em telas maiores, quebra responsiva para 2/1 */
    .grade { display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; }
    @media (max-width: 1100px) {
        .grade { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 700px) {
        .grade { grid-template-columns: 1fr; }
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

    /* Estilos do Modal */
    .modal-dialog { max-width: 600px; }
    .modal-content { 
        background: #ffffff; 
        border: none; 
        border-radius: 20px; 
        box-shadow: 0 15px 50px rgba(0,0,0,0.15); 
    }
    .modal-header { 
        background: linear-gradient(135deg, var(--petroleo) 0%, #034c6a 100%);
        border: none;
        border-radius: 20px 20px 0 0;
        padding: 20px 30px;
    }
    .modal-header .modal-title { 
        color: #fff; 
        font-weight: 600; 
        font-size: 1.25rem;
        letter-spacing: 0.3px;
    }
    .modal-header .btn-close { 
        background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat;
        opacity: 0.8;
        transition: opacity 0.2s;
        padding: 12px;
    }
    .modal-header .btn-close:hover { opacity: 1; }
    .modal-body { 
        padding: 30px; 
        background: #fff;
        border-radius: 0 0 20px 20px;
    }
    .modal-body .form-label { 
        color: #344054; 
        font-weight: 500; 
        font-size: 0.9rem;
        margin-bottom: 6px;
        letter-spacing: 0.3px;
    }
    .modal-body .form-control, 
    .modal-body .form-select { 
        border-radius: 10px; 
        border: 1px solid #E4E7EC;
        padding: 12px 16px;
        font-size: 0.95rem;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.05);
        transition: all 0.2s;
    }
    .modal-body .form-control:focus,
    .modal-body .form-select:focus { 
        border-color: var(--petroleo); 
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.05), 0 0 0 4px rgba(1,46,64,0.08);
    }
    .modal-body textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }
    #previewContainer { 
        background: #F9FAFB; 
        padding: 16px;
        border-radius: 12px;
        border: 1px dashed #E4E7EC;
        text-align: center;
    }
    #previewImg { 
        max-width: 100%;
        height: auto;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .modal-body button[type="submit"] { 
        background: var(--petroleo); 
        color: #fff; 
        border: none; 
        padding: 14px;
        font-weight: 600; 
        font-size: 1rem;
        border-radius: 12px; 
        transition: all 0.2s ease;
        margin-top: 10px;
        letter-spacing: 0.3px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, 0.05);
    }
    .modal-body button[type="submit"]:hover { 
        background: #034c6a; 
        transform: translateY(-1px); 
        box-shadow: 0 6px 20px rgba(1,46,64,0.15);
    }
    .modal-body .input-group-text {
        background: #F9FAFB;
        border: 1px solid #E4E7EC;
        border-radius: 10px;
        padding: 0 16px;
        color: #344054;
        font-weight: 500;
    }
    /* Navegação do Modal */
    .modal-nav {
        background: #f8f9fa;
        padding: 15px 30px;
        border-bottom: 1px solid #e9ecef;
    }
    .modal-nav .nav-pills {
        gap: 10px;
    }
    .modal-nav .nav-link {
        color: #344054;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.2s;
    }
    .modal-nav .nav-link:hover {
        background: rgba(1,46,64,0.05);
    }
    .modal-nav .nav-link.active {
        background: var(--petroleo);
        color: white;
    }
    .modal-body {
        max-height: calc(100vh - 250px);
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }
    .modal-body::-webkit-scrollbar {
        width: 6px;
    }
    .modal-body::-webkit-scrollbar-track {
        background: transparent;
    }
    .modal-body::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 6px;
    }
    .form-section {
        padding: 20px 0;
    }
    .form-section:not(:last-child) {
        border-bottom: 1px solid #e9ecef;
    }
    .form-section-title {
        color: #344054;
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 20px;
    }
    
    /* Animação suave do modal */
    .modal.fade .modal-dialog {
        transform: scale(0.95);
        transition: transform 0.2s ease-out;
    }
    .modal.show .modal-dialog {
        transform: scale(1);
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

    // Função para abrir o modal manualmente
    function abrirModal() {
        modal.style.display = 'flex';
        modal.style.opacity = '1';
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    // Função para fechar o modal manualmente
    function fecharModal() {
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    // Fechar modal ao clicar fora
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            fecharModal();
        }
    });

    // Fechar modal ao clicar no botão X
    modal.querySelector('.btn-close').addEventListener('click', fecharModal);

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

    // Clique no botão Editar
    editarBtns.forEach(btn => {
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

    // Pré-visualização da imagem
    document.getElementById('edit-imagem').addEventListener('change', function(){
        const f = this.files[0];
        if (f) {
            const url = URL.createObjectURL(f);
            previewImg.src = url;
            previewContainer.style.display = 'block';
        }
    });
});
</script>

<style>
/* Mantém o mesmo estilo, apenas corrige exibição do modal sem interferir no CSS */
.modal {
    display: none;
    justify-content: center;
    align-items: center;
    background: rgba(0,0,0,0.6);
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    z-index: 9999;
}
.modal.show {
    display: flex;
    animation: fadeIn 0.25s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
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
             "  document.getElementById('modalProdutoLabel').textContent = 'Editar Produto';\n" .
             "  document.getElementById('acao').value = 'editar';\n" .
             "  document.getElementById('edit-id').value = '" . intval($productToEdit['id']) . "';\n" .
             "  document.getElementById('edit-nome').value = $jsNome;\n" .
             "  document.getElementById('edit-descricao').value = $jsDescricao;\n" .
             "  document.getElementById('edit-quantidade').value = $jsQuantidade;\n" .
             "  document.getElementById('edit-preco').value = $jsPreco;\n" .
             "  document.getElementById('edit-categoria').value = $jsCategoria;\n" .
             ($productToEdit['imagem'] ? "  document.getElementById('previewImg').src = $jsImg; document.getElementById('previewContainer').style.display = 'block';\n" : '') .
             "  var modalEl = document.getElementById('modalProduto'); modalEl.style.display = 'flex'; modalEl.classList.add('show'); document.body.style.overflow = 'hidden';\n" .
             "});</script>";
    }
}
?>

<!-- estilos de alertas movidos para a página dedicada de alertas -->

