<?php

namespace App\Tests\Controller;

use App\Kernel;
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
        $this->tokenService = $this->createMock(TokenService::class);
        $this->cookieService = new CookieService();
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function test_logout_revokes_token_and_clears_cookies_and_redirects(): void
    {
        // Arrange
        // - Create client
        $client = static::createClient();

        $this->tokenService
            ->expects($this->once())
            ->method('revokeToken')
            ->with(CookieService::REFRESH_COOKIE_NAME)
        ;
        $this->tokenService
            ->expects($this->once())
            ->method('revokeToken')
            ->with(CookieService::ACCESS_COOKIE_NAME)
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
        $client->request('POST', '/logout', [
            'redirect_url' => 'https://example.com/after-logout',
        ]);

        // Assert
        // - Response is a redirect
        $response = $client->getResponse();
        $this->assertSame(302, $response->getStatusCode());
        // - Location header matches redirect_url
        // - Access and refresh cookies are cleared
        // - TokenService::revokeToken was called
    }

    public function test_logout_fails_when_refresh_token_cookie_is_missing(): void
    {
        // Arrange
        // - Create client without refresh token cookie
        // - Provide redirect_url

        // Act
        // - Perform POST /logout

        // Assert
        // - Response status is 400
        // - Error message indicates missing refresh token
    }

    public function test_logout_fails_when_redirect_url_is_missing(): void
    {
        // Arrange
        // - Create client with refresh token cookie
        // - Do not include redirect_url in POST data

        // Act
        // - Perform POST /logout

        // Assert
        // - Response status is 400
        // - Error message indicates missing redirect location
    }

    public function test_logout_fails_when_redirect_url_is_invalid(): void
    {
        // Arrange
        // - Create client with refresh token cookie
        // - Provide invalid redirect_url value

        // Act
        // - Perform POST /logout

        // Assert
        // - Response status is 400
        // - Error message indicates invalid redirect location
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
