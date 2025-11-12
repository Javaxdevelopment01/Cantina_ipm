<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Função de segurança para texto
function safe($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $imagemDb = null;

    // Upload de imagem se existir
    if (!empty($_FILES['imagem']['name'])) {
        $uploadDir = __DIR__ . '/../../uploads/vendedores/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $newName = time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $dest = $uploadDir . $newName;
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $dest)) {
            $imagemDb = 'uploads/vendedores/' . $newName;
        }
    }

    // Hash da senha
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // Inserção no banco
    $stmt = $conn->prepare("INSERT INTO vendedor (nome, email, senha, imagem) VALUES (:nome, :email, :senha, :img)");
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha' => $senhaHash,
        ':img' => $imagemDb
    ]);

    $mensagem = "Vendedor cadastrado com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastro de Vendedor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2>Cadastro de Vendedor</h2>
    <?php if(!empty($mensagem)): ?>
        <div class="alert alert-success"><?= safe($mensagem) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label>Nome</label>
            <input type="text" class="form-control" name="nome" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" class="form-control" name="email" required>
        </div>

        <div class="mb-3">
            <label>Senha</label>
            <input type="password" class="form-control" name="senha" required>
        </div>

        <div class="mb-3">
            <label>Imagem (opcional)</label>
            <input type="file" class="form-control" name="imagem">
        </div>

        <button type="submit" class="btn btn-primary">Cadastrar</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
