<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_adm.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

function safe($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Lista backups já existentes
$backupDir = __DIR__ . '/../../../backups/';
$backups = [];
if (is_dir($backupDir)) {
    foreach (scandir($backupDir) as $file) {
        if ($file === '.' || $file === '..') continue;
        if (substr($file, -4) !== '.zip') continue;
        $full = $backupDir . $file;
        $backups[] = [
            'name' => $file,
            'size' => filesize($full),
            'mtime' => filemtime($full),
        ];
    }
    usort($backups, fn($a,$b) => $b['mtime'] <=> $a['mtime']);
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backups - ADM | Cantina IPM</title>
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

        .card-backup {
            border-radius:16px;
            background:#ffffff;
            padding:1.5rem;
            box-shadow:0 10px 32px rgba(15,23,42,0.06);
            border:1px solid rgba(148,163,184,0.18);
            margin-bottom: 1.5rem;
        }

        .card-backup h6 {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 1rem;
        }

        .backup-option {
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .backup-option:hover {
            border-color: #012E40;
            background: #f9fafb;
        }

        .backup-option.selected {
            border-color: #012E40;
            background: #f0f9ff;
        }

        .backup-option input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #012E40;
        }

        .backup-option-label {
            display: flex;
            align-items: start;
            gap: 1rem;
            cursor: pointer;
        }

        .backup-option-icon {
            font-size: 1.5rem;
            color: #012E40;
            margin-top: 0.2rem;
        }

        .backup-option-content {
            flex: 1;
        }

        .backup-option-title {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .backup-option-desc {
            font-size: 0.85rem;
            color: #6b7280;
            margin: 0;
        }

        .btn-backup-run {
            border-radius:999px;
            padding: 0.75rem 1.5rem;
            font-weight:600;
            background:linear-gradient(135deg,#012E40,#034c6a);
            border:none;
            color:#f9fafb;
            display:inline-flex;
            align-items:center;
            gap:0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-backup-run:hover {
            background:linear-gradient(135deg,#034c6a,#012E40);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(1,46,64,0.3);
        }

        .btn-backup-run:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .info-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #dbeafe;
            color: #1e40af;
            margin-left: 0.5rem;
        }

        .progress-container {
            display: none;
            margin-top: 1rem;
        }

        .progress {
            height: 8px;
            border-radius: 999px;
            background: #e5e7eb;
        }

        .progress-bar {
            background: linear-gradient(90deg, #012E40, #034c6a);
            border-radius: 999px;
        }

        .backup-success {
            display: none;
            padding: 1rem;
            border-radius: 12px;
            background: #d1fae5;
            border: 1px solid #6ee7b7;
            color: #065f46;
            margin-top: 1rem;
        }

        .backup-success i {
            color: #059669;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/menu_adm.php'; ?>

<div class="main-adm-page">
    <div class="page-header-admin">
        <div>
            <h2>Backups do Sistema</h2>
            <p>Crie backups completos ou seletivos do sistema da cantina</p>
        </div>
    </div>

    <!-- Card de Seleção de Backup -->
    <div class="card-backup">
        <h6><i class="fa-solid fa-sliders me-2"></i>Selecione o que incluir no backup</h6>
        
        <div class="backup-option selected" onclick="toggleOption(this, 'database')">
            <label class="backup-option-label">
                <input type="checkbox" id="opt-database" checked onchange="updateSelection()">
                <i class="fa-solid fa-database backup-option-icon"></i>
                <div class="backup-option-content">
                    <div class="backup-option-title">
                        Base de Dados (Principal)
                        <span class="info-badge">Recomendado</span>
                    </div>
                    <p class="backup-option-desc">
                        Produtos, vendas, usuários, clientes, relatórios financeiros, histórico e logs do sistema
                    </p>
                </div>
            </label>
        </div>

        <div class="backup-option" onclick="toggleOption(this, 'uploads')">
            <label class="backup-option-label">
                <input type="checkbox" id="opt-uploads" onchange="updateSelection()">
                <i class="fa-solid fa-cloud-arrow-up backup-option-icon"></i>
                <div class="backup-option-content">
                    <div class="backup-option-title">Uploads de Administradores e Vendedores</div>
                    <p class="backup-option-desc">
                        Imagens de perfil, documentos e ficheiros enviados pelos utilizadores do sistema
                    </p>
                </div>
            </label>
        </div>

        <div class="backup-option" onclick="toggleOption(this, 'products')">
            <label class="backup-option-label">
                <input type="checkbox" id="opt-products" onchange="updateSelection()">
                <i class="fa-solid fa-images backup-option-icon"></i>
                <div class="backup-option-content">
                    <div class="backup-option-title">Imagens de Produtos</div>
                    <p class="backup-option-desc">
                        Todas as fotografias dos produtos disponíveis na cantina
                    </p>
                </div>
            </label>
        </div>

        <div class="backup-option" onclick="toggleOption(this, 'receipts')">
            <label class="backup-option-label">
                <input type="checkbox" id="opt-receipts" onchange="updateSelection()">
                <i class="fa-solid fa-file-pdf backup-option-icon"></i>
                <div class="backup-option-content">
                    <div class="backup-option-title">Recibos em PDF</div>
                    <p class="backup-option-desc">
                        Todos os recibos gerados pelo sistema em formato PDF
                    </p>
                </div>
            </label>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4">
            <div>
                <small class="text-muted" id="selection-info">
                    <i class="fa-solid fa-info-circle me-1"></i>
                    <span id="selection-count">1 item</span> selecionado
                </small>
            </div>
            <button type="button" class="btn-backup-run" id="btnCriarBackup">
                <i class="fa-solid fa-download"></i> Criar Backup Agora
            </button>
        </div>

        <div class="progress-container" id="progressContainer">
            <small class="text-muted mb-2 d-block">
                <i class="fa-solid fa-spinner fa-spin me-1"></i>
                <span id="progress-text">A preparar backup...</span>
            </small>
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     role="progressbar" 
                     style="width: 100%"></div>
            </div>
        </div>

        <div class="backup-success" id="backupSuccess">
            <i class="fa-solid fa-circle-check"></i>
            <strong>Backup criado com sucesso!</strong>
            <div id="backup-details" class="mt-2 small"></div>
        </div>
    </div>

    <!-- Card de Backups Existentes -->
    <div class="card-backup">
        <h6><i class="fa-solid fa-clock-rotate-left me-2"></i>Backups existentes</h6>
        <?php if (empty($backups)): ?>
            <p class="text-muted mb-0" style="font-size:0.9rem;">
                <i class="fa-solid fa-folder-open me-2"></i>
                Ainda não existem backups na pasta <code>/backups</code>.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th><i class="fa-solid fa-file-zipper me-2"></i>Ficheiro</th>
                        <th><i class="fa-solid fa-calendar me-2"></i>Data</th>
                        <th><i class="fa-solid fa-hard-drive me-2"></i>Tamanho</th>
                        <th class="text-end">Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($backups as $b): ?>
                        <tr>
                            <td><code><?= safe($b['name']); ?></code></td>
                            <td><?= date('d/m/Y H:i', $b['mtime']); ?></td>
                            <td><?= number_format($b['size'] / 1024 / 1024, 2, ',', '.'); ?> MB</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="/backups/<?= safe($b['name']); ?>"
                                   download>
                                    <i class="fa-solid fa-download"></i> Descarregar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="alert alert-info small mb-0">
        <i class="fa-solid fa-lightbulb me-2"></i>
        <strong>Dica:</strong> Os backups são salvos em formato ZIP e incluem apenas os itens selecionados.
        Recomendamos fazer backups regulares da base de dados para garantir a segurança dos seus dados.
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleOption(element, type) {
        const checkbox = element.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        updateSelection();
    }

    function updateSelection() {
        // Atualiza visual das opções
        document.querySelectorAll('.backup-option').forEach(option => {
            const checkbox = option.querySelector('input[type="checkbox"]');
            if (checkbox.checked) {
                option.classList.add('selected');
            } else {
                option.classList.remove('selected');
            }
        });

        // Conta itens selecionados
        const count = document.querySelectorAll('.backup-option input:checked').length;
        const countText = count === 1 ? '1 item' : count + ' itens';
        document.getElementById('selection-count').textContent = countText;

        // Habilita/desabilita botão
        const btn = document.getElementById('btnCriarBackup');
        btn.disabled = count === 0;
    }

    document.getElementById('btnCriarBackup')?.addEventListener('click', function () {
        const btn = this;
        const progressContainer = document.getElementById('progressContainer');
        const successContainer = document.getElementById('backupSuccess');
        const progressText = document.getElementById('progress-text');

        // Coleta opções selecionadas
        const formData = new FormData();
        formData.append('include_database', document.getElementById('opt-database').checked);
        formData.append('include_uploads', document.getElementById('opt-uploads').checked);
        formData.append('include_product_images', document.getElementById('opt-products').checked);
        formData.append('include_receipts', document.getElementById('opt-receipts').checked);

        // Desabilita botão e mostra progresso
        btn.disabled = true;
        progressContainer.style.display = 'block';
        successContainer.style.display = 'none';
        progressText.textContent = 'A criar backup...';

        // Faz requisição
        fetch('../../api/backup_manager.php', {
            method: 'POST',
            body: formData
        })
        .then(async (r) => {
            const text = await r.text();
            let data = null;
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('Resposta não é JSON: ' + text.substring(0, 200));
            }
            return data;
        })
        .then(data => {
            if (data && data.success) {
                progressText.textContent = 'Backup concluído!';
                
                // Mostra mensagem de sucesso
                const details = `
                    <div><strong>Ficheiro:</strong> ${data.file.split('/').pop()}</div>
                    <div><strong>Tamanho:</strong> ${data.size}</div>
                    <div><strong>Itens incluídos:</strong> ${data.items.join(', ')}</div>
                    <div><strong>Data:</strong> ${data.timestamp}</div>
                `;
                document.getElementById('backup-details').innerHTML = details;
                successContainer.style.display = 'block';
                
                // Recarrega página após 3 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                alert('Falha ao criar backup: ' + (data.error || 'erro desconhecido'));
                progressContainer.style.display = 'none';
            }
        })
        .catch(err => {
            alert('Erro ao criar backup: ' + err);
            progressContainer.style.display = 'none';
        })
        .finally(() => {
            btn.disabled = false;
        });
    });

    // Inicializa contagem
    updateSelection();
</script>

</body>
</html>
