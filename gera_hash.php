<?php
$password = '200718';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "HASH: " . $hash . PHP_EOL;
?>
