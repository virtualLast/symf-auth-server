# Auth Server – Next Steps

Inside your callback, you now have:

Keycloak user identity

Keycloak access token

Keycloak refresh token (inside $accessToken->getRefreshToken())

Your tasks now are:

Resolve or create a local User in your DB

Store the Keycloak refresh token

Generate your own opaque access_token

Persist it in user_token table

Set your cookie using your token, not Keycloak’s

Return your callback response

## 2. Update User Login Flow
- After validating user credentials:
  - Generate refresh token.
  - Persist token entity.
  - Return cookies:
    - HttpOnly `access_token`
    - HttpOnly `refresh_token`

## 3. Build Refresh Token Endpoint
- Read refresh token from cookie.
- Validate token:
  - Exists
  - Not expired
  - Not revoked
- Rotate token:
  - Create a new Token entity
  - Mark old one revoked
- Issue new access token + refresh token.

## 4. Add Logout Endpoint
- Read refresh token.
- Mark token revoked in DB.
- Clear cookies.

## 5. Add Middleware for Access Token Validation
- Parse access token.
- Validate signature & expiry.
- Load user from DB if needed.
- Attach user to request.

## 6. Session Management Admin View (Optional)
- List tokens per user.
- Allow manual revocation.
- Useful for debugging and support.

## 7. Add Tests
- Login flow
- Refresh flow
- Token rotation
- Revoked token re-use
- Logout
