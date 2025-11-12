<?php
// api_ia.php
// Endpoint para comunicação com a IA

header("Content-Type: application/json");

// Lê a mensagem enviada pelo JavaScript
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["message"])) {
    echo json_encode(["error" => "Mensagem não encontrada"]);
    exit;
}

$message = $data["message"];
$apiKey = "AQUI_VAI_A_TUA_CHAVE_DA_OPENAI"; // coloca aqui tua chave

$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $apiKey
]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "model" => "gpt-3.5-turbo",
    "messages" => [
        ["role" => "system", "content" => "Tu és uma assistente virtual simpática que fala português de Angola."],
        ["role" => "user", "content" => $message]
    ]
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(["error" => curl_error($ch)]);
    exit;
}
curl_close($ch);

echo $response;
?>
