<?php
// gestao_pedidos.php - Visão de pedidos para o Administrador
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

// Carrega pedidos (sem alterar controller de vendedor)
// Aqui o admin apenas consulta, sem ações de atender/cancelar
$pedidos = [];
try {
    $sql = "
        SELECT 
            p.id,
            p.data_pedido,
            p.estado,
            p.forma_pagamento,
            p.total,
            c.nome AS cliente_nome
        FROM pedido p
        LEFT JOIN cliente c ON c.id = p.id_cliente
        ORDER BY p.data_pedido DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $pedidos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Pedidos - ADM | Cantina IPM</title>
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

        .linha-pedido {
            padding:0.7rem 0.75rem;
            border-radius:10px;
            display:grid;
            grid-template-columns:minmax(0, 2fr) minmax(0, 1.5fr) minmax(0, 1.2fr) auto;
            gap:0.75rem;
            align-items:center;
            transition:background 0.15s ease;
            font-size:0.88rem;
        }
        .linha-pedido + .linha-pedido {
            margin-top:2px;
        }
        .linha-pedido:hover {
            background:#f9fafb;
        }
        @media (max-width: 767.98px) {
            .linha-pedido {
                grid-template-columns:1fr;
                align-items:flex-start;
            }
        }

        .pedido-id {
            font-weight:600;
            color:#0f172a;
        }
        .pedido-cliente {
            color:#111827;
            font-weight:500;
        }
        .pedido-sub {
            color:#6b7280;
            font-size:0.8rem;
        }
        .badge-estado {
            font-size:0.75rem;
            border-radius:999px;
            padding:0.2rem 0.7rem;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/menu_adm.php'; ?>

<div class="main-adm-page">
    <div class="page-header-admin">
        <div>
            <h2>Gestão de Pedidos</h2>
            <p>Consulta dos pedidos registados na cantina (independente do vendedor).</p>
        </div>
    </div>

    <div class="card-lista">
        <?php if (empty($pedidos)): ?>
            <div class="text-center text-muted py-3" style="font-size:0.9rem;">
                Ainda não existem pedidos registados.
            </div>
        <?php else: ?>
            <?php foreach ($pedidos as $p): ?>
                <?php
                $estado = strtolower($p['estado'] ?? '');
                $badgeClass = 'bg-secondary-subtle text-secondary';
                $label = ucfirst($estado ?: 'desconhecido');
                if ($estado === 'pendente') {
                    $badgeClass = 'bg-warning-subtle text-warning';
                    $label = 'Pendente';
                } elseif ($estado === 'atendido') {
                    $badgeClass = 'bg-info-subtle text-info';
                    $label = 'Atendido';
                } elseif ($estado === 'finalizado') {
                    $badgeClass = 'bg-success-subtle text-success';
                    $label = 'Finalizado';
                } elseif ($estado === 'cancelado') {
                    $badgeClass = 'bg-danger-subtle text-danger';
                    $label = 'Cancelado';
                }
                ?>
                <div class="linha-pedido">
                    <div>
                        <div class="pedido-id">Pedido #<?= (int)$p['id']; ?></div>
                        <div class="pedido-sub">
                            <?= safe(!empty($p['data_pedido']) ? date('d/m/Y H:i', strtotime($p['data_pedido'])) : 'Data indisponível'); ?>
                        </div>
                    </div>
                    <div>
                        <div class="pedido-cliente">
                            <?= safe($p['cliente_nome']) ?: '<span style="color: #999; font-style: italic;">Sem cliente registado</span>'; ?>
                        </div>
                        <div class="pedido-sub">
                            Forma de pagamento: <?= safe($p['forma_pagamento'] ?? '—'); ?>
                        </div>
                    </div>
                    <div>
                        <div class="fw-semibold">
                            Total: Kz <?= number_format((float)($p['total'] ?? 0), 2, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge badge-estado <?= $badgeClass; ?>">
                            <?= $label; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


