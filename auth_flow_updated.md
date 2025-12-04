# Auth Flow (Updated for Opaque Internal Access Tokens)

This document describes the authentication flow for your system using:

- **Keycloak** as the external identity provider  
- **Your Symfony Auth Server** as the session manager and token issuer  
- **Opaque internal access tokens** stored in HTTP-only cookies  
- **Your API** validating opaque tokens via a lookup  
- **Your Frontend** simply sending cookies  

Only the sections impacted by the switch from JWT → opaque tokens have been updated.

---

## 🔄 Overall Flow

### 1. **User starts login at Auth Server**
- User hits `/login` on your Symfony Auth Server.
- Auth Server redirects them to Keycloak’s `authorization_endpoint`.

### 2. **User authenticates with Keycloak**
- Keycloak shows UI.
- When successful, Keycloak redirects back to:  
  `https://auth-server/oidc/callback`

### 3. **Auth Server handles Keycloak callback**
- Your callback controller:
  - Exchanges authorization code for **Keycloak access_token + refresh_token**
  - Extracts user identity data (sub, email, etc.)

### 4. **Auth Server issues *its own* opaque access token**
Instead of using Keycloak’s access_token, the Auth Server generates:

- A **random opaque string** (e.g. UUID or crypto-random)
- Stores it in a database/cache mapping to:
  - Keycloak user ID (`sub`)
  - token expiry  
  - Keycloak refresh token  
  - permissions/roles (optional)

Example storage record:

```
internal_token: "4f09db1c3b3e..."
user_id: "72abe9d0-81d9-4e94-948d-8cc649223c9f"
expires_at: 2025-01-01 14:00:00
refresh_token: "…" (Keycloak)
```

### 5. **Auth Server sends cookies to the browser**
You set two cookies:

#### Cookie A — **access_token**
- Value: **your opaque token**
- HttpOnly
- Secure
- SameSite=Lax or Strict
- Expires ~15 minutes

#### Cookie B — **refresh_token**
- Value: encrypted Keycloak refresh token *or* your own rotated refresh token
- HttpOnly
- Secure
- SameSite=Strict
- Longer expiry (~7–30 days)

### 6. **Frontend calls your API (Automatic: cookies included)**
- Browser sends `access_token` cookie automatically.
- No JS access needed.

### 7. **API validates the opaque token**
The API does:

1. Look up the token in DB/Redis.
2. Ensure it is valid & not expired.
3. Fetch user ID and permissions.
4. Proceed with the API request.

If token is expired → return `401` with `"token_expired"`.

### 8. **Frontend silently refreshes**
When API returns `"token_expired"`:

- The browser redirects to Auth Server’s `/refresh` endpoint.
- Auth Server:
  - Reads refresh_token cookie
  - Uses stored Keycloak refresh token to get a new KC access token
  - Creates a *new internal opaque access token*
  - Sends updated cookies back

---

## 🛡️ Why Opaque Tokens Are Ideal Here

- Your API does **not** need to validate JWT signatures.
- Internal permissions remain flexible (changeable without reissuing tokens).
- Refresh logic remains clean on Auth Server.
- Zero leakage of Keycloak internals to external apps.

---

## 🛠 Updated Responsibilities Summary

### ✔ Auth Server
- Talks to Keycloak
- Stores Keycloak refresh token
- Issues its own opaque access tokens
- Issues its own long-lived refresh tokens
- Handles token rotation
- Sends cookies to frontend

### ✔ API
- Receives only your opaque access token
- Does a DB/cache lookup to validate
- Returns user data

### ✔ Frontend
- Sends requests normally
- Stores no tokens
- Relies entirely on cookies

---

## ✅ No JWT Required
You only move to JWT if:
- You have multiple APIs needing local verification
- You want stateless validation
- You want to eliminate DB/cache lookups

For now, opaque tokens are cleaner and safer.

---

## End of Updates
Only the sections that changed (token handling, storage, API validation) were rewritten. The rest of your original file can remain unchanged.
