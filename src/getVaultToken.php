<?php

function getVaultToken($vaultAddr, $roleId, $secretId) {
    $url = "{$vaultAddr}/v1/auth/approle/login";

    $data = json_encode([
        "role_id" => $roleId,
        "secret_id" => $secretId
    ]);

    $headers = ["Content-Type: application/json"];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        die("Error: Unable to authenticate with Vault. Response: " . $response);
    }

    $jsonResponse = json_decode($response, true);
    return $jsonResponse['auth']['client_token'];
}

function generateHMAC($vaultAddr, $vaultToken, $keyName, $input, $hashAlgorithm = "sha2-384") {
    $url = "{$vaultAddr}/v1/transit/hmac/{$keyName}/{$hashAlgorithm}";

    $base64Input = base64_encode($input);

    $data = json_encode([
        "input" => $base64Input
    ]);

    $headers = [
        "X-Vault-Token: {$vaultToken}",
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        die("Error: Unable to generate HMAC. Response: " . $response);
    }

    $jsonResponse = json_decode($response, true);
    return $jsonResponse['data']['hmac'];
}

function verifyHMAC($vaultAddr, $vaultToken, $keyName, $input, $hmac, $hashAlgorithm = "sha2-384") {
    $url = "{$vaultAddr}/v1/transit/verify/{$keyName}/{$hashAlgorithm}";

    $data = [
        "input" => base64_encode($input),
        "hmac" => $hmac
    ];

    $headers = [
        "X-Vault-Token: {$vaultToken}",
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        die("Error: Unable to verify HMAC. Response: " . $response);
    }

    $jsonResponse = json_decode($response, true);
    return $jsonResponse['data']['valid'];
}

// === CONFIG ===
$vaultAddr = "http://vault:8200";
$roleId = "4282de00-ced6-85c6-0aaf-8e709376e07a";  // Replace with actual role_id
$secretId = "5019acb7-82c1-bf72-5a79-cc965544f5c4"; // Replace with actual secret_id
$keyName = "hmac-key";
$inputMessage = "my-secret-message";

// === AUTHENTICATE ===
$vaultToken = getVaultToken($vaultAddr, $roleId, $secretId);
echo "Vault Token: " . $vaultToken . PHP_EOL;

// === GENERATE HMAC ===
$hmac = generateHMAC($vaultAddr, $vaultToken, $keyName, $inputMessage);
echo "Generated HMAC: " . $hmac . PHP_EOL;

// // === AUTHENTICATE ===
$isValid = verifyHMAC($vaultAddr, $vaultToken, $keyName, $inputMessage, $hmac);
echo $isValid ? "HMAC is valid!" : "HMAC is invalid!";
?>
