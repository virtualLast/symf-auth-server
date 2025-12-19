# OIDC + Keycloak Params Integration – Next Steps Guide

This document describes the **exact next steps** to integrate Keycloak-issued `params` claims
into the Symfony application in a **secure, production-safe way**.

This assumes:
- Keycloak realm is already configured to emit a `params` claim
- OIDC login via `OidcController` is already working
- The `params` claim is a **JSON string** (business constraint)

---

## Phase 1 – Verify Keycloak Output

### 1. Inspect the raw access token

Temporarily log the token in `OidcController::callback()`:

```php
$rawToken = $accessToken->getToken();
```

Paste the token into https://jwt.io and confirm:

- `params` claim exists
- `params` is a **string**
- String contains valid JSON
- Claim is in the **access token** (not just userinfo)

❗ If this fails, fix the Keycloak realm before proceeding.

---

## Phase 2 – Add Claim Ingestion

### 2. Create a JWT payload reader

```php
final class JwtPayloadReader
{
    public function read(string $jwt): array
    {
        [, $payload] = explode('.', $jwt, 3);

        return json_decode(
            base64_decode(strtr($payload, '-_', '+/')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
```

Purpose:
- Decode JWT payload only
- No validation
- No business logic

---

### 3. Parse and validate `params`

```php
final class AuthorizationParamsParser
{
    public function parse(array $claims): AuthorizationParams
    {
        if (!isset($claims['params']) || !is_string($claims['params'])) {
            throw new AccessDeniedHttpException('Missing params');
        }

        $data = json_decode($claims['params'], true, 512, JSON_THROW_ON_ERROR);

        if (!isset($data['AccessLevel'], $data['HierCode'])) {
            throw new AccessDeniedHttpException('Malformed params');
        }

        return new AuthorizationParams(
            $data['AccessLevel'],
            $data['HierCode']
        );
    }
}
```

Rules:
- Fail closed
- No fallbacks
- No defaults

---

### 4. Create a value object

```php
final class AuthorizationParams
{
    public function __construct(
        public readonly array $accessLevels,
        public readonly array $hierCodes
    ) {}
}
```

This ensures raw JSON never leaks into the app.

---

## Phase 3 – Wire Into OIDC Callback

In `OidcController::callback()`:

```php
$rawToken = $accessToken->getToken();
$claims = $this->jwtPayloadReader->read($rawToken);
$authzParams = $this->authorizationParamsParser->parse($claims);
```

Do **not** interpret or transform here.

---

## Phase 4 – Choose Where Authorization Lives

### Option A (Recommended): Persist & Normalize

Extend `UserService`:

```php
public function syncAuthorizationParams(
    User $user,
    AuthorizationParams $params
): void;
```

Benefits:
- Auditability
- Role changes survive token expiry
- Reduced coupling to Keycloak

---

### Option B: Embed in Internal Token

In `TokenService::issueTokens()`:

```php
$payload['authz'] = [
    'accessLevels' => $params->accessLevels,
    'hierCodes' => $params->hierCodes,
];
```

Only acceptable if internal tokens are short-lived.

---

## Phase 5 – Map to Internal Roles

```php
final class AuthorizationRoleMapper
{
    public function map(AuthorizationParams $params): array
    {
        $roles = ['ROLE_USER'];

        foreach ($params->accessLevels as $level) {
            if (str_contains($level, 'FleetAdmin')) {
                $roles[] = 'ROLE_FLEET_ADMIN';
            }

            if (str_contains($level, 'ReadOnly')) {
                $roles[] = 'ROLE_READ_ONLY';
            }
        }

        return array_unique($roles);
    }
}
```

Never expose Keycloak strings outside this layer.

---

## Phase 6 – Enforce Fail-Closed Behavior

If:
- `params` missing
- JSON invalid
- Unknown AccessLevel
- Invalid HierCode

➡ Deny access (`403 Forbidden`)

No degraded modes.

---

## Phase 7 – Clean Up

- Remove debug token logging
- Remove fallback logic
- Centralize all authz logic

---

## Phase 8 – Tests (Mandatory)

### Unit tests
- Valid params
- Invalid JSON
- Missing keys
- Invalid prefixes

### Integration test
- Full OIDC login
- Token ingestion
- Role assignment
- Invalid users rejected

---

## Final Notes

- No custom grant required
- No OAuth client extensions required
- All complexity is intentionally isolated

This document represents a **production-grade authorization ingestion pipeline**.
