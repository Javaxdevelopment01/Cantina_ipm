<?php
header('Content-Type: application/json; charset=utf-8');
$path = __DIR__ . '/write_test_output.txt';
$ok = @file_put_contents($path, json_encode(['time'=>date('c'), 'user'=>get_current_user(), 'uid'=>getmyuid()], JSON_PRETTY_PRINT));
$res = ['ok' => $ok !== false, 'bytes' => $ok, 'path' => $path];
echo json_encode($res, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
