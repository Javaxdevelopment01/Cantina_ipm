<?php
require_once __DIR__ . '/../config/database.php';
include '../includes/menu.php';
include '../includes/header.php';



// Função de segurança para texto
function safe($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];
    $nome = trim($_POST['nome']);
    $quantidade = intval($_POST['quantidade']);
    $preco = floatval(str_replace(',', '.', $_POST['preco']));
    $categoria = intval($_POST['categoria']);
    $imagemDb = null;

    // Upload de imagem se existir
    if (!empty($_FILES['imagem']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/produtos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $newName = time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $dest = $uploadDir . $newName;
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $dest)) {
            $imagemDb = 'uploads/produtos/' . $newName;
        }
    }

    if ($acao === 'cadastrar') {
        $stmt = $conn->prepare("INSERT INTO produto (nome, quantidade, preco, categoria_id, imagem) VALUES (:nome, :qtd, :preco, :cat, :img)");
        $stmt->execute([
            ':nome' => $nome,
            ':qtd' => $quantidade,
            ':preco' => $preco,
            ':cat' => $categoria,
            ':img' => $imagemDb
        ]);
    }

    if ($acao === 'editar' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        if ($imagemDb) {
            $sql = "UPDATE produto SET nome=:nome, quantidade=:qtd, preco=:preco, categoria_id=:cat, imagem=:img WHERE id=:id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nome'=>$nome,
                ':qtd'=>$quantidade,
                ':preco'=>$preco,
                ':cat'=>$categoria,
                ':img'=>$imagemDb,
                ':id'=>$id
            ]);
        } else {
            $sql = "UPDATE produto SET nome=:nome, quantidade=:qtd, preco=:preco, categoria_id=:cat WHERE id=:id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nome'=>$nome,
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

    // Após qualquer ação, recarrega a página
   
}


// Buscar produtos e categorias
$stmt = $conn->prepare("
    SELECT p.*, c.nome AS categoria_nome
    FROM produto p
    LEFT JOIN categoria c ON p.categoria_id = c.id
    ORDER BY p.id DESC
");
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
    <title>Gestão de Produtos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f7f8fa;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .main {
            padding: 30px;
        }

        .cabecalho {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .cabecalho h2 {
            font-weight: 700;
            color: #012E40;
        }

        .btn-dourado {
            background: #D4AF37;
            color: #012E40;
            font-weight: 600;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: 0.2s;
        }

        .btn-dourado:hover {
            background: #e0c44f;
            transform: scale(1.05);
        }

        .grade {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            justify-content: center;
        }

        .produto-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            text-align: center;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            transition: 0.3s ease;
            padding-bottom: 10px;
        }

        .produto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.15);
        }

        .img-produto {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .produto-card h5 {
            margin: 10px 0 5px;
            color: #012E40;
            font-weight: bold;
        }

        .produto-card p {
            margin: 0;
            color: #555;
            font-size: 0.9rem;
        }

        .produto-card strong {
            display: block;
            margin-top: 5px;
            color: #D4AF37;
            font-size: 1.1rem;
        }

        .acoes {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .acoes .btn {
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: 0.2s;
        }

        .acoes .btn:hover {
            transform: scale(1.15);
        }

        /* Modal */
        .modal-header {
            background-color: #012E40;
            color: white;
        }

        .modal-footer button {
            min-width: 100px;
        }
    </style>
</head>
<body>

<div class="main container-fluid">
    <div class="cabecalho">
        <div>
            <h2>Produtos</h2>
            <p>Gestão dos produtos da Cantina IPM</p>
        </div>
        <button class="btn btn-dourado" id="btnNovoProduto">
            <i class="fa-solid fa-plus"></i> Novo Produto
        </button>
    </div>

    <div class="grade">
        <?php if (empty($produtos)): ?>
            <div class="alert alert-light text-center">Nenhum produto encontrado.</div>
        <?php else: ?>
            <?php foreach ($produtos as $p): ?>
                <div class="produto-card">
                    <img src="<?php echo safe(!empty($p['imagem']) ? '../'.$p['imagem'] : 'https://via.placeholder.com/400x300?text=Sem+Imagem'); ?>" class="img-produto">
                    <h5><?php echo safe($p['nome']); ?></h5>
                    <p><?php echo safe($p['categoria_nome']); ?></p>
                    <p>Qtd: <?php echo intval($p['quantidade']); ?></p>
                    <strong>KZ <?php echo number_format($p['preco'], 2, ',', '.'); ?></strong>
                    <div class="acoes">
                        <button class="btn btn-outline-primary btn-editar"
                            data-id="<?php echo $p['id']; ?>"
                            data-nome="<?php echo safe($p['nome']); ?>"
                            data-quantidade="<?php echo $p['quantidade']; ?>"
                            data-preco="<?php echo $p['preco']; ?>"
                            data-categoria="<?php echo $p['categoria_id']; ?>">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Deseja excluir este produto?');">
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                            <button class="btn btn-outline-danger btn-sm">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
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
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formProduto" method="post" enctype="multipart/form-data">
            <input type="hidden" name="acao" id="acao" value="cadastrar">
            <input type="hidden" name="id" id="edit-id">
            <div class="mb-3">
                <label>Nome</label>
                <input type="text" class="form-control" name="nome" id="edit-nome" required>
            </div>
            <div class="mb-3">
                <label>Quantidade</label>
                <input type="number" class="form-control" name="quantidade" id="edit-quantidade" required>
            </div>
            <div class="mb-3">
                <label>Preço</label>
                <input type="text" class="form-control" name="preco" id="edit-preco" required>
            </div>
            <div class="mb-3">
                <label>Categoria</label>
                <select class="form-select" name="categoria" id="edit-categoria" required>
                    <option value="">Selecionar...</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo safe($c['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Imagem</label>
                <input type="file" class="form-control" name="imagem">
            </div>
            <button type="submit" class="btn btn-success w-100">Salvar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const modal = new bootstrap.Modal(document.getElementById('modalProduto'));
    const form = document.getElementById('formProduto');
    const acao = document.getElementById('acao');
    const label = document.getElementById('modalProdutoLabel');

    document.getElementById('btnNovoProduto').addEventListener('click', function(){
        label.textContent = 'Novo Produto';
        acao.value = 'cadastrar';
        form.reset();
        modal.show();
    });

    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', function(){
            label.textContent = 'Editar Produto';
            acao.value = 'editar';
            document.getElementById('edit-id').value = this.dataset.id;
            document.getElementById('edit-nome').value = this.dataset.nome;
            document.getElementById('edit-quantidade').value = this.dataset.quantidade;
            document.getElementById('edit-preco').value = this.dataset.preco;
            document.getElementById('edit-categoria').value = this.dataset.categoria;
            modal.show();
        });
    });
});
</script>
</body>
</html>
