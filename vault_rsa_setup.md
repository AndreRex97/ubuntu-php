# 📌 HashiCorp Vault: RSA Key Management with AppRole Authentication

This guide documents how to securely manage RSA keys in HashiCorp Vault using AppRole authentication (Role ID + Secret ID) instead of the root token.

---

## 🔧 Prerequisites

- Vault server is up and running (in dev or production mode)
- CLI access to Vault
- `VAULT_ADDR` is set (e.g., `http://127.0.0.1:8200`)
- Initial setup may use root token (but we'll switch to AppRole)

---

## 1. 🔐 Enable AppRole Authentication

```bash
vault auth enable approle
```

---

## 2. 📜 Create a Policy for RSA Key Access

Create a file named `rsa-policy.hcl`:

```hcl
path "transit/keys/rsa-key" {
  capabilities = ["read", "update"]
}

path "transit/sign/rsa-key" {
  capabilities = ["update"]
}

path "transit/encrypt/rsa-key" {
  capabilities = ["update"]
}

path "transit/decrypt/rsa-key" {
  capabilities = ["update"]
}
```

Apply the policy:

```bash
vault policy write rsa-policy rsa-policy.hcl
```

---

## 3. 🧑‍💻 Create AppRole and Bind the Policy

```bash
vault write auth/approle/role/rsa-role \
  token_policies="rsa-policy" \
  token_ttl=1h \
  token_max_ttl=4h
```

---

## 4. 🔑 Fetch Role ID and Secret ID

```bash
vault read auth/approle/role/rsa-role/role-id
vault write -f auth/approle/role/rsa-role/secret-id
```

Save these values securely. You’ll use them to authenticate without the root token.

---

## 5. 🔁 Enable Transit Engine and Create RSA Key

```bash
vault secrets enable transit
vault write -f transit/keys/rsa-key type=rsa-2048
```

To rotate the key:

```bash
vault write -f transit/keys/rsa-key/rotate
```

---

## 6. 🔓 Authenticate Using Role ID + Secret ID

```bash
vault write auth/approle/login \
  role_id="<ROLE_ID>" \
  secret_id="<SECRET_ID>"
```

This returns a Vault token (`client_token`) which can be used for subsequent requests.

---

## 7. 📬 Fetch the RSA Public Key

```bash
vault read transit/keys/rsa-key
```

This provides a list of public keys by version. You’ll get output like:

```json
{
  "data": {
    "keys": {
      "1": {
        "public_key": "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9..."
      }
    }
  }
}
```

---

## ✅ Summary

You’ve now:
- Enabled Vault AppRole authentication
- Created a scoped RSA key policy
- Generated a secure RSA key pair in Transit
- Authenticated using Role ID + Secret ID
- Fetched public keys programmatically (e.g., via PHP or curl)
