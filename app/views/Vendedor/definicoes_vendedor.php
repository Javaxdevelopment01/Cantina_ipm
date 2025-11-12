<?php
session_start();
if(!isset($_SESSION['vendedor_id'])) {
    header('Location: login_vendedor.php');
    exit;
}
require_once __DIR__ . '/../../../config/database.php';

// carrega settings atuais
$settingsPath = __DIR__ . '/../../../config/settings_vendedor.json';
$settings = [];
if (file_exists($settingsPath)) {
    $settings = json_decode(file_get_contents($settingsPath), true) ?: [];
}

function safe($v){ return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Definições - Vendedor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        :root {
            --petroleo: #012E40;
            --dourado: #D4AF37;
            --bg-main: #f7f8fa;
            --bg-panel: #ffffff;
            --text-main: #212529;
            --muted: #6b7280;
        }
        body{font-family:Segoe UI, sans-serif;background:var(--bg-main);color:var(--text-main)}
        .main{margin-left:250px;padding:30px}
        .panel{background:var(--bg-panel);border-radius:10px;padding:18px;margin-bottom:16px;box-shadow:0 3px 10px rgba(0,0,0,0.06);border:1px solid rgba(0,0,0,0.04)}
        h2{color:var(--petroleo);margin-bottom:10px}
        label{display:block;font-weight:600;color:var(--text-main);margin-bottom:6px}
        input[type=text], input[type=color], select{width:100%;padding:8px;border:1px solid rgba(0,0,0,0.08);border-radius:6px;margin-bottom:10px;background:var(--bg-panel);color:var(--text-main)}
        input[type=file] { margin-bottom: 10px }
        .row{display:flex;gap:12px}
        .col{flex:1}
        .actions{display:flex;gap:8px;align-items:center}
        .btn{background:var(--petroleo);color:#fff;padding:8px 12px;border:none;border-radius:6px;cursor:pointer}
        .muted{color:var(--muted);font-size:0.9rem}
        .section-title{font-size:1.05rem;margin-bottom:8px;color:var(--petroleo)}
        .small{font-size:0.9rem;color:var(--muted)}
    </style>
</head>
<body>

<?php include 'includes/menu_vendedor.php'; ?>

<div class="main">
    <h2>Definições do Sistema</h2>

    <form id="formDefinicoes">
        <div class="panel">
            <div class="section-title">Tema & Aparência</div>
            <div class="row">
                <div class="col">
                    <label>Modo de Tema</label>
                    <select name="theme[mode]">
                        <option value="light" <?php if(($settings['theme']['mode'] ?? 'light')=='light') echo 'selected'; ?>>Light</option>
                        <option value="dark" <?php if(($settings['theme']['mode'] ?? '')=='dark') echo 'selected'; ?>>Dark</option>
                    </select>
                </div>
                <div class="col">
                    <label>Cor Primária</label>
                    <input type="color" name="theme[primary_color]" value="<?php echo safe($settings['theme']['primary_color'] ?? '#012E40'); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label>Cor Secundária</label>
                    <input type="color" name="theme[secondary_color]" value="<?php echo safe($settings['theme']['secondary_color'] ?? '#D4AF37'); ?>">
                </div>
                <div class="col">
                    <label>Idioma</label>
                    <select name="idiomas[default]">
                        <?php $langs = ['pt-BR','en-US']; foreach($langs as $l): ?>
                        <option value="<?php echo safe($l); ?>" <?php if(($settings['idiomas']['default'] ?? 'pt-BR') == $l) echo 'selected'; ?>><?php echo safe($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="section-title">Personalização</div>
            <label>Logo</label>
            <div class="row">
                <div class="col">
                    <input type="text" name="store[logo]" id="storeLogo" value="<?php echo safe($settings['store']['logo'] ?? ''); ?>" placeholder="URL ou caminho da logo">
                </div>
                <div style="width:160px">
                    <input type="file" id="uploadLogo" accept="image/*">
                </div>
            </div>
            <label>Favicon</label>
            <div class="row">
                <div class="col">
                    <input type="text" name="store[favicon]" id="storeFavicon" value="<?php echo safe($settings['store']['favicon'] ?? ''); ?>" placeholder="URL ou caminho do favicon">
                </div>
                <div style="width:160px">
                    <input type="file" id="uploadFavicon" accept="image/*">
                </div>
            </div>
        </div>

        <div class="actions">
            <button type="button" id="btnSalvar" class="btn">Guardar Definições</button>
            <div id="status" class="muted">Alterações não guardadas.</div>
        </div>
    </form>
</div>

<script>
document.getElementById('btnSalvar').addEventListener('click', async function(){
    const form = document.getElementById('formDefinicoes');
    const data = {};
    // serialize inputs with names like a[b][c]
    const inputs = form.querySelectorAll('input, select');
    inputs.forEach(i => {
        if (!i.name) return;
        const val = i.value;
        assignPath(data, i.name, val);
    });

    document.getElementById('status').textContent = 'A gravar...';

    const res = await fetch('salvar_definicoes.php', {
        method: 'POST', 
        headers: {'Content-Type':'application/json'}, 
        body: JSON.stringify(data)
    });
    const json = await res.json();
    if (json.ok) {
        document.getElementById('status').textContent = 'Guardado com sucesso.';
    } else {
        document.getElementById('status').textContent = 'Erro ao gravar.';
    }
});

function assignPath(obj, path, value) {
    const parts = path.replace(/\]/g,'').split(/\[/);
    let cur = obj;
    for (let i=0;i<parts.length;i++){
        const p = parts[i];
        if (i===parts.length-1) { cur[p]=value; }
        else { cur[p] = cur[p] || {}; cur = cur[p]; }
    }
}

// Upload helpers
async function uploadFile(file){
    const form = new FormData(); 
    form.append('file', file);
    const res = await fetch('upload_media.php', { 
        method:'POST', 
        body: form 
    });
    return res.json();
}

document.getElementById('uploadLogo')?.addEventListener('change', async function(e){
    const f = e.target.files[0]; 
    if (!f) return;
    document.getElementById('status').textContent = 'A carregar logo...';
    const j = await uploadFile(f);
    if (j.ok) { 
        document.getElementById('storeLogo').value = j.path; 
        document.getElementById('status').textContent = 'Logo carregado.';
    } else {
        document.getElementById('status').textContent = 'Erro no upload.';
    }
});

document.getElementById('uploadFavicon')?.addEventListener('change', async function(e){
    const f = e.target.files[0]; 
    if (!f) return;
    document.getElementById('status').textContent = 'A carregar favicon...';
    const j = await uploadFile(f);
    if (j.ok) { 
        document.getElementById('storeFavicon').value = j.path; 
        document.getElementById('status').textContent = 'Favicon carregado.';
    } else {
        document.getElementById('status').textContent = 'Erro no upload.';
    }
});
</script>

</body>
</html>
