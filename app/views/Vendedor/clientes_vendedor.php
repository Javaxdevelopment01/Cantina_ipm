<?php
session_start();
if(!isset($_SESSION['vendedor_id'])) {
	header('Location: login_vendedor.php');
	exit;
}
require_once __DIR__ . '/../../../config/database.php';

// Função de segurança
function safe($str) {
	return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Tenta obter clientes que já fizeram compras (tenta venda -> pedido -> todos clientes como fallback)
$clientes = [];
try {
	// Primeiro tenta buscar clientes a partir da tabela 'venda'
	$stmt = $conn->prepare("SELECT DISTINCT c.id, c.nome, c.email, c.telefone FROM cliente c JOIN venda v ON v.cliente_id = c.id ORDER BY c.nome ASC");
	$stmt->execute();
	$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
	// Se não retornou nada, tenta pela tabela 'pedido'
	if (!$clientes) {
		$stmt = $conn->prepare("SELECT DISTINCT c.id, c.nome, c.email, c.telefone FROM cliente c JOIN pedido p ON p.cliente_id = c.id ORDER BY c.nome ASC");
		$stmt->execute();
		$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
} catch (Exception $e) {
	// Se ocorrer erro (nomes de colunas diferentes), tenta obter todos os clientes como fallback
	try {
		$stmt = $conn->prepare("SELECT id, nome, email, telefone FROM cliente ORDER BY nome ASC");
		$stmt->execute();
		$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} catch (Exception $e2) {
		$clientes = [];
	}
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Clientes - Vendedor</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<style>
		* { box-sizing:border-box; margin:0; padding:0; }
		body { font-family:'Segoe UI', sans-serif; background:#f7f8fa; }
		.main { margin-left:250px; padding:30px; transition:0.3s; }
		.clientes-list { background:white; border-radius:12px; padding:20px; box-shadow:0 3px 10px rgba(0,0,0,0.08); }
		.cliente-item { padding:12px; border-bottom:1px solid #f0f0f0; display:flex; gap:12px; align-items:center; }
		.cliente-item:last-child { border-bottom:none; }
		.cliente-avatar { width:44px; height:44px; border-radius:50%; background:#e9ecef; display:flex; align-items:center; justify-content:center; color:#6c757d; font-weight:bold; }
		.cliente-info { flex:1; }
		.cliente-name { font-weight:700; color:#012E40; }
		.cliente-meta { color:#6b7280; font-size:0.95rem; }
		h2 { color:#012E40; margin-bottom:12px; }
	</style>
</head>
<body>

<?php include 'includes/menu_vendedor.php'; ?>

<div class="main">
	<h2>Clientes que já compraram</h2>
	<div class="clientes-list">
		<?php if (empty($clientes)): ?>
			<p>Nenhum cliente encontrado.</p>
		<?php else: ?>
			<?php foreach ($clientes as $c): ?>
				<div class="cliente-item">
					<div class="cliente-avatar"><?php echo strtoupper(substr(safe($c['nome'] ?? $c['nome'] ?? 'U'),0,1)); ?></div>
					<div class="cliente-info">
						<div class="cliente-name"><?php echo safe($c['nome'] ?? $c['name'] ?? 'Nome desconhecido'); ?></div>
						<div class="cliente-meta">
							<?php if (!empty($c['email'])): ?><?php echo safe($c['email']); ?><?php endif; ?>
							<?php if (!empty($c['telefone'])): ?><?php if (!empty($c['email'])) echo ' · '; ?><?php echo safe($c['telefone']); ?><?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

</body>
</html>
