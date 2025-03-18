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

function generateHMAC($vaultAddr, $vaultToken, $keyName, $input, $hashAlgorithm = "sha2-256") {
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

function verifyHMAC($vaultAddr, $vaultToken, $keyName, $input, $hmac, $hashAlgorithm = "sha2-256") {
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

// Function to send a request to Vault API
function vaultRequest($url, $method = "GET", $token, $data = [])
{
    $ch = curl_init($url);
    $headers = [
        "X-Vault-Token: $token",
        "Content-Type: application/json"
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "Error: HTTP $httpCode - " . $response . "\n";
        return false;
    }

    return json_decode($response, true);
}

// === CONFIG ===
$vaultAddr = "http://vault:8200";
$roleId = "7af48f17-e1a6-9d7d-8f35-e992dc6b86a8";  // Replace with actual role_id
$secretId = "221b3ccb-1427-375a-5adc-bbb22ed6081f"; // Replace with actual secret_id
$keyName = "hmac-key";
$inputMessage = "my-secret-message";

// === AUTHENTICATE ===
// $vaultToken = getVaultToken($vaultAddr, $roleId, $secretId);
// echo "Vault Token: " . $vaultToken . PHP_EOL;

$vaultToken = "hvs.CAESINl03x0Wq_l1qhHRHdpcS_rIFQbcTTb3gJNT1oqFcuHeGh4KHGh2cy5oeDNoOUZLMFNIWDV0SlJOaURodVN2cng";
echo "Vault Token: " . $vaultToken . PHP_EOL;

// Step 1: Check current token TTL
$lookupUrl = "$vaultAddr/v1/auth/token/lookup-self";
$tokenInfo = vaultRequest($lookupUrl, "GET", $vaultToken);

if (!$tokenInfo) {
    die("Failed to retrieve token info.\n");
}

echo "tokenInfo= " . json_encode($tokenInfo) . "\n";

$ttl = $tokenInfo['data']['ttl'];          // Current TTL (in seconds)
$maxTtl = $tokenInfo['data']['max_ttl'];   // Max TTL (in seconds)

echo "Current Token TTL: $ttl seconds\n";
echo "Max Token TTL: $maxTtl seconds\n";

// === GENERATE HMAC ===
$hmac = generateHMAC($vaultAddr, $vaultToken, $keyName, $inputMessage);
echo "Generated HMAC: " . $hmac . PHP_EOL;

// // === AUTHENTICATE ===
$isValid = verifyHMAC($vaultAddr, $vaultToken, $keyName, $inputMessage, $hmac);
echo $isValid ? "HMAC is valid!" . PHP_EOL : "HMAC is invalid!" . PHP_EOL;
?>
