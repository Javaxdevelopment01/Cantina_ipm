<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_adm.php');
    exit;
}
require_once __DIR__ . '/../../../config/database.php';

// Busca histórico com junção para pegar nomes (de forma otimizada ou 2 queries)
// Como admin e vendedor são tabelas diferentes, usaremos UNION ou queries separadas na view? 
// Melhor fazer um LEFT JOIN duplo

$sql = "
    SELECT lh.*, 
           COALESCE(a.nome, v.nome, 'Usuário Removido') as nome
    FROM login_history lh
    LEFT JOIN admin a ON lh.user_type = 'admin' AND lh.user_id = a.id
    LEFT JOIN vendedor v ON lh.user_type = 'vendedor' AND lh.user_id = v.id
    ORDER BY lh.login_time DESC
    LIMIT 100
";
$stmt = $conn->query($sql);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Logins</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <style>
        /* Reusing common admin styles is best, but for now we keep page specific styles inline or normalized */
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

        .container-fluid {
             max-width: 100%; 
             background: white; 
             padding: 30px; 
             border-radius: 10px; 
             box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
        }

        h2 { color: #012E40; border-bottom: 2px solid #D4AF37; padding-bottom: 10px; margin-bottom: 20px; font-size: 1.5rem; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #012E40; font-weight: 600; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; font-weight: bold; }
        .badge-admin { background: #012E40; color: white; }
        .badge-vendedor { background: #D4AF37; color: #012E40; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-failed { background: #f8d7da; color: #721c24; }

        @media (max-width: 768px) {
            .main-adm, .topbar-adm { margin-left: 0; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/menu_adm.php'; ?>

<div class="topbar-adm">
    <div style="font-weight:600; font-size:1.1rem; color:#012E40;">
        <i class="fa-solid fa-clock-rotate-left" style="margin-right:8px; color:#D4AF37;"></i>
        Histórico de Acessos
    </div>
    <div></div>
</div>

<div class="main-adm">
    <div class="container-fluid">
        <h2>Últimos 100 Logins</h2>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Usuário</th>
                        <th>Tipo</th>
                        <th>IP</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i:s', strtotime($log['login_time'])) ?></td>
                        <td><?= htmlspecialchars($log['nome']) ?></td>
                        <td>
                            <span class="badge badge-<?= $log['user_type'] ?>">
                                <?= ucfirst($log['user_type']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                        <td>
                            <span class="badge badge-<?= $log['status'] ?>">
                                <?= ucfirst($log['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="../../assets/js/responsive.js"></script>
</body>
</html>
