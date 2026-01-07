<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Service\CookieService;
use App\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

class LogoutControllerTest extends WebTestCase
{

    private TokenService $tokenService;
    private CookieService $cookieService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = $this->createMock(TokenService::class);
        $this->cookieService = new CookieService();

    }

    public function test_logout_revokes_token_and_clears_cookies_and_redirects(): void
    {
        // Arrange
        // - Create client
        $client = static::createClient();

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setRoles(['ROLE_USER']);
        $client->loginUser($user);

        $this->tokenService
            ->expects($this->once())
            ->method('revokeToken')
            ->with(CookieService::REFRESH_COOKIE_NAME)
        ;

        static::getContainer()->set(TokenService::class, $this->tokenService);
        // - Set refresh token cookie
        $client->getCookieJar()
            ->set(
                $this->createRefreshTokenCookie()
            )
        ;
        $client->getCookieJar()
            ->set(
                $this->createAccessTokenCookie()
            )
        ;
        // - Send POST request with valid redirect_url

        // Act
        // - Perform POST /logout
        $client->request('POST', '/logout/', [
            'redirect_url' => 'https://example.com/after-logout',
        ]);

        // Assert
        // - Response is a redirect
        $response = $client->getResponse();
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://example.com/after-logout', $response->headers->get('Location'));

        // - Access and refresh cookies are cleared
        $response = $client->getResponse();
        $this->assertCount(2, $response->headers->getCookies());
        foreach ($response->headers->getCookies() as $cookie) {
            $this->assertTrue($cookie->getExpiresTime() < time());
        }
    }

    public function test_logout_fails_when_refresh_token_cookie_is_missing(): void
    {
        // Arrange
        $client = static::createClient();
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setRoles(['ROLE_USER']);
        $client->loginUser($user);

        // Act
        $client->request('POST', '/logout/', [
            'redirect_url' => 'https://example.com/after-logout',
        ]);

        // Assert
        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Refresh token not found', $response->getContent());
    }

    public function test_logout_fails_when_redirect_url_is_missing(): void
    {
        // Arrange
        $client = static::createClient();
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setRoles(['ROLE_USER']);
        $client->loginUser($user);

        $client->getCookieJar()->set($this->createRefreshTokenCookie());

        // Act
        $client->request('POST', '/logout/', []);

        // Assert
        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Redirect location not found', $response->getContent());
    }

    public function test_logout_fails_when_redirect_url_is_invalid(): void
    {
        // Arrange
        $client = static::createClient();
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setRoles(['ROLE_USER']);
        $client->loginUser($user);

        $client->getCookieJar()->set($this->createRefreshTokenCookie());

        // Act
        $client->request('POST', '/logout/', [
            'redirect_url' => 'not-a-url',
        ]);

        // Assert
        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid redirect location', $response->getContent());
    }

    private function createRefreshTokenCookie(): Cookie
    {
        $cookie = $this->cookieService->createRefresh(CookieService::REFRESH_COOKIE_NAME, new \DateTimeImmutable('+1 month'));
        return new Cookie(
            $cookie->getName(),
            $cookie->getValue(),
            $cookie->getExpiresTime()
        );
    }

    private function createAccessTokenCookie(): Cookie
    {
        $cookie = $this->cookieService->createAccess(CookieService::ACCESS_COOKIE_NAME, new \DateTimeImmutable('+1 hour'));
        return new Cookie(
            $cookie->getName(),
            $cookie->getValue(),
            $cookie->getExpiresTime()
        );
    }
}
