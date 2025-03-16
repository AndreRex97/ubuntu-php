# Step-by-Step Guide to Implementing Vault HMAC with AppRole Authentication

## 1. Enable AppRole Authentication
To authenticate clients without root access, enable **AppRole authentication** in Vault:

```sh
vault auth enable approle
```

---

## 2. Enable and Configure the Transit Secrets Engine
To generate HMACs securely, enable the **transit secrets engine**:

```sh
vault secrets enable transit
```

Then, create a **transit key** for HMAC operations:

```sh
vault write -f transit/keys/hmac-key
```

---

## 3. Create a Vault Policy for HMAC Operations
Define a **policy** that grants permission to perform HMAC operations.

Create a policy file (`hmac-policy.hcl`):

```hcl
#Allow generating HMAC using transit engine
path "transit/hmac/hmac-key" {
  capabilities = ["update"]
}

#Allow generating HMAC using transit engine with sha2-384 algo
path "transit/hmac/hmac-key/sha2-384" {
  capabilities = ["update"]
}

#Allow verifying HMAC using transit engine
path "transit/verify/hmac-key" {
  capabilities = ["update"]
}
#Allow verifying HMAC using transit engine with sha2-384 algo
path "transit/verify/hmac-key/sha2-384" {
  capabilities = ["update"]
}
```

Apply the policy in Vault:

```sh
vault policy write hmac-policy hmac-policy.hcl
```

---

## 4. Create an AppRole with Limited Permissions
Create an **AppRole** that is tied to the policy:

```sh
vault write auth/approle/role/hmac-role \
    token_policies="hmac-policy" \
    token_ttl="1h" \
    token_max_ttl="4h"
```

- `hmac-role`: Name of the AppRole.
- `hmac-policy`: Assigned policy.
- `token_ttl="1h"`: Token expires in 1 hour.
- `token_max_ttl="4h"`: Max lifetime of the token.

---

## 5. Retrieve Role ID & Secret ID
After creating the AppRole, retrieve the **Role ID** and **Secret ID**.

### Retrieve Role ID
```sh
vault read auth/approle/role/hmac-role/role-id
```
Example output:
```json
{
  "data": {
    "role_id": "abcd1234-5678-90ef-ghij-klmnopqrstuv"
  }
}
```

### Retrieve Secret ID
```sh
vault write -f auth/approle/role/hmac-role/secret-id
```
Example output:
```json
{
  "data": {
    "secret_id": "wxyz5678-90ab-cdef-ghij-klmnopqrstuv",
    "secret_id_accessor": "12345678-90ab-cdef-ghij-klmnopqrstuv"
  }
}
```
🔒 **Keep these credentials secure!**

---

## 6. Authenticate Using Role ID & Secret ID
Authenticate to Vault using the retrieved credentials:

```sh
vault write auth/approle/login \
    role_id="abcd1234-5678-90ef-ghij-klmnopqrstuv" \
    secret_id="wxyz5678-90ab-cdef-ghij-klmnopqrstuv"
```
Example response:
```json
{
  "auth": {
    "client_token": "s.zfX5zvAlE3VwP7TURqGnt0lQ",
    "lease_duration": 3600,
    "renewable": true
  }
}
```
Use the **client token** to perform HMAC operations.

---

## 7. Generate an HMAC Using Vault's Transit API
### CLI Example:
```sh
vault write transit/hmac/hmac-key \
    input=$(echo -n "my-secret-message" | base64) \
    hash_algorithm=sha2-256 \
    -token="s.zfX5zvAlE3VwP7TURqGnt0lQ"
```
Example response:
```json
{
  "data": {
    "hmac": "b27b256d6e2f1c4a9dffac82b7a2c0a2a7d6c5a5a7c9d7e6b2f5d4c3e9b8a1f3"
  }
}
```

---

## 8. Verify HMAC Using Vault
Once you have an HMAC, you can verify it against the original message.

### CLI Example:
```sh
vault write transit/verify/hmac-key \
    input=$(echo -n "my-secret-message" | base64) \
    hmac="b27b256d6e2f1c4a9dffac82b7a2c0a2a7d6c5a5a7c9d7e6b2f5d4c3e9b8a1f3" \
    hash_algorithm=sha2-256 \
    -token="s.zfX5zvAlE3VwP7TURqGnt0lQ"
```
Example response:
```json
{
  "data": {
    "valid": true
  }
}
```

---

## Summary
1. **Enable AppRole authentication** and **Transit Secrets Engine**.
2. **Create a role with a policy** allowing HMAC operations.
3. **Retrieve Role ID and Secret ID**.
4. **Authenticate with Role ID & Secret ID** to obtain a Vault token.
5. **Use the token to generate an HMAC**.
6. **Verify the HMAC using Vault**.

---

Would you like to integrate this into Laravel/Symfony or a Dockerized Vault setup? 🚀

