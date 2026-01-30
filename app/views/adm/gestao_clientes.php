<?php
// gestao_clientes.php - Consulta de clientes pelo Administrador
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_adm.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

function safe($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Carrega lista de clientes (inspirado em Vendedor/clientes_vendedor.php)
$clientes = [];
try {
    // Tenta obter clientes que já fizeram compras (tabelas venda/pedido)
    $stmt = $conn->prepare("
        SELECT DISTINCT c.id, c.nome, c.email, c.telefone 
        FROM cliente c 
        JOIN venda v ON v.cliente_id = c.id 
        ORDER BY c.nome ASC
    ");
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$clientes) {
        $stmt = $conn->prepare("
            SELECT DISTINCT c.id, c.nome, c.email, c.telefone 
            FROM cliente c 
            JOIN pedido p ON p.cliente_id = c.id 
            ORDER BY c.nome ASC
        ");
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    // Fallback: obtém todos os clientes
    try {
        $stmt = $conn->prepare("SELECT id, nome, email, telefone FROM cliente ORDER BY nome ASC");
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) {
        $clientes = [];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Clientes - ADM | Cantina IPM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background:#f3f4f6; }

        .main-adm-page {
            margin-left: 250px;
            padding: 26px;
        }
        @media (max-width: 767.98px) {
            .main-adm-page {
                margin-left: 0;
                padding: 18px 16px 26px;
            }
        }

        .page-header-admin {
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-wrap:wrap;
            gap:1rem;
            margin-bottom:1.5rem;
        }
        .page-header-admin h2 {
            font-size:1.3rem;
            font-weight:600;
            margin:0;
            color:#0f172a;
        }
        .page-header-admin p {
            font-size:0.9rem;
            color:#6b7280;
            margin:0;
        }

        .card-lista {
            border-radius:16px;
            background:#ffffff;
            padding:0.75rem 0.75rem 0.3rem;
            box-shadow:0 10px 32px rgba(15,23,42,0.06);
            border:1px solid rgba(148,163,184,0.18);
        }

        .linha-cliente {
            padding:0.55rem 0.65rem;
            border-radius:10px;
            display:flex;
            align-items:center;
            gap:0.75rem;
            transition:background 0.15s ease;
        }
        .linha-cliente + .linha-cliente {
            margin-top:2px;
        }
        .linha-cliente:hover {
            background:#f9fafb;
        }

        .avatar-cliente {
            width:40px;
            height:40px;
            border-radius:50%;
            background:#e5e7eb;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:700;
            color:#4b5563;
            overflow:hidden;
        }

        .cliente-nome {
            font-weight:600;
            color:#0f172a;
            font-size:0.95rem;
        }
        .cliente-meta {
            font-size:0.82rem;
            color:#6b7280;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/menu_adm.php'; ?>

<div class="main-adm-page">
    <div class="page-header-admin">
        <div>
            <h2>Gestão de Clientes</h2>
            <p>Consulta dos clientes (alunos/utilizadores) que já realizaram pedidos ou compras.</p>
        </div>
    </div>

    <div class="card-lista">
        <?php if (empty($clientes)): ?>
            <div class="text-center text-muted py-3" style="font-size:0.9rem;">
                Ainda não existem clientes registados ou sem histórico de compras.
            </div>
        <?php else: ?>
            <?php foreach ($clientes as $c): ?>
                <?php
                $nome = $c['nome'] ?? $c['name'] ?? 'Nome desconhecido';
                $iniciais = '';
                if (!empty($nome)) {
                    $partes = preg_split('/\s+/', trim($nome));
                    $iniciais = strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
                }
                ?>
                <div class="linha-cliente">
                    <div class="avatar-cliente">
                        <?= $iniciais ?: 'CL'; ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="cliente-nome"><?= safe($nome); ?></div>
                        <div class="cliente-meta">
                            <?php if (!empty($c['email'])): ?>
                                <?= safe($c['email']); ?>
                            <?php endif; ?>
                            <?php if (!empty($c['telefone'])): ?>
                                <?php if (!empty($c['email'])): ?> · <?php endif; ?>
                                <?= safe($c['telefone']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


