<?php

require_once __DIR__ . '/VaultRsaMultiCache.php';

// Replace with your secure values
$vault = new VaultRsaMultiCache(
    'http://vault:8200',
    'b276cca0-c415-8232-bd33-009c03cf8f89',
    '8fec1698-cb9b-810b-3066-43be9dd1a64c'
);

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$keyName = isset($_GET['key']) ? $_GET['key'] : 'rsa-key';
$version = isset($_GET['version']) ? intval($_GET['version']) : null;

try {
    if ($action === 'latest') {
        $key = $vault->getLatestPublicKey($keyName);
        echo json_encode(['status' => 'ok', 'version' => 'latest', 'key' => $key]);
    } elseif ($action === 'version' && $version !== null) {
        $key = $vault->getSpecificPublicKey($keyName, $version);
        if ($key) {
            echo json_encode(['status' => 'ok', 'version' => $version, 'key' => $key]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Key version not found']);
        }
    } elseif ($action === 'clear') {
        $vault->clearCache();
        echo json_encode(['status' => 'ok', 'message' => 'Shared memory cache cleared']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
