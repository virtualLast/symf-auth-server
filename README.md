# SSO Authentication Server

A Symfony-based authentication server that provides centralized Single Sign-On (SSO) capabilities through multiple authentication flows: **OIDC**, **SAML**, and **traditional login forms**. This server acts as an authentication gateway, issuing secure session cookies that frontend applications can use to authenticate requests to separate API services.

## Overview

This project centralizes user authentication across multiple identity providers and authentication methods. After successful authentication, users receive HTTP-only cookies containing access and refresh tokens. Frontend applications consume these cookies to make authenticated requests to downstream APIs without managing authentication directly.

## Features

- **Multi-Provider OIDC Support**: Authenticate via OAuth 2.0/OIDC providers (Auth0, Keycloak, Okta)
- **SAML 2.0 Support**: Enterprise SSO via SAML protocol
- **Login Form Authentication**: Traditional username/password authentication
- **Token Management**: Issues internal access and refresh tokens with automatic expiry
- **Cookie-Based Sessions**: Secure, HTTP-only cookies for seamless frontend integration
- **Token Refresh**: Automatic token refresh endpoint for maintaining sessions
- **Multi-Realm Support**: Configure multiple identity provider realms

## Architecture

### Authentication Flow

1. **User Initiates Authentication**
   - OIDC: Redirect to `/oidc/login/{provider}`
   - SAML: Redirect to `/saml/login`
   - Login Form: Navigate to `/auth/login`

2. **Provider Authentication**
   - OIDC: User authenticates with external OAuth provider
   - SAML: User authenticates with SAML identity provider
   - Login Form: User submits credentials directly

3. **Callback Processing**
   - Server receives authentication response
   - Creates or retrieves local user record
   - Issues internal access and refresh tokens
   - Stores tokens in database with expiry times

4. **Cookie Issuance**
   - Sets secure HTTP-only cookies:
     - `access_token`: Short-lived (1 day) access token
     - `refresh_token`: Long-lived (1 month) refresh token
   - Redirects to dashboard (or configured destination)

5. **Frontend Usage**
   - Cookies automatically included in subsequent requests
   - Frontend applications use cookies to authenticate API calls
   - Tokens validated by downstream services

### Key Components

#### Controllers

- **`OidcController`** (`src/Controller/OidcController.php`)
  - Handles OIDC authentication flows
  - Routes: `/oidc/login/{provider}`, `/oidc/callback/{provider}`
  - Supports multiple OAuth providers (Auth0, Keycloak, etc.)

- **`SamlController`** (`src/Controller/SamlController.php`)
  - Manages SAML 2.0 authentication
  - Routes: `/saml/login`, `/saml/acs`, `/saml/meta`
  - Processes SAML assertions and metadata

- **`AuthLoginUserController`** (`src/Controller/AuthLoginUserController.php`)
  - Traditional login form interface
  - Route: `/auth/login`
  - Delegates authentication to `LoginFormAuthenticator`

- **`RefreshController`** (`src/Controller/RefreshController.php`)
  - Token refresh endpoint
  - Route: `/refresh`
  - Issues new access and refresh tokens

- **`DashboardController`** (`src/Controller/DashboardController.php`)
  - Post-authentication landing page
  - Displays authentication cookies for demonstration
  - Can be replaced with redirect to any destination

#### Services

- **`TokenService`** (`src/Service/TokenService.php`)
  - Creates and manages internal tokens
  - Handles token issuance, revocation, and lookup
  - Stores IDP tokens alongside internal tokens

- **`CookieService`** (`src/Service/CookieService.php`)
  - Creates secure HTTP-only cookies
  - Configures cookie attributes (secure, SameSite, etc.)

- **`UserService`** (`src/Service/UserService.php`)
  - Manages user lookup and creation
  - Links authentication providers to local users

- **`ScopeService`** (`src/Service/ScopeService.php`)
  - Determines OAuth scopes per provider

- **`TokenParamsService`** (`src/Service/TokenParamsService.php`)
  - Extracts and parses custom claims from IDP tokens

#### Authenticators

- **`LoginFormAuthenticator`** (`src/Authenticator/LoginFormAuthenticator.php`)
  - Handles traditional login form authentication
  - Validates credentials against database
  - Issues tokens and cookies on success

#### Entities

- **`User`**: Local user records linked to identity providers
- **`Token`**: Stores both IDP tokens and internal access/refresh tokens

## Supported Identity Providers

### OIDC Providers

Configured in `config/packages/knpu_oauth2_client.yaml`:

- **Auth0** (`auth0`)
- **Keycloak Local** (`keycloak_local`) - Development realm
- **Keycloak Tesco** (`keycloak_tesco`) - Custom realm example

### SAML Providers

Configured in `config/saml/settings.php`:

- **Keycloak SAML** (`keycloak_saml`)

### Adding New Providers

1. **OIDC**: Add client configuration to `knpu_oauth2_client.yaml`
2. **SAML**: Update SAML settings and metadata configuration
3. **Provider Enum**: Add entry to `App\Model\Enum\ProviderEnum`
4. **Scopes**: Configure scopes in `ScopeService` if needed

## Installation

### Requirements

- PHP 8.2+
- PostgreSQL 16+
- Composer
- Symfony CLI (optional)

### Setup

1. **Clone repository**
   ```bash
   git clone <repository-url>
   cd symf-auth-server
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env .env.local
   # Edit .env.local with your configuration
   ```

4. **Set up database**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Load fixtures (optional)**
   ```bash
   php bin/console doctrine:fixtures:load
   ```

6. **Start development server**
   ```bash
   symfony server:start up -d && docker compose up -d
   ```

## Configuration

### Environment Variables

Required variables in `.env.local`:

```env
# Database
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/dbname?serverVersion=16&charset=utf8"

# Token Security
REFRESH_TOKEN_SALT=your-random-salt

# OIDC Providers
KEYCLOAK_OIDC_CLIENT_ID=your-client-id
KEYCLOAK_OIDC_CLIENT_SECRET=your-client-secret
AUTH0_OIDC_CLIENT_ID=your-auth0-client-id
AUTH0_OIDC_CLIENT_SECRET=your-auth0-client-secret
```

### Security Configuration

Edit `config/packages/security.yaml` to customize:

- Password hashing algorithms
- Firewall rules
- Access control patterns

### SAML Configuration

Edit `config/saml/settings.php` to configure:

- Service Provider metadata
- Identity Provider endpoints
- Assertion consumer service URL

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/oidc/login/{provider}` | Initiate OIDC authentication |
| `GET` | `/oidc/callback/{provider}` | OIDC callback handler |
| `GET` | `/saml/login` | Initiate SAML authentication |
| `POST` | `/saml/acs` | SAML assertion consumer service |
| `GET` | `/saml/meta` | SAML service provider metadata |
| `GET/POST` | `/auth/login` | Display/submit login form |
| `GET` | `/refresh` | Refresh access token |
| `GET` | `/dashboard` | Post-login dashboard |

## Token Flow

### Access Tokens

- **Lifetime**: 1 day (86400 seconds)
- **Format**: ULID string
- **Storage**: Database + HTTP-only cookie
- **Purpose**: Short-lived authentication credential

### Refresh Tokens

- **Lifetime**: 1 month (2592000 seconds)
- **Format**: ULID string (hashed with SHA-512 in database)
- **Storage**: Database (hashed) + HTTP-only cookie (raw)
- **Purpose**: Obtain new access tokens without re-authentication

### Token Refresh Flow

1. Frontend detects expired access token
2. Makes request to `/refresh` endpoint
3. Server validates refresh token from cookie
4. Issues new access and refresh tokens
5. Old refresh token is revoked
6. New cookies set in response

## Cookie Configuration

Cookies are configured in `CookieService` with:

- **HTTP-Only**: `true` (prevents JavaScript access)
- **Secure**: `true` (requires HTTPS)
- **SameSite**: `Lax` (CSRF protection)
- **Path**: `/` (available site-wide)
- **Domain**: `null` (current domain)

## Frontend Integration

### Using Authentication Cookies

Frontend applications automatically receive cookies after authentication. No additional configuration needed for cookie transmission.

### Example: Making Authenticated API Requests

```javascript
// Cookies automatically included with same-origin requests
fetch('https://api.example.com/protected-resource', {
  credentials: 'include' // Include cookies in cross-origin requests
})
  .then(response => response.json())
  .then(data => console.log(data));
```

### Handling Token Expiry

```javascript
async function fetchWithRefresh(url, options = {}) {
  let response = await fetch(url, { ...options, credentials: 'include' });

  if (response.status === 401) {
    // Token expired, refresh
    await fetch('/refresh', { credentials: 'include' });
    // Retry original request
    response = await fetch(url, { ...options, credentials: 'include' });
  }

  return response;
}
```

## Security Considerations

- **Secrets Management**: Never commit `.env.local` or production secrets
- **HTTPS Required**: Secure cookies require HTTPS in production
- **Token Storage**: Refresh tokens are hashed before database storage
- **CSRF Protection**: Login forms include CSRF token validation
- **Password Hashing**: Uses Symfony's auto algorithm (bcrypt/argon2)

## Customization

### Changing Redirect Destination

Edit the controllers to redirect to your desired destination instead of `app_dashboard_index`:

```php
// In OidcController.php, SamlController.php, LoginFormAuthenticator.php
$response = $this->redirectToRoute('your_custom_route');
```

### Adjusting Token Expiry

Edit constants in `TokenService`:

```php
private const TOKEN_EXPIRY_1_DAY = 86400;      // Access token
private const TOKEN_EXPIRY_1_MONTH = 2592000;  // Refresh token
```

### Custom Claims/Scopes

Implement custom logic in:
- `TokenParamsService`: Extract custom claims from IDP tokens
- `ScopeService`: Define required OAuth scopes per provider
- `ResourceOwnerMapper`: Map IDP user data to local user records

## Next Steps

The following enhancements are planned:

### 1. Dynamic Redirect URI Validation

**Current State**: After successful authentication, users are redirected to a hardcoded dashboard route (`app_dashboard_index`).

**Planned Implementation**:
- Accept a `redirect_uri` query parameter on authentication endpoints
- Validate the redirect URI format using URL parsing
- Check the validated URI against a whitelist stored in the database or configuration
- Redirect users to the validated URI after successful authentication with cookies set
- Return an error response if the redirect URI is invalid or not whitelisted

**Implementation Tasks**:
1. Create a `RedirectUri` entity or configuration to store whitelisted domains/URLs
2. Create a `RedirectUriService` to validate and whitelist URIs
3. Update `OidcController::callback()` to accept and validate `redirect_uri` parameter
4. Update `SamlController::acs()` to accept and validate `redirect_uri` parameter
5. Update `LoginFormAuthenticator::onAuthenticationSuccess()` to handle redirect URIs
6. Store the original `redirect_uri` in session during login initiation
7. Add admin interface or console command to manage whitelisted URIs

**Example Usage**:
```
/oidc/login/auth0?redirect_uri=https://app.example.com/dashboard
/auth/login?redirect_uri=https://app.example.com/home
```

### 2. "Sign in with Fleet" - Organization-Based Provider Selection

**Current State**: Users must know the exact provider endpoint to initiate authentication.

**Planned Implementation**:
- Create an `Organization` (or `Fleet`) entity with fields:
  - Name (e.g., "Acme Corporation", "Tesco Logistics")
  - Display name for dropdown
  - Associated identity provider (references `ProviderEnum`)
  - Optional logo/branding
  - Active/inactive status
- Display a landing page with a dropdown listing all active organizations
- When user selects an organization, redirect to the appropriate `/oidc/login/{provider}` endpoint
- User completes normal OIDC authentication flow
- Maintain `redirect_uri` throughout the flow

**Implementation Tasks**:
1. Create `Organization` entity with:
   - `name` (string)
   - `displayName` (string)
   - `provider` (ProviderEnum)
   - `isActive` (boolean)
2. Create `OrganizationRepository` and `OrganizationService`
3. Create migration and fixtures for organizations
4. Create new landing page route (e.g., `/auth/select-organization`)
5. Build organization selection UI with dropdown/cards
6. Implement JavaScript/form submission to redirect to correct provider endpoint
7. Create API endpoint to fetch active organizations (for dynamic dropdown population)
8. Add admin CRUD interface for managing organizations
9. Update authentication flow to track selected organization in session/token

