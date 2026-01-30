<?php
header('Content-Type: application/json; charset=utf-8');
$raw = file_get_contents('php://input');
$res = [
    'raw' => substr($raw,0,1000),
    'php_input_empty' => ($raw === ''),
    'post' => $_POST,
    'request' => $_REQUEST,
    'get' => $_GET,
    'headers' => getallheaders(),
];
echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
