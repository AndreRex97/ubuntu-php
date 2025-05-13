<?php

class VaultRsaMultiCache
{
    private $vaultAddr;
    private $roleId;
    private $secretId;
    private $shmKey;
    private $shmSize;

    const SLOT_LATEST = 1;
    const SLOT_REQUESTED = 2;
    const EXPIRY_SECONDS = 86400;

    public function __construct($vaultAddr, $roleId, $secretId, $keyFile = __FILE__, $shmSize = 4096)
    {
        $this->vaultAddr = rtrim($vaultAddr, '/');
        $this->roleId = $roleId;
        $this->secretId = $secretId;
        $this->shmKey = ftok($keyFile, 'R');
        $this->shmSize = $shmSize;
    }

    private function vaultLogin()
    {
        $url = $this->vaultAddr . '/v1/auth/approle/login';
        $payload = json_encode(array(
            'role_id' => $this->roleId,
            'secret_id' => $this->secretId
        ));

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json')
        ));

        $response = curl_exec($ch);
        if (!$response) {
            throw new Exception('Vault login failed: ' . curl_error($ch));
        }

        $data = json_decode($response, true);
        return $data['auth']['client_token'];
    }

    private function fetchAllKeys($token, $keyName)
    {
        $url = $this->vaultAddr . '/v1/transit/keys/' . $keyName;

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('X-Vault-Token: ' . $token)
        ));

        $response = curl_exec($ch);
        if (!$response) {
            throw new Exception('Failed to fetch key metadata: ' . curl_error($ch));
        }

        return json_decode($response, true);
    }

    private function getSharedMemory()
    {
        return shm_attach($this->shmKey, $this->shmSize);
    }

    private function readSlot($slotId)
    {
        $shm = $this->getSharedMemory();
        if (shm_has_var($shm, $slotId)) {
            return shm_get_var($shm, $slotId);
        }
        return null;
    }

    private function writeSlot($slotId, $data)
    {
        $shm = $this->getSharedMemory();
        shm_put_var($shm, $slotId, $data);
    }

    public function clearCache()
    {
        $shm = $this->getSharedMemory();
        shm_remove($shm);
    }

    public function getLatestPublicKey($keyName)
    {
        $slot = $this->readSlot(self::SLOT_LATEST);
        $token = $this->vaultLogin();
        $vaultData = $this->fetchAllKeys($token, $keyName);
        $latestVersion = max(array_keys($vaultData['data']['keys']));

        if ($slot && (time() - $slot['fetched_at'] < self::EXPIRY_SECONDS) && $slot['version'] == $latestVersion) {
            return $slot['public_key'];
        }

        $latestKey = $vaultData['data']['keys'][$latestVersion]['public_key'];

        $this->writeSlot(self::SLOT_LATEST, array(
            'version' => $latestVersion,
            'public_key' => $latestKey,
            'fetched_at' => time()
        ));

        return $latestKey;
    }

    public function getSpecificPublicKey($keyName, $version)
    {
        $slot = $this->readSlot(self::SLOT_REQUESTED);

        if ($slot && $slot['version'] == $version && (time() - $slot['fetched_at'] < self::EXPIRY_SECONDS)) {
            return $slot['public_key'];
        }

        $token = $this->vaultLogin();
        $vaultData = $this->fetchAllKeys($token, $keyName);

        if (!isset($vaultData['data']['keys'][$version])) {
            return null;
        }

        $publicKey = $vaultData['data']['keys'][$version]['public_key'];

        $this->writeSlot(self::SLOT_REQUESTED, array(
            'version' => $version,
            'public_key' => $publicKey,
            'fetched_at' => time()
        ));

        return $publicKey;
    }
}
