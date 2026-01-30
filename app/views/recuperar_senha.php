<?php
$tipo = $_GET['tipo'] ?? 'vendedor'; // 'admin' ou 'vendedor'
$titulo = ($tipo === 'admin') ? 'Admin' : 'Vendedor';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - <?= htmlspecialchars($titulo) ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: linear-gradient(135deg, #012E40 0%, #1a4d66 100%); margin: 0; }
        .card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; max-width: 400px; text-align: center; }
        h2 { color: #012E40; margin-bottom: 20px; }
        p { color: #666; margin-bottom: 30px; font-size: 0.9em; }
        input { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #D4AF37; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        button:hover { background: #c49621; }
        .back-link { display: block; margin-top: 15px; color: #012E40; text-decoration: none; font-size: 0.9em; }
        .alert { padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 0.9em; display: none; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Recuperar Senha</h2>
        <p>Digite seu e-mail cadastrado para receber um link de redefinição.</p>
        
        <div id="message" class="alert"></div>

        <form id="resetForm">
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
            <input type="email" name="email" placeholder="Seu e-mail" required>
            <button type="submit">Enviar Link</button>
        </form>

        <a href="javascript:history.back()" class="back-link">Voltar para Login</a>
    </div>

    <script>
        document.getElementById('resetForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            const msg = document.getElementById('message');
            
            btn.disabled = true;
            btn.textContent = 'Enviando...';
            msg.style.display = 'none';

            try {
                const formData = new FormData(this);
                const response = await fetch('../api/enviar_recuperacao.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                msg.textContent = data.message;
                msg.className = 'alert ' + (data.success ? 'alert-success' : 'alert-error');
                msg.style.display = 'block';
                
                if (data.success) {
                    this.reset();
                }
            } catch (err) {
                msg.textContent = 'Erro ao conectar com o servidor.';
                msg.className = 'alert alert-error';
                msg.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.textContent = 'Enviar Link';
            }
        });
    </script>
</body>
</html>
