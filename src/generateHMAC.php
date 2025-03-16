<?php

function generateHMAC($vaultAddr, $vaultToken, $keyName, $input, $hashAlgorithm = "sha2-384") {
    $url = "{$vaultAddr}/v1/transit/hmac/{$keyName}/{$hashAlgorithm}";
    
    // Convert input to Base64
    $base64Input = base64_encode($input);

    $data = [
        "input" => $base64Input
    ];

    $headers = [
        "X-Vault-Token: {$vaultToken}",
        "Content-Type: application/json"
    ];

    // Initialize cURL session
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        die("Error: Unable to generate HMAC. Response: " . $response);
    }

    $jsonResponse = json_decode($response, true);
    return $jsonResponse['data']['hmac'];
}

// === CONFIG ===
$vaultAddr = "http://vault:8200"; // Change to your Vault server
$vaultToken = "hvs.CAESIPcrNp89st8djSTYN8KuIaFKRgpytrVI0L7y1-CwotFjGh4KHGh2cy5iY0FYUzAxSkV3cmt1T1RBaDJ4RzZhVHY";   // Replace with your Vault token
$keyName = "hmac-key";             // Vault key name
$input = "my-secret-message";         // Message to hash

// === CALL FUNCTION ===
$hmac = generateHMAC($vaultAddr, $vaultToken, $keyName, $input);
echo "Generated HMAC: " . $hmac . PHP_EOL;
?>
