# Code Review - Symfony Auth Server

**Reviewer:** AI Code Review  
**Project:** Symfony 7.3 Authentication Server (SAML 2.0 + OIDC)

---

## Executive Summary

The codebase demonstrates a solid foundation with clean architecture, modern PHP practices, and good separation of concerns. However, there are **critical security gaps** that must be addressed before production deployment, particularly around Symfony Security integration and error handling.

**Overall Assessment:** ⚠️ **Good foundation, but requires security hardening**

---

## Strengths

### 1. Architecture & Design
- ✅ Clean separation of concerns (Controllers, Services, Entities, Mappers, DTOs)
- ✅ Proper use of dependency injection throughout
- ✅ Good use of value objects (DTOs) and enums
- ✅ Clear separation between SAML and OIDC flows

### 2. Code Quality
- ✅ Modern PHP 8.2+ features (strict types, readonly classes, enums, attributes)
- ✅ Strong typing with DTOs and enums
- ✅ Proper use of Doctrine lifecycle callbacks
- ✅ Good database design with proper relationships and constraints

### 3. Best Practices
- ✅ Constructor injection for dependencies
- ✅ Repository pattern implementation
- ✅ Service layer abstraction
- ✅ Proper use of readonly classes where appropriate

---

## Critical Issues 🔴

## Code Quality Issues 🟡

## Design Concerns 🟡

### 10. Service Responsibility Violations

**Location:** `src/Service/UserService.php`

**Issue:**
- `UserService` mixes multiple concerns:
  - User lookup
  - User creation
  - Role synchronization
  - Access level mapping

**Recommendation:**
- Split into focused services:
  - `UserRepository` for queries
  - `UserFactory` for creation
  - `UserSynchronizer` for updates
  - Keep `UserService` as facade/orchestrator

**Priority:** **LOW** (refactoring)

---

### 12. Missing Abstraction for Provider-Specific Logic

**Location:** `src/Service/TokenParamsService.php` (lines 17-21)

**Issue:**
- Hardcoded provider check
- Not extensible for new providers
- Violates Open/Closed Principle

**Current Code:**
```php
public function parse(ResourceOwnerInterface $resourceOwner, ProviderEnum $provider): ?AccessRolesDto
{
    if ($provider !== ProviderEnum::KEYCLOAK_TESCO) {
        return null;
    }
    // ...
}
```

**Recommendation:**
- Use Strategy pattern
- Create provider-specific parsers
- Register parsers via service configuration

**Priority:** **LOW** (but good for maintainability)

---

## Missing Features 🔵

### 13. No Token Refresh Mechanism

**Location:** `src/Service/TokenService.php`

**Issue:**
- Refresh tokens are stored but never used
- No endpoint to refresh expired access tokens
- No automatic token refresh logic

**Impact:** Users must re-authenticate when access token expires

**Recommendation:**
1. Create `/oidc/refresh` endpoint
2. Implement refresh token validation
3. Issue new access/refresh token pair
4. Consider automatic refresh in middleware

**Priority:** **HIGH** (for production)

---

### 14. Incomplete Logout Implementation

**Location:** `src/Controller/OidcController.php` (lines 48-52)

**Issue:**
- Stub implementation only
- No token revocation
- No session cleanup
- No redirect to provider logout

**Current Code:**
```php
#[Route('/logout/{provider}', name: 'oidc_logout')]
public function logout(string $provider): Response
{
    return new Response('logout.');
}
```

**Recommendation:**
1. Revoke tokens in database
2. Clear cookies
3. Optionally redirect to provider logout
4. Clear session data

**Priority:** **HIGH**

---

### 15. No Token Validation Middleware

**Location:** Missing entirely

**Issue:**
- No middleware to validate access tokens on protected routes
- No integration with Symfony Security
- Dashboard and other routes unprotected

**Recommendation:**
1. Create token validation authenticator
2. Integrate with Symfony Security
3. Validate token expiration
4. Check token revocation status

**Priority:** **HIGH** (critical for security)

---

### 16. SAML Logout Not Implemented

**Location:** `src/Controller/SamlController.php` (lines 62-68)

**Issue:**
- Logout method exists but doesn't handle SLO properly
- No session cleanup
- No redirect handling

**Priority:** **MEDIUM**

---

## Testing Concerns 🟡

### 17. Limited Test Coverage

**Location:** `tests/` directory

**Issue:**
- Tests exist but coverage appears limited
- No integration tests for authentication flows
- No tests for error scenarios
- No tests for security edge cases

**Recommendation:**
1. Add integration tests for SAML flow
2. Add integration tests for OIDC flow
3. Test error handling scenarios
4. Test token validation and refresh
5. Test security edge cases

**Priority:** **MEDIUM**

---

## Recommendations Priority List

### 🔴 High Priority (Must Fix Before Production)

1. **Implement Symfony Security Integration**
   - Create custom authenticator
   - Configure firewall with authentication
   - Add access control rules
   - Integrate token validation

2. **Fix Error Handling**
   - Add comprehensive error logging
   - Create user-friendly error pages
   - Hide sensitive error details from users
   - Use Symfony's error handling system

3. **Implement Token Validation Middleware**
   - Create token authenticator
   - Validate token expiration
   - Check token revocation
   - Protect routes

4. **Complete Logout Implementation**
   - Revoke tokens
   - Clear cookies and sessions
   - Handle provider logout redirects

5. **Move Hardcoded URLs to Environment Variables**
   - Use Symfony parameters
   - Environment-specific configs

### 🟡 Medium Priority (Should Fix Soon)

6. **Implement Token Refresh Endpoint**
   - Create refresh endpoint
   - Validate refresh tokens
   - Issue new token pairs

7. **Add Input Validation**
   - Validate email formats
   - Validate token structures
   - Validate claim formats
   - Use Symfony Validator

8. **Improve Error Handling Consistency**
   - Standardize error handling
   - Use custom exception types
   - Better error context

9. **Add Integration Tests**
   - Test authentication flows
   - Test error scenarios
   - Test security edge cases

10. **Refactor Provider-Specific Logic**
    - Use Strategy pattern for parsers
    - Make extensible for new providers

### 🔵 Low Priority (Nice to Have)

11. **Consider JWT for Internal Tokens**
    - Add signing/encryption
    - Better token security

12. **Refactor Service Responsibilities**
    - Split UserService
    - Better separation of concerns

13. **Add More Comprehensive Unit Tests**
    - Increase coverage
    - Test edge cases

---

## Code Examples for Fixes

### Example 1: Proper Error Handling

```php
// In OidcController::callback()
try {
    $accessToken = $client->getAccessToken();
    $remoteUser = $client->fetchUserFromToken($accessToken);
} catch (IdentityProviderException $e) {
    $this->logger->error('OIDC callback error', [
        'provider' => $provider->value,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    $this->addFlash('error', 'Authentication failed. Please try again.');
    return $this->redirectToRoute('app_login');
}
```

### Example 2: Token Validation Authenticator

```php
// src/Security/TokenAuthenticator.php
class TokenAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        return $request->cookies->has(CookieService::ACCESS_COOKIE_NAME);
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->cookies->get(CookieService::ACCESS_COOKIE_NAME);
        
        $tokenEntity = $this->tokenRepository->findByLocalAccessToken($token);
        
        if (!$tokenEntity || $tokenEntity->isRevoked()) {
            throw new AuthenticationException('Invalid token');
        }
        
        if ($tokenEntity->getLocalAccessTokenExpiresAt() < new \DateTimeImmutable()) {
            throw new AuthenticationException('Token expired');
        }
        
        return new SelfValidatingPassport(
            new UserBadge($tokenEntity->getUser()->getUserIdentifier())
        );
    }
}
```

### Example 3: Environment-Based Configuration

```php
// config/services.yaml
parameters:
    saml.metadata_url: '%env(SAML_METADATA_URL)%'
    saml.idp.entity_id: '%env(SAML_IDP_ENTITY_ID)%'
    saml.sp.entity_id: '%env(SAML_SP_ENTITY_ID)%'
```

---

## Security Checklist

Before deploying to production, ensure:

- [ ] Symfony Security firewall configured with authentication
- [ ] Access control rules defined for all protected routes
- [ ] Error handling doesn't expose sensitive information
- [ ] All errors are logged with context
- [ ] Token validation middleware implemented
- [ ] Token refresh endpoint implemented
- [ ] Logout properly revokes tokens and clears sessions
- [ ] All hardcoded URLs moved to environment variables
- [ ] Input validation added for all user inputs
- [ ] Integration tests cover authentication flows
- [ ] Security edge cases tested
- [ ] Error scenarios tested
- [ ] Token expiration and revocation tested

---

## Additional Notes

### Positive Highlights

- Excellent use of DTOs and value objects
- Proper use of readonly classes where appropriate
- Clean separation between SAML and OIDC flows
- Good database design with proper relationships
- Modern PHP practices throughout

### Architecture Suggestions

Consider implementing:
- Event-driven architecture for authentication events
- Command/Query separation for complex operations
- API versioning if exposing endpoints
- Rate limiting for authentication endpoints
- Audit logging for security events

---

## Conclusion

The codebase shows good architectural decisions and modern PHP practices. The main concerns are around **security integration** and **error handling**, which are critical for production deployment. Once these are addressed, the application will be well-positioned for production use.

**Estimated Effort:** 
- High priority items: 2-3 weeks
- Medium priority items: 1-2 weeks
- Low priority items: 1 week

**Total:** ~4-6 weeks for complete hardening

---

**Document Version:** 1.0

