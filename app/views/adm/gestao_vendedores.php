<?php
// gestao_vendedores.php - Gestão de vendedores pelo Administrador
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_adm.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

function safe($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Processamento de formulário: criar, editar, ativar/inativar, excluir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    if ($acao === 'criar' || $acao === 'editar') {
        $id    = isset($_POST['id']) ? intval($_POST['id']) : null;
        $nome  = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $status = $_POST['status'] ?? 'ativo';
        $imagemDb = null;

        // Upload da imagem (opcional)
        if (!empty($_FILES['imagem']['name'])) {
            $uploadDir = __DIR__ . '/../../uploads/vendedores/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            $newName = time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
            $dest = $uploadDir . $newName;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $dest)) {
                $imagemDb = 'uploads/vendedores/' . $newName;
            }
        }

        if ($acao === 'criar') {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO vendedor (nome, email, senha, imagem, status) VALUES (:nome, :email, :senha, :img, :status)");
            $stmt->execute([
                ':nome'   => $nome,
                ':email'  => $email,
                ':senha'  => $senhaHash,
                ':img'    => $imagemDb,
                ':status' => $status,
            ]);
        } elseif ($acao === 'editar' && $id) {
            // Atualiza com ou sem alteração de senha/imagem
            $fields = [
                'nome'   => $nome,
                'email'  => $email,
                'status' => $status,
            ];
            $setSql = "nome = :nome, email = :email, status = :status";

            if (!empty($senha)) {
                $fields['senha'] = password_hash($senha, PASSWORD_DEFAULT);
                $setSql .= ", senha = :senha";
            }
            if ($imagemDb) {
                $fields['imagem'] = $imagemDb;
                $setSql .= ", imagem = :imagem";
            }

            $fields['id'] = $id;

            $sql = "UPDATE vendedor SET $setSql WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array_combine(
                array_map(function ($k) { return ':' . $k; }, array_keys($fields)),
                array_values($fields)
            ));
        }
    }

    if ($acao === 'toggle' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE vendedor SET status = IF(status = 'ativo','inativo','ativo') WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    if ($acao === 'excluir' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM vendedor WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    header('Location: gestao_vendedores.php');
    exit;
}

// Carrega vendedores existentes
try {
    $stmt = $conn->prepare("SELECT id, nome, email, imagem, status, created_at FROM vendedor ORDER BY nome ASC");
    $stmt->execute();
    $vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Caso a coluna status ou created_at não exista ainda, faz um select mais simples
    $stmt = $conn->prepare("SELECT id, nome, email, imagem, 'ativo' AS status, NULL AS created_at FROM vendedor ORDER BY nome ASC");
    $stmt->execute();
    $vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Vendedores - ADM | Cantina IPM</title>
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

        .btn-novo-vendedor {
            border-radius:999px;
            padding-inline:1.3rem;
            font-weight:600;
            background:linear-gradient(135deg,#012E40,#034c6a);
            border:none;
            color:#f9fafb;
            display:inline-flex;
            align-items:center;
            gap:0.5rem;
        }

        .card-lista {
            border-radius:16px;
            background:#ffffff;
            padding:0.75rem 0.75rem 0.3rem;
            box-shadow:0 10px 32px rgba(15,23,42,0.06);
            border:1px solid rgba(148,163,184,0.18);
        }

        .linha-vendedor {
            padding:0.55rem 0.65rem;
            border-radius:10px;
            display:flex;
            align-items:center;
            gap:0.75rem;
            transition:background 0.15s ease;
        }
        .linha-vendedor + .linha-vendedor {
            margin-top:2px;
        }
        .linha-vendedor:hover {
            background:#f9fafb;
        }

        .avatar-vendedor {
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
        .avatar-vendedor img {
            width:100%;
            height:100%;
            object-fit:cover;
        }

        .vendedor-nome {
            font-weight:600;
            color:#0f172a;
            font-size:0.95rem;
        }
        .vendedor-email {
            font-size:0.82rem;
            color:#6b7280;
        }

        .badge-status {
            font-size:0.75rem;
            border-radius:999px;
            padding:0.15rem 0.7rem;
        }

        .actions-vendedor {
            display:flex;
            gap:0.35rem;
        }
        .btn-icon-xs {
            width:32px;
            height:32px;
            border-radius:999px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border:none;
        }
        .btn-edit { background:#0f172a; color:#f9fafb; }
        .btn-toggle { background:#6b7280; color:#f9fafb; }
        .btn-delete { background:#dc2626; color:#fef2f2; }

        .modal-admin .modal-content {
            border-radius:18px;
            border:none;
            box-shadow:0 18px 46px rgba(15,23,42,0.18);
        }
        .modal-admin .modal-header {
            background:linear-gradient(135deg,#012E40,#034c6a);
            color:#ffffff;
            border:none;
            border-radius:18px 18px 0 0;
        }
        .modal-admin .modal-header .btn-close {
            filter:invert(1);
            opacity:0.8;
        }
        .modal-admin .modal-header .btn-close:hover { opacity:1; }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/menu_adm.php'; ?>

<div class="main-adm-page">
    <div class="page-header-admin">
        <div>
            <h2>Gestão de Vendedores</h2>
            <p>Cria, edita e controla o acesso dos vendedores ao sistema.</p>
        </div>
        <button type="button" class="btn btn-novo-vendedor" data-bs-toggle="modal" data-bs-target="#modalVendedor">
            <i class="fa-solid fa-user-plus"></i> Novo vendedor
        </button>
    </div>

    <div class="card-lista">
        <?php if (empty($vendedores)): ?>
            <div class="text-center text-muted py-3" style="font-size:0.9rem;">
                Ainda não existem vendedores registrados.
            </div>
        <?php else: ?>
            <?php foreach ($vendedores as $v): ?>
                <?php
                // Caminho da imagem igual ao módulo do vendedor: BD guarda "uploads/vendedores/ficheiro.jpg"
                // e no HTML usamos "/app/{$v['imagem']}"
                $img = (!empty($v['imagem'])) ? $v['imagem'] : null;
                $iniciais = '';
                if (!empty($v['nome'])) {
                    $partes = preg_split('/\s+/', trim($v['nome']));
                    $iniciais = strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
                }
                ?>
                <div class="linha-vendedor">
                    <div class="avatar-vendedor">
                        <?php if ($img): ?>
                            <img src="/app/<?= safe($img); ?>" alt="<?= safe($v['nome']); ?>">
                        <?php else: ?>
                            <?= $iniciais ?: 'VD'; ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="vendedor-nome"><?= safe($v['nome']); ?></div>
                        <div class="vendedor-email"><?= safe($v['email']); ?></div>
                    </div>
                    <div class="me-2 text-end">
                        <span class="badge badge-status <?= ($v['status'] ?? 'ativo') === 'ativo' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'; ?>">
                            <?= ($v['status'] ?? 'ativo') === 'ativo' ? 'Ativo' : 'Inativo'; ?>
                        </span>
                        <?php if (!empty($v['created_at'])): ?>
                            <div class="text-muted" style="font-size:0.75rem;">
                                desde <?= safe(date('d/m/Y', strtotime($v['created_at']))); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="actions-vendedor">
                        <button
                            type="button"
                            class="btn-icon-xs btn-edit btn-editar"
                            data-id="<?= $v['id']; ?>"
                            data-nome="<?= safe($v['nome']); ?>"
                            data-email="<?= safe($v['email']); ?>"
                            data-status="<?= safe($v['status'] ?? 'ativo'); ?>"
                        >
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="post">
                            <input type="hidden" name="acao" value="toggle">
                            <input type="hidden" name="id" value="<?= $v['id']; ?>">
                            <button type="submit" class="btn-icon-xs btn-toggle" title="Ativar/Inativar">
                                <i class="fa-solid fa-user-slash"></i>
                            </button>
                        </form>
                        <form method="post" onsubmit="return confirm('Tens a certeza que desejas apagar este vendedor?');">
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" value="<?= $v['id']; ?>">
                            <button type="submit" class="btn-icon-xs btn-delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal criar/editar vendedor -->
<div class="modal fade modal-admin" id="modalVendedor" tabindex="-1" aria-labelledby="modalVendedorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVendedorLabel">Novo vendedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form method="post" enctype="multipart/form-data" id="formVendedor">
                    <input type="hidden" name="acao" id="acaoVendedor" value="criar">
                    <input type="hidden" name="id" id="idVendedor">

                    <div class="mb-3">
                        <label class="form-label">Nome completo</label>
                        <input type="text" class="form-control" name="nome" id="nomeVendedor" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="emailVendedor" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Senha de acesso</label>
                        <input type="password" class="form-control" name="senha" id="senhaVendedor" placeholder="Define a senha (deixa vazio para não alterar ao editar)" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto de perfil (opcional)</label>
                        <input type="file" class="form-control" name="imagem" accept="image/*">
                        <small class="text-muted">Aparecerá no painel de vendedor.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="status" id="statusVendedor">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                        </select>
                    </div>

                    <div class="mt-4 d-grid">
                        <button type="submit" class="btn btn-dark">
                            <i class="fa-solid fa-check me-2"></i> Guardar vendedor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        const modalEl = document.getElementById('modalVendedor');
        if (!modalEl) return;

        const form = document.getElementById('formVendedor');
        const acao = document.getElementById('acaoVendedor');
        const idField = document.getElementById('idVendedor');
        const nome = document.getElementById('nomeVendedor');
        const email = document.getElementById('emailVendedor');
        const senha = document.getElementById('senhaVendedor');
        const status = document.getElementById('statusVendedor');
        const modalTitle = document.getElementById('modalVendedorLabel');

        const bsModal = new bootstrap.Modal(modalEl);

        // Quando clicar em "Novo vendedor"
        modalEl.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (trigger && trigger.matches('.btn-novo-vendedor')) {
                acao.value = 'criar';
                modalTitle.textContent = 'Novo vendedor';
                form.reset();
                idField.value = '';
                senha.required = true;
            }
        });

        // Ao clicar em editar
        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', () => {
                acao.value = 'editar';
                modalTitle.textContent = 'Editar vendedor';
                idField.value = btn.dataset.id || '';
                nome.value = btn.dataset.nome || '';
                email.value = btn.dataset.email || '';
                status.value = btn.dataset.status || 'ativo';
                senha.value = '';
                senha.required = false; // Não obriga senha ao editar
                bsModal.show();
            });
        });
    })();
</script>
</body>
</html>


