<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_adm.php');
    exit;
}
require_once __DIR__ . '/../../../config/database.php';

// Buscar pedidos pendentes com detalhes do usuário
$sql = "
    SELECT r.*, 
           COALESCE(v.nome, a.nome, 'Desconhecido') as nome,
           COALESCE(v.imagem, a.foto_perfil) as foto
    FROM password_reset_requests r
    LEFT JOIN vendedor v ON r.user_type = 'vendedor' AND r.email = v.email
    LEFT JOIN admin a ON r.user_type = 'admin' AND r.email = a.email
    WHERE r.status = 'pending' 
    ORDER BY r.created_at ASC
";
$stmt = $conn->query($sql);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Redefinição de Senha</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f9; margin: 0; }
        
        .main-adm {
            margin-left: 250px;
            padding: 26px;
            transition: margin-left 0.3s ease;
        }

        .topbar-adm {
            margin-left: 250px;
            padding: 14px 26px;
            background: #ffffff;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .container-box { 
            max-width: 100%; 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
        }

        h2 { color: #012E40; border-bottom: 2px solid #D4AF37; padding-bottom: 10px; margin-bottom: 30px; font-size:1.5rem; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        th { background: #f8f9fa; color: #012E40; font-weight: 600; }
        
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; color: white; font-weight: bold; transition: 0.3s; margin-right:5px; }
        .btn-approve { background: #28a745; }
        .btn-approve:hover { background: #218838; }
        .btn-reject { background: #dc3545; }
        .btn-reject:hover { background: #c82333; }
        
        .no-data { text-align: center; color: #666; padding: 30px; }
        .status-msg { margin-bottom: 15px; padding: 10px; border-radius: 5px; display: none; }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #eee; }

        @media (max-width: 768px) {
            .main-adm, .topbar-adm { margin-left: 0; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/menu_adm.php'; ?>

<div class="topbar-adm">
    <div style="font-weight:600; font-size:1.1rem; color:#012E40;">
        <i class="fa-solid fa-key" style="margin-right:8px; color:#D4AF37;"></i>
        Gerir Senhas
    </div>
    <div></div>
</div>

<div class="main-adm">
    <div class="container-box">
        <h2>Solicitações de Redefinição de Senha</h2>
        
        <div id="statusMsg" class="status-msg"></div>

        <?php if (empty($requests)): ?>
            <div class="no-data">
                <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 15px;"></i>
                <p>Nenhuma solicitação pendente.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Usuário</th>
                            <th>Tipo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr id="row-<?= $req['id'] ?>">
                            <td><?= date('d/m/Y H:i', strtotime($req['created_at'])) ?></td>
                            <td>
                                <div class="user-info">
                                    <?php if (!empty($req['foto'])): ?>
                                        <img src="../../../<?= htmlspecialchars($req['foto']) ?>" class="avatar" alt="Foto">
                                    <?php else: ?>
                                        <div class="avatar" style="display:flex;align-items:center;justify-content:center;background:#ccc;color:#666;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight:bold;"><?= htmlspecialchars($req['nome']) ?></div>
                                        <div style="font-size:0.85em;color:#666;"><?= htmlspecialchars($req['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= ucfirst(htmlspecialchars($req['user_type'])) ?></td>
                            <td>
                                <button class="btn btn-approve" onclick="processRequest(<?= $req['id'] ?>, 'approve')">
                                    <i class="fas fa-check"></i> Aprovar
                                </button>
                                <button class="btn btn-reject" onclick="processRequest(<?= $req['id'] ?>, 'reject')">
                                    <i class="fas fa-times"></i> Rejeitar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    async function processRequest(id, action) {
        if (!confirm('Confirma ' + (action === 'approve' ? 'APROVAR' : 'REJEITAR') + ' esta solicitação?')) return;

        try {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', action);

            const response = await fetch('../../api/aprovar_reset.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            const msgBox = document.getElementById('statusMsg');
            
            if (data.success) {
                document.getElementById('row-' + id).remove();
                msgBox.textContent = data.message;
                msgBox.style.background = '#d4edda';
                msgBox.style.color = '#155724';
                msgBox.style.display = 'block';
                
                // Se não houver mais linhas, recarrega para mostrar msg de vazio
                if (document.querySelectorAll('tbody tr').length === 0) {
                    location.reload();
                }
            } else {
                alert('Erro: ' + data.message);
            }
        } catch (e) {
            alert('Erro de conexão.');
        }
    }
</script>
<script src="../../assets/js/responsive.js"></script>
</body>
</html>
