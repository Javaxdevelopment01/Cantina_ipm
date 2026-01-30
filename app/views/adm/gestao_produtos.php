<?php
// gestao_produtos.php - Gestão de produtos pelo Administrador
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_adm.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

function safe($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Processamento de formulário (criar/editar/excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao      = $_POST['acao'];
    $nome      = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $preco      = floatval(str_replace(',', '.', $_POST['preco'] ?? 0));
    $categoria  = intval($_POST['categoria'] ?? 0);
    $imagemDb   = null;

    // Upload de imagem (opcional)
    if (!empty($_FILES['imagem']['name'])) {
        $uploadDir = __DIR__ . '/../../../uploads/produtos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $newName = time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $dest = $uploadDir . $newName;
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $dest)) {
            $imagemDb = 'uploads/produtos/' . $newName;
        }
    }

    if ($acao === 'cadastrar') {
        // Mesmo comportamento do vendedor: se já existir produto com mesmo nome+categoria, soma quantidade
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
                ':id' => $existing['id'],
            ];
            if ($imagemDb) {
                $params[':img'] = $imagemDb;
            }
            $stmt->execute($params);
        } else {
            $stmt = $conn->prepare("INSERT INTO produto (nome, descricao, quantidade, preco, categoria_id, imagem) VALUES (:nome, :descricao, :qtd, :preco, :cat, :img)");
            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao,
                ':qtd' => $quantidade,
                ':preco' => $preco,
                ':cat' => $categoria,
                ':img' => $imagemDb,
            ]);
        }
    }

    if ($acao === 'editar' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        if ($imagemDb) {
            $sql = "UPDATE produto SET nome=:nome, descricao=:descricao, quantidade=:qtd, preco=:preco, categoria_id=:cat, imagem=:img WHERE id=:id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao,
                ':qtd' => $quantidade,
                ':preco' => $preco,
                ':cat' => $categoria,
                ':img' => $imagemDb,
                ':id' => $id,
            ]);
        } else {
            $sql = "UPDATE produto SET nome=:nome, descricao=:descricao, quantidade=:qtd, preco=:preco, categoria_id=:cat WHERE id=:id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':descricao' => $descricao,
                ':qtd' => $quantidade,
                ':preco' => $preco,
                ':cat' => $categoria,
                ':id' => $id,
            ]);
        }
    }

    if ($acao === 'excluir' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM produto WHERE id=:id");
        $stmt->execute([':id' => $id]);
    }

    header('Location: gestao_produtos.php');
    exit;
}

// Listagem de produtos e categorias
$stmt = $conn->prepare("SELECT p.*, c.nome AS categoria_nome FROM produto p LEFT JOIN categoria c ON p.categoria_id = c.id ORDER BY p.id DESC");
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT id, nome FROM categoria ORDER BY nome ASC");
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Produtos - ADM | Cantina IPM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f3f4f6;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            color: #1f2937;
            line-height: 1.5;
        }

        .main-adm-page {
            margin-left: 250px;
            padding: 26px;
            min-height: 100vh;
        }

        @media (max-width: 767.98px) {
            .main-adm-page {
                margin-left: 0;
                padding: 18px 16px 26px;
            }
        }

        .page-header-admin {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #012E40 0%, #034c6a 100%);
            padding: 1.5rem;
            border-radius: 12px;
            color: white;
        }

        .page-header-admin > div:first-child {
            flex: 1;
        }

        .page-header-admin h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.3rem 0;
            color: #ffffff;
        }

        .page-header-admin p {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.9);
            margin: 0;
        }

        .btn-novo-produto {
            border-radius: 8px;
            padding: 0.6rem 1.4rem;
            font-weight: 600;
            background: linear-gradient(135deg, #D4AF37, #f3d26a);
            border: none;
            color: #012E40;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn-novo-produto:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
        }

        .btn-novo-produto i {
            font-size: 0.95rem;
        }

        .card-filtro {
            border-radius: 12px;
            background: #ffffff;
            padding: 1.2rem;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(148, 163, 184, 0.2);
            margin-bottom: 2rem;
        }

        .produto-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }

        @media (max-width: 1200px) {
            .produto-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .produto-grid {
                grid-template-columns: 1fr;
            }
        }

        .produto-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .produto-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.15);
        }

        .produto-card img {
            width: 100%;
            height: 170px;
            object-fit: cover;
        }

        .produto-card-body {
            padding: 0.9rem 1rem 0.75rem;
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
        }

        .produto-titulo {
            font-size: 0.98rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.15rem;
        }

        .produto-categoria {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .produto-meta {
            font-size: 0.78rem;
            color: #6b7280;
            margin-top: 0.3rem;
        }

        .produto-preco {
            font-weight: 700;
            color: #D4AF37;
            font-size: 1rem;
        }

        .produto-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.4rem;
            margin-top: auto;
        }

        .btn-icon-sm {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
        }

        .btn-edit {
            background: #0f172a;
            color: #f9fafb;
        }

        .btn-delete {
            background: #dc2626;
            color: #fef2f2;
        }

        .modal-admin .modal-content {
            border-radius: 18px;
            border: none;
            box-shadow: 0 18px 46px rgba(15, 23, 42, 0.18);
        }

        .modal-admin .modal-header {
            background: linear-gradient(135deg, #012E40, #034c6a);
            color: #ffffff;
            border: none;
            border-radius: 18px 18px 0 0;
        }

        .modal-admin .modal-header .btn-close {
            filter: invert(1);
            opacity: 0.8;
        }

        .modal-admin .modal-header .btn-close:hover {
            opacity: 1;
        }

        /* Estilos para campos de filtro - CSS puro */
        .filtro-input, .filtro-select {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            font-family: inherit;
            background-color: #ffffff;
            color: #1f2937;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .filtro-input:focus, .filtro-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .filtro-input::placeholder {
            color: #9ca3af;
        }

        .filtro-select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%231f2937' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
            padding-right: 2.5rem;
        }

        .filtro-select:disabled {
            background-color: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .filtro-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }

        .filtro-campo {
            flex: 1;
            min-width: 150px;
        }

        .filtro-campo.nome {
            min-width: 200px;
        }

        .filtro-campo.categoria {
            min-width: 140px;
        }

        .filtro-campo.stock {
            min-width: 120px;
        }

        .filtro-total {
            margin-left: auto;
            font-size: 0.82rem;
            color: #6b7280;
            white-space: nowrap;
        }

        .filtro-total strong {
            color: #0f172a;
            font-weight: 600;
        }

        @media (max-width: 900px) {
            .filtro-campo {
                min-width: 120px;
            }

            .filtro-campo.nome {
                min-width: 100%;
            }

            .filtro-total {
                width: 100%;
                margin-left: 0;
                margin-top: 0.5rem;
            }
        }

        .empty-state {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            color: #6b7280;
            font-size: 1rem;
            margin: 2rem 0;
        }

        /* Modal styles - CSS puro */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
        }

        .modal-content {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #012E40, #034c6a);
            color: #ffffff;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: none;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #ffffff;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease;
        }

        .modal-close:hover {
            transform: scale(1.2);
        }

        .modal-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #0f172a;
            font-size: 0.95rem;
        }

        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 0.7rem 0.9rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            color: #1f2937;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            box-sizing: border-box;
        }

        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%231f2937' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.9rem center;
            background-size: 16px 12px;
            padding-right: 2.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                border-radius: 12px;
            }
        }

        .form-input-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-input-group .form-input {
            margin-bottom: 0;
            flex: 1;
        }

        .form-input-addon {
            padding: 0.7rem 0.9rem;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            color: #6b7280;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-help-text {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 0.3rem;
        }

        .preview-container {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            display: none;
            background: #f9fafb;
        }

        .preview-container.active {
            display: block;
        }

        .preview-container img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #012E40, #034c6a);
            color: #ffffff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(1, 46, 64, 0.3);
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .full-width {
            width: 100%;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/menu_adm.php'; ?>

<div class="main-adm-page">
    <div class="page-header-admin">
        <div>
            <h2>Gestão de Produtos</h2>
            <p>Administra todos os produtos da cantina: criação, edição e remoção.</p>
        </div>
        <button type="button" class="btn btn-novo-produto" id="btnNovoModelo">
            <i class="fa-solid fa-plus"></i> Novo produto
        </button>
    </div>

    <div class="card-filtro">
        <form class="filtro-form">
            <div class="filtro-campo nome">
                <input type="text" class="filtro-input" id="filtroNome" placeholder="Procurar por nome...">
            </div>
            <div class="filtro-campo categoria">
                <select id="filtroCategoria" class="filtro-select">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id']; ?>"><?= safe($c['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtro-campo stock">
                <select id="filtroStock" class="filtro-select">
                    <option value="">Stock</option>
                    <option value="baixo">Abaixo de 5</option>
                    <option value="zero">Esgotado</option>
                </select>
            </div>
            <div class="filtro-total">
                Total: <strong id="totalProdutosSpan"><?= count($produtos); ?></strong> produto(s)
            </div>
        </form>
    </div>

    <?php if (empty($produtos)): ?>
        <div class="empty-state">Ainda não existem produtos cadastrados.</div>
    <?php else: ?>
        <div class="produto-grid" id="produtoGrid">
            <?php foreach ($produtos as $p): ?>
                <?php
                $imgSrc = !empty($p['imagem'])
                    ? (strpos($p['imagem'], '/') === 0 ? $p['imagem'] : '/' . $p['imagem'])
                    : 'https://via.placeholder.com/400x300?text=Sem+Imagem';
                ?>
                <div class="produto-card"
                     data-nome="<?= strtolower(safe($p['nome'])); ?>"
                     data-categoria="<?= intval($p['categoria_id']); ?>"
                     data-quantidade="<?= intval($p['quantidade']); ?>">
                    <img src="<?= safe($imgSrc); ?>" alt="<?= safe($p['nome']); ?>">
                    <div class="produto-card-body">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <div class="produto-titulo"><?= safe($p['nome']); ?></div>
                                <div class="produto-categoria"><?= safe($p['categoria_nome'] ?: 'Sem categoria'); ?></div>
                            </div>
                            <div class="text-end">
                                <div class="produto-preco">KZ <?= number_format($p['preco'], 2, ',', '.'); ?></div>
                                <div class="produto-meta">Qtd: <?= intval($p['quantidade']); ?></div>
                            </div>
                        </div>
                        <?php if (intval($p['quantidade']) <= 5): ?>
                            <div class="produto-meta text-danger">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>Stock baixo
                            </div>
                        <?php endif; ?>
                        <div class="produto-actions mt-3">
                            <button
                                type="button"
                                class="btn-icon-sm btn-edit btn-editar"
                                data-id="<?= $p['id']; ?>"
                                data-nome="<?= safe($p['nome']); ?>"
                                data-descricao="<?= safe($p['descricao']); ?>"
                                data-quantidade="<?= $p['quantidade']; ?>"
                                data-preco="<?= $p['preco']; ?>"
                                data-categoria="<?= $p['categoria_id']; ?>"
                                data-img="<?= $imgSrc; ?>"
                            >
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <form method="post" onsubmit="return confirm('Deseja excluir este produto?');">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?= $p['id']; ?>">
                                <button type="submit" class="btn-icon-sm btn-delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de criação/edição de produto (CSS Puro) -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitulo">Novo produto</h2>
            <button type="button" class="modal-close" id="btnFecharModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="formProdutoAdm" method="post" enctype="multipart/form-data">
                <input type="hidden" name="acao" id="acaoAdm" value="cadastrar">
                <input type="hidden" name="id" id="produtoIdAdm">

                <div class="form-row">
                    <div>
                        <div class="form-group">
                            <label class="form-label">Nome do produto</label>
                            <input type="text" class="form-input" name="nome" id="nomeAdm" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Categoria</label>
                            <select class="form-select" name="categoria" id="categoriaAdm" required>
                                <option value="" disabled selected>Selecionar categoria...</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= $c['id']; ?>"><?= safe($c['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <div>
                                <label class="form-label">Quantidade</label>
                                <div class="form-input-group">
                                    <input type="number" min="0" class="form-input" name="quantidade" id="quantidadeAdm" required>
                                    <span class="form-input-addon">un</span>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Preço</label>
                                <div class="form-input-group">
                                    <span class="form-input-addon">KZ</span>
                                    <input type="text" class="form-input" name="preco" id="precoAdm" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label class="form-label">Imagem do produto</label>
                            <input type="file" class="form-input" name="imagem" id="imagemAdm" accept="image/*">
                            <p class="form-help-text">Formatos: JPG, PNG. Máx: 2MB.</p>
                        </div>
                        <div id="previewContainerAdm" class="preview-container">
                            <p style="color: #6b7280; margin-bottom: 0.5rem; font-size: 0.85rem;">Pré-visualização</p>
                            <img id="previewImgAdm" src="" alt="Preview">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <textarea class="form-textarea" name="descricao" id="descricaoAdm" placeholder="Descrição detalhada do produto..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="btnCancelar">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnSalvar">
                <i class="fa-solid fa-check"></i> Guardar produto
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Controle do Modal - CSS Puro
    (function () {
        const modalOverlay = document.getElementById('modalOverlay');
        const btnNovoModelo = document.getElementById('btnNovoModelo');
        const btnFecharModal = document.getElementById('btnFecharModal');
        const btnCancelar = document.getElementById('btnCancelar');
        const btnSalvar = document.getElementById('btnSalvar');
        const formProdutoAdm = document.getElementById('formProdutoAdm');

        function abrirModal() {
            modalOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function fecharModal() {
            modalOverlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Abrir modal
        if (btnNovoModelo) {
            btnNovoModelo.addEventListener('click', abrirModal);
        }

        // Fechar modal
        if (btnFecharModal) {
            btnFecharModal.addEventListener('click', fecharModal);
        }

        if (btnCancelar) {
            btnCancelar.addEventListener('click', fecharModal);
        }

        // Fechar ao clicar no overlay
        if (modalOverlay) {
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) {
                    fecharModal();
                }
            });
        }

        // Enviar form
        if (btnSalvar) {
            btnSalvar.addEventListener('click', () => {
                if (formProdutoAdm) {
                    formProdutoAdm.submit();
                }
            });
        }

        // Fechar com tecla Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalOverlay.classList.contains('active')) {
                fecharModal();
            }
        });
    })();

    // Filtragem simples no frontend
    (function () {
        const grid = document.getElementById('produtoGrid');
        if (!grid) return;

        const filtroNome = document.getElementById('filtroNome');
        const filtroCategoria = document.getElementById('filtroCategoria');
        const filtroStock = document.getElementById('filtroStock');
        const totalSpan = document.getElementById('totalProdutosSpan');

        function aplicarFiltros() {
            const nomeVal = (filtroNome.value || '').toLowerCase();
            const catVal = filtroCategoria.value;
            const stockVal = filtroStock.value;
            let visiveis = 0;

            grid.querySelectorAll('.produto-card').forEach(card => {
                const cardNome = card.dataset.nome || '';
                const cardCat = card.dataset.categoria || '';
                const cardQtd = parseInt(card.dataset.quantidade || '0', 10);
                let mostrar = true;

                if (nomeVal && !cardNome.includes(nomeVal)) {
                    mostrar = false;
                }
                if (catVal && cardCat !== catVal) {
                    mostrar = false;
                }
                if (stockVal === 'baixo' && !(cardQtd > 0 && cardQtd <= 5)) {
                    mostrar = false;
                }
                if (stockVal === 'zero' && cardQtd !== 0) {
                    mostrar = false;
                }

                card.style.display = mostrar ? '' : 'none';
                if (mostrar) visiveis++;
            });

            if (totalSpan) {
                totalSpan.textContent = visiveis;
            }
        }

        [filtroNome, filtroCategoria, filtroStock].forEach(el => {
            if (el) el.addEventListener('input', aplicarFiltros);
        });
    })();

    // Modal ADM: preencher para editar / limpar para novo
    (function () {
        const form = document.getElementById('formProdutoAdm');
        const acao = document.getElementById('acaoAdm');
        const idField = document.getElementById('produtoIdAdm');
        const nomeField = document.getElementById('nomeAdm');
        const descricaoField = document.getElementById('descricaoAdm');
        const quantidadeField = document.getElementById('quantidadeAdm');
        const precoField = document.getElementById('precoAdm');
        const categoriaField = document.getElementById('categoriaAdm');
        const imagem = document.getElementById('imagemAdm');
        const previewContainer = document.getElementById('previewContainerAdm');
        const previewImg = document.getElementById('previewImgAdm');
        const modalTitle = document.getElementById('modalTitulo');

        // Quando clica no botão de editar do card
        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const nome = this.dataset.nome;
                const descricao = this.dataset.descricao;
                const quantidade = this.dataset.quantidade;
                const preco = this.dataset.preco;
                const categoria = this.dataset.categoria;
                const img = this.dataset.img;

                modalTitle.textContent = 'Editar produto';
                acao.value = 'editar';
                idField.value = id;
                nomeField.value = nome;
                descricaoField.value = descricao;
                quantidadeField.value = quantidade;
                precoField.value = preco;
                categoriaField.value = categoria;

                if (img && img !== 'https://via.placeholder.com/400x300?text=Sem+Imagem') {
                    previewImg.src = img;
                    previewContainer.classList.add('active');
                } else {
                    previewContainer.classList.remove('active');
                }

                document.getElementById('modalOverlay').classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        });

        // Quando clica no botão "Novo produto" limpamos o form
        const btnNovoModelo = document.getElementById('btnNovoModelo');
        if (btnNovoModelo) {
            btnNovoModelo.addEventListener('click', () => {
                acao.value = 'cadastrar';
                modalTitle.textContent = 'Novo produto';
                form.reset();
                idField.value = '';
                previewContainer.classList.remove('active');
            });
        }

        // Preview da imagem
        if (imagem) {
            imagem.addEventListener('change', () => {
                const f = imagem.files[0];
                if (f) {
                    const url = URL.createObjectURL(f);
                    previewImg.src = url;
                    previewContainer.classList.add('active');
                }
            });
        }
    })();
</script>
</body>
</html>
