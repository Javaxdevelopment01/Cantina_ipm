<?php
session_start();

// Limpa as configurações específicas do vendedor ao fazer logout
if (isset($_SESSION['vendedor_id'])) {
    $vendedorSettingsPath = __DIR__ . '/../../../config/settings_vendedor_' . $_SESSION['vendedor_id'] . '.json';
    if (file_exists($vendedorSettingsPath)) {
        unlink($vendedorSettingsPath);
    }
}

session_destroy();
header('Location: login_vendedor.php');
exit;
