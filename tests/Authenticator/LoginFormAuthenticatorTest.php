<?php

namespace App\Tests\Authenticator;

use App\Authenticator\LoginFormAuthenticator;
use App\Entity\User;
use App\Service\CookieService;
use App\Service\TokenService;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class LoginFormAuthenticatorTest extends TestCase
{

    private UserService $userService;
    private TokenService $tokenService;
    private CookieService $cookieService;
    private UrlGeneratorInterface $urlGenerator;

    private LoginFormAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserService::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->tokenService = $this->createMock(TokenService::class);
        $this->cookieService = $this->createMock(CookieService::class);

        $this->authenticator = new LoginFormAuthenticator(
            $this->userService,
            $this->urlGenerator,
            $this->tokenService,
            $this->cookieService
        );
    }

    public function test_authenticate_returns_passport_for_valid_request(): void
    {
        // Arrange
        // - Create a Request with email, password, csrf_token
        $request = $this->createRequest();
        // - Configure UserService::findByEmail to return a User
        $this->userService
            ->method('findByEmail')
            ->willReturn($this->createUser())
        ;

        // Act
        // - Call $this->authenticator->authenticate($request)
        $passport = $this->authenticator->authenticate($request);

        // Assert
        // - Passport is returned
        // - Passport contains UserBadge and PasswordCredentials
        $this->assertIsArray($passport->getBadges());
        $badges = $passport->getBadges();
        $this->assertContains(UserBadge::class, array_keys($badges));
        $this->assertContains(PasswordCredentials::class, array_keys($badges));
        $this->assertContains(CsrfTokenBadge::class, array_keys($badges));
    }

    public function test_authenticate_throws_exception_when_email_is_missing(): void
    {
        // Arrange
        // - Request missing 'email'
        $request = $this->createRequest(['email' => null]);

        // Assert
        // - Expect AuthenticationException
        $this->expectException(AuthenticationException::class);
        $this->authenticator->authenticate($request);
    }

    public function test_authenticate_throws_exception_when_password_is_missing(): void
    {
        // Arrange
        // - Request missing 'password'
        $request = $this->createRequest(['password' => null]);

        // Assert
        // - Expect AuthenticationException
        $this->expectException(AuthenticationException::class);
        $this->authenticator->authenticate($request);
    }

    public function test_authenticate_throws_exception_when_csrf_token_is_missing(): void
    {
        // Arrange
        // - Request missing 'csrf_token'
        $request = $this->createRequest(['csrf_token' => null]);

        // Assert
        // - Expect AuthenticationException
        $this->expectException(AuthenticationException::class);
        $this->authenticator->authenticate($request);
    }

    public function test_user_badge_loader_uses_user_service(): void
    {
        // Arrange
        // - Create Request with valid data
        $email = 'user@example.com';
        $request = $this->createRequest(['email' => $email]);
        // - Configure UserService mock to expect findByEmail() call
        $this->userService
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($this->createUser());

        // Act
        // - Call authenticate()
        $passport = $this->authenticator->authenticate($request);
        // - Extract UserBadge from Passport
        $userBadge = $passport->getBadge(UserBadge::class);
        // - Trigger user loader closure
        $user = $userBadge->getUser();
        $this->assertInstanceOf(User::class, $user);

        // Assert
        // - UserService::findByEmail was called
        $this->assertSame($email, $user->getEmail());
    }

    public function test_authenticate_allows_unknown_user_to_propagate_failure(): void
    {
        // Arrange
        // - UserService::findByEmail returns null
        $request = $this->createRequest(['email' => 'unknown@example.com']);
        $this->userService
            ->method('findByEmail')
            ->willReturn(null)
        ;

        // Act
        // - authenticate()
        $passport = $this->authenticator->authenticate($request);

        // Assert
        $userBadge = $passport->getBadge(UserBadge::class);
        $this->expectException(UserNotFoundException::class);
        $userBadge->getUser();
        // - Actual failure happens later in security flow
    }

    private function createRequest(array $args = []): Request
    {
        $authUrl = $this->urlGenerator->generate('app_auth_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
        return Request::create(
            $authUrl,
            'POST',
            array_merge(
                [
                    'email' => 'user@example.com',
                    'password' => 'secret',
                    'csrf_token' => 'token',
                ],
                $args
            )

        );
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('user@example.com');

        return $user;
    }
}
