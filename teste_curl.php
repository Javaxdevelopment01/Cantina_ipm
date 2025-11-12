<?php
require_once __DIR__ . "/helpers/CurlHelper.php";

// Teste de requisição GET
$resposta = curlComSSL("https://www.google.com/");

if ($resposta["sucesso"]) {
    echo "Sucesso! CURL com SSL funcionou.";
} else {
    echo "Erro CURL: " . $resposta["erro"];
}
?>
