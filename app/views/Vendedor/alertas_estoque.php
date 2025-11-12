<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';
include __DIR__ . '/includes/menu_vendedor.php';
include __DIR__ . '/../../../includes/assistente_virtual.php';

// Adiciona scripts necessários
echo '<script src="/assets/js/monitor-estoque.js"></script>';

function safe($str){ return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

// Busca produtos
$stmt = $conn->prepare("SELECT p.*, c.nome AS categoria_nome FROM produto p LEFT JOIN categoria c ON p.categoria_id = c.id ORDER BY p.id DESC");
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$lowThreshold = 5; // mesmo limiar usado em produtos

// (listagem completa — itens críticos serão destacados quando necessário)
?>

<div class="main container-fluid" style="margin-left:270px; padding:30px;">
    <div class="page-header mb-4">
        <div>
            <h2 style="color:var(--petroleo); margin:0;">Alertas de Estoque</h2>
            <p class="text-muted">Lista completa de produtos com suas quantidades — itens críticos estão destacados.</p>
        </div>
    </div>

    <div class="alerts-list">
        <?php if (empty($produtos)): ?>
            <div class="alert alert-light">Nenhum produto encontrado.</div>
        <?php else: ?>
            <?php foreach ($produtos as $li):
                $isLow = intval($li['quantidade']) <= $lowThreshold;
            ?>
                <div class="alert-item d-flex align-items-center mb-3 p-3 <?php echo $isLow ? 'alert-low-stock' : ''; ?>" data-prod-id="<?php echo $li['id']; ?>">
                    <img src="<?php echo safe(!empty($li['imagem']) ? '/'.$li['imagem'] : 'https://via.placeholder.com/100x80?text=Sem+Img'); ?>" alt="" class="item-thumb">
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                            <div>
                                <strong class="item-name"><?php echo safe($li['nome']); ?></strong>
                                <div class="text-muted small"><?php echo safe($li['categoria_nome']); ?></div>
                            </div>
                            <div style="text-align:right; min-width:120px;">
                                <?php if ($isLow): ?>
                                    <div class="badge badge-critical">Crítico</div>
                                <?php endif; ?>
                                <div class="qty-display" style="font-weight:700; font-size:1.1rem; margin-top:6px;">Qtd: <span class="alert-qty"><?php echo intval($li['quantidade']); ?></span></div>
                                <?php if (!$isLow): ?>
                                    <div class="small text-muted">Em estoque</div>
                                <?php else: ?>
                                    <div class="small text-muted">Limiar: <?php echo $lowThreshold; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mt-2">
                            <?php // Botão rápido para repor estoque: abre página de edição do produto ?>
                            <a href="produtos_vendedor.php?edit=<?php echo $li['id']; ?>" class="btn btn-sm btn-outline-primary">Repor Estoque</a>
                            <?php if (intval($li['quantidade']) <= 0): ?>
                                <span class="ms-2 badge bg-danger">Esgotado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<style>
.alerts-list { display:flex; flex-direction:column; gap:12px; }
.alert-item { background:#fff; border-radius:12px; box-shadow:0 6px 18px rgba(1,46,64,0.06); }
.item-thumb { width:100px; height:70px; object-fit:cover; border-radius:8px; margin-right:16px; }
.alert-item { background:#fff; border-radius:12px; box-shadow:0 6px 18px rgba(1,46,64,0.06); }
.alert-low-stock { background:#ffecec; border-left:5px solid #D9534F; }
.badge-critical { display:inline-block; background:#D9534F; color:#fff; padding:6px 8px; border-radius:8px; font-weight:700; }
.item-name { color:var(--petroleo); }
.qty-display { color:#012E40; }
.alerts-list { display:flex; flex-direction:column; gap:12px; }
</style>

<script>
// Polling para atualizar a lista dinamicamente a cada 10s
(function(){
    const lowThreshold = <?php echo json_encode($lowThreshold); ?>;
    const container = document.querySelector('.alerts-list');
    if (!container) return;

    function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]; }); }

    function buildList(items){
        if (!items.length) { container.innerHTML = '<div class="alert alert-light">Nenhum produto encontrado.</div>'; return; }
        container.innerHTML = items.map(function(p){
            const img = p.imagem ? (p.imagem.startsWith('/')? p.imagem : ('/'+p.imagem)) : 'https://via.placeholder.com/100x80?text=Sem+Img';
            const isLow = parseInt(p.quantidade,10) <= parseInt(lowThreshold,10);
            return `\
                <div class="alert-item d-flex align-items-center mb-3 p-3 ${isLow ? 'alert-low-stock' : ''}" data-prod-id="${p.id}">\
                    <img src="${img}" alt="" class="item-thumb">\
                    <div style="flex:1;">\
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">\
                            <div>\
                                <strong class="item-name">${escapeHtml(p.nome)}</strong>\
                                <div class="text-muted small">${escapeHtml(p.categoria_nome||'')}</div>\
                            </div>\
                            <div style="text-align:right; min-width:120px;">\
                                ${isLow ? '<div class="badge badge-critical">Crítico</div>' : ''}\
                                <div class="qty-display" style="font-weight:700; font-size:1.1rem; margin-top:6px;">Qtd: <span class="alert-qty">${p.quantidade}</span></div>\
                                ${isLow ? '<div class="small text-muted">Limiar: ' + lowThreshold + '</div>' : '<div class="small text-muted">Em estoque</div>' }\
                            </div>\
                        </div>\
                        <div class="mt-2">\
                            <a href="produtos_vendedor.php?edit=${p.id}" class="btn btn-sm btn-outline-primary">Repor Estoque</a>\
                            ${parseInt(p.quantidade,10) <= 0 ? '<span class="ms-2 badge bg-danger">Esgotado</span>' : '' }\
                        </div>\
                    </div>\
                </div>\
            `;
        }).join('');
    }

    function fetchAndUpdate(){
        fetch('produtos_vendedor.php?ajax=produtos')
            .then(r=>r.json())
            .then(function(data){
                // build full list; low-stock styling applied in buildList
                buildList(data);
            })
            .catch(console.error);
    }

    fetchAndUpdate();
    setInterval(fetchAndUpdate,10000);
})();

// Reconciliação: confirmação antes de enviar
document.addEventListener('DOMContentLoaded', function(){
    const btn = document.getElementById('btnRecon');
    if (!btn) return;
    btn.addEventListener('click', function(){
        if (!confirm('Tem certeza? Esta operação irá reduzir o estoque com base em vendas historicas e não pode ser desfeita.')) return;
        document.getElementById('reconForm').submit();
    });
});
</script>
