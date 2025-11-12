<?php
require_once __DIR__ . '/../../../config/database.php';
$id = $_POST['id'];

$conn->beginTransaction();

// Atualiza estado do pedido
$stmt = $conn->prepare("UPDATE pedido SET estado='atendido', lido=1 WHERE id=?");
$stmt->execute([$id]);

// Cria venda
$stmt2 = $conn->prepare("
  INSERT INTO venda (id_pedido, data_venda, total, estado)
  SELECT id, NOW(), total, 'concluida' FROM pedido WHERE id=?;
");
$stmt2->execute([$id]);

$conn->commit();

echo "Pedido atendido com sucesso!";
?>
