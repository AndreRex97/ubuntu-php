<?php
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

// === CONFIG ===
$vaultAddr = "http://vault:8200"; // Change to your Vault server
$vaultToken = "hvs.CAESIK0c2Kn_U75oRUCLSTf6Y4Dr34-RveIDpE3cwUrh7mfmGh4KHGh2cy4wMWtsTWxmQnh0eEcyQ3JEZThWelNjYjc";   // Replace with your Vault token
$keyName = "axs-hmac-key";             // Vault key name
$input = "my-secret-message";         // Message to hash
$hmac = "vault:v1:h3DWHxZMqU974PObWKoGccEkn81k+C/qd/rpq1eVDeA=";

// === CALL FUNCTION ===
$isValid = verifyHMAC($vaultAddr, $vaultToken, $keyName, $input, $hmac);
echo $isValid ? "HMAC is valid!" : "HMAC is invalid!";
