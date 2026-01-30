<?php
header('Content-Type: application/json');
$key = getenv('GEMINI_API_KEY') ?: 'AIzaSyAkHWcAwr9cByqFRItrJ_U6f91A2o2KY2Y'; // Hardcoded fallback based on previous context
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $key;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>
