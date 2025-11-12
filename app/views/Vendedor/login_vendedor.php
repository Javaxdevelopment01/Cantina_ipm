<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';


$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    $stmt = $conn->prepare("SELECT * FROM vendedor WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $vendedor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($vendedor && password_verify($senha, $vendedor['senha'])) {
        $_SESSION['vendedor_id'] = $vendedor['id'];
        $_SESSION['vendedor_nome'] = $vendedor['nome'];
        $_SESSION['vendedor_imagem'] = $vendedor['imagem'];
        header('Location: dashboard_vendedor.php');
        exit;
    } else {
        $erro = 'Email ou senha inválidos.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Vendedor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f7f8fa; font-family: 'Segoe UI', sans-serif; }
.card { padding: 2rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.btn-dourado { background: #D4AF37; color: #012E40; font-weight: 600; border: none; width: 100%; padding: 10px; border-radius: 8px; }
.btn-dourado:hover { background: #e0c44f; }
</style>
</head>
<body>
<div class="card col-md-4">
    <h3 class="mb-4 text-center" style="color:#012E40;">Login Vendedor</h3>
    <?php if($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label>Email</label>
            <input type="email" class="form-control" name="email" required>
        </div>
        <div class="mb-3">
            <label>Senha</label>
            <input type="password" class="form-control" name="senha" required>
        </div>
        <button type="submit" class="btn-dourado">Entrar</button>
    </form>
</div>
</body>
</html>
