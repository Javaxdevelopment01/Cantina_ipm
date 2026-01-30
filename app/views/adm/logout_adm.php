<?php
session_start();
// Limpa apenas a sessão do administrador (mantém outras sessões caso existam)
unset($_SESSION['admin_id'], $_SESSION['admin_nome'], $_SESSION['admin_foto']);
// Se quiser destruir toda a sessão (remover tudo), use session_destroy();
session_destroy();

// Redireciona para a página de login do administrador
header('Location: login_adm.php');
exit;
