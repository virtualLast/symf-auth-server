<?php

namespace App\Tests\Controller;

use App\Model\Enum\ProviderEnum;
use App\Service\ScopeService;
use App\Service\TokenParamsService;
use App\Service\TokenService;
use App\Service\UserService;
use App\Mapper\ResourceOwnerMapper;
use App\Service\CookieService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\DependencyInjection\InvalidOAuth2ClientException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OidcControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ClientRegistry $clientRegistry;
    private OAuth2ClientInterface $oauth2Client;
    private TokenParamsService $tokenParamsService;
    private TokenService $tokenService;
    private UserService $userService;
    private ResourceOwnerMapper $resourceOwnerMapper;
    private CookieService $cookieService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clientRegistry = $this->createMock(ClientRegistry::class);
        $this->oauth2Client = $this->createMock(OAuth2ClientInterface::class);
        $this->tokenParamsService = $this->createMock(TokenParamsService::class);
        $this->tokenService = $this->createMock(TokenService::class);
        $this->userService = $this->createMock(UserService::class);
        $this->resourceOwnerMapper = $this->createMock(ResourceOwnerMapper::class);
        $this->cookieService = $this->createMock(CookieService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->client = static::createClient();

        static::getContainer()->set(ClientRegistry::class, $this->clientRegistry);
        static::getContainer()->set(TokenParamsService::class, $this->tokenParamsService);
        static::getContainer()->set(TokenService::class, $this->tokenService);
        static::getContainer()->set(UserService::class, $this->userService);
        static::getContainer()->set(ResourceOwnerMapper::class, $this->resourceOwnerMapper);
        static::getContainer()->set(CookieService::class, $this->cookieService);
    }

    /**
     * Test callback when the OAuth provider throws IdentityProviderException.
     */
    public function test_callback_identity_provider_exception(): void
    {
        // arrange
        $provider = $this->getProvider();
        $this->clientRegistry
            ->method('getClient')
            ->willReturn($this->oauth2Client)
        ;

        $this->oauth2Client
            ->method('getAccessToken')
            ->willThrowException($this->createMock(IdentityProviderException::class))
        ;

        // act
        $this->client->request('GET', '/oidc/callback/' . $provider->value);
        $response = $this->client->getResponse();

        // assert
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Token callback error', $response->getContent());
    }

    /**
     * Test callback with a successful OAuth flow.
     */
    public function test_callback_success(): void
    {
        // arrange
        $provider = $this->getProvider();
        $this->clientRegistry
            ->method('getClient')
            ->willReturn($this->oauth2Client)
        ;

        $accessToken = $this->createMock(AccessToken::class);
        $this->oauth2Client->method('getAccessToken')->willReturn($accessToken);

        $resourceOwner = $this->createMock(ResourceOwnerInterface::class);
        $this->oauth2Client->method('fetchUserFromToken')->willReturn($resourceOwner);

        $this->tokenParamsService
            ->method('parse')
            ->willReturn(null)
        ;

        $this->resourceOwnerMapper
            ->method('map')
            ->willReturn($this->createMock(\App\Model\Dto\ResourceOwnerDto::class))
        ;

        $localUser = $this->createMock(\App\Entity\User::class);
        $this->userService
            ->method('findOrCreate')
            ->willReturn($localUser)
        ;

        $tokenEntity = new \App\Entity\Token();
        $tokenEntity->setLocalAccessToken('mock_access_token');
        $tokenEntity->setRawLocalRefreshToken('mock_refresh_token');
        $tokenEntity->setLocalAccessTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
        $tokenEntity->setLocalRefreshTokenExpiresAt(new \DateTimeImmutable('+1 month'));

        $this->tokenService->method('createToken')->willReturn($tokenEntity);
        $this->tokenService->method('issueTokens')->willReturn($tokenEntity);

        $this->cookieService
            ->method('createAccess')
            ->willReturn(new \Symfony\Component\HttpFoundation\Cookie('access_token', 'mock_access_token'))
        ;
        $this->cookieService
            ->method('createRefresh')
            ->willReturn(new \Symfony\Component\HttpFoundation\Cookie('refresh_token', 'mock_refresh_token'))
        ;

        // act
        $this->client->request('GET', '/oidc/callback/' . $provider->value);
        $response = $this->client->getResponse();

        // assert
        $this->assertSame(302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect('/dashboard/'));

        $cookies = $response->headers->getCookies();
        $this->assertCount(2, $cookies);
        $this->assertSame('access_token', $cookies[0]->getName());
        $this->assertSame('mock_access_token', $cookies[0]->getValue());
        $this->assertSame('refresh_token', $cookies[1]->getName());
        $this->assertSame('mock_refresh_token', $cookies[1]->getValue());
    }

    /**
     * Test callback when token issuance fails.
     */
    public function test_callback_token_issuance_exception(): void
    {
        // arrange
        $provider = $this->getProvider();
        $this->clientRegistry
            ->method('getClient')
            ->willReturn($this->oauth2Client)
        ;

        $accessToken = $this->createMock(AccessToken::class);
        $this->oauth2Client->method('getAccessToken')->willReturn($accessToken);
        $this->oauth2Client->method('fetchUserFromToken')->willReturn($this->createMock(ResourceOwnerInterface::class));

        $this->tokenService
            ->method('createToken')
            ->willThrowException(new \Exception('Issuance error'))
        ;

        // act
        $this->client->request('GET', '/oidc/callback/' . $provider->value);
        $response = $this->client->getResponse();

        // assert
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Token Issuance callback error', $response->getContent());
    }

    /**
     * Test callback when parsing access roles fails.
     */
    public function test_callback_token_params_parse_exception(): void
    {
        // arrange
        $provider = $this->getProvider();
        $this->clientRegistry
            ->method('getClient')
            ->willReturn($this->oauth2Client)
        ;

        $accessToken = $this->createMock(AccessToken::class);
        $this->oauth2Client->method('getAccessToken')->willReturn($accessToken);
        $this->oauth2Client->method('fetchUserFromToken')->willReturn($this->createMock(ResourceOwnerInterface::class));

        $this->tokenParamsService
            ->method('parse')
            ->willThrowException(new \Exception('Parse error'))
        ;

        // act
        $this->client->request('GET', '/oidc/callback/' . $provider->value);
        $response = $this->client->getResponse();

        // assert
        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Access Roles callback error', $response->getContent());
    }

    /**
     * test private getOAuthClientOr404 indirectly.
     */
    public function test_get_oauth_client_or_404_invalid_client(): void
    {
        // arrange
        $provider = $this->getProvider();
        $this->clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with($provider->value)
            ->willThrowException(new InvalidOAuth2ClientException())
        ;

        // act
        $this->client->request('GET', '/oidc/login/' . $provider->value);
        $response = $this->client->getResponse();

        // assert
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * Test that login redirects to the OAuth provider with correct scopes.
     */
    public function test_login_redirects_to_provider(): void
    {
        // arrange
        $provider = $this->getProvider();
        $this->clientRegistry
            ->expects($this->once())
            ->method('getClient')
            ->with($provider->value)
            ->willReturn($this->oauth2Client)
        ;

        $this->oauth2Client
            ->expects($this->once())
            ->method('redirect')
            ->with(['openid', 'profile', 'email'], [])
            ->willReturn(new RedirectResponse('https://oauth-provider.com/auth'))
        ;

        // act
        $this->client->request('GET', '/oidc/login/' . $provider->value);
        $response = $this->client->getResponse();

        // assert
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://oauth-provider.com/auth', $response->headers->get('Location'));
    }

    /**
     * Test that login with an unknown provider returns a 404.
     */
    public function test_login_unknown_provider_throws_not_found(): void
    {
        // act
        $this->client->request('GET', '/oidc/login/non_existent_provider');
        $response = $this->client->getResponse();

        // assert
        $this->assertSame(404, $response->getStatusCode());
    }

    private function getProvider(): ProviderEnum
    {
        return ProviderEnum::KEYCLOAK_LOCAL;
    }
}
