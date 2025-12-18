<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
use App\Service\TokenService;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;

final class TokenServiceTest extends TestCase
{
    private TokenRepository $tokenRepository;
    private TokenService $tokenService;

    protected function setUp(): void
    {
        $this->tokenRepository = $this->createMock(TokenRepository::class);
        $this->tokenService = new TokenService($this->tokenRepository);
    }

    /**
     * create new access token and pass to create token
     * @throws \Exception
     */
    public function test_it_creates_a_token_from_an_idp_access_token(): void
    {
        // arrange
        $accessToken = $this->createAccessToken();

        // act
        $token = $this->tokenService->createToken($accessToken);

        // assert
        self::assertSame('idp-access', $token->getIdpAccessToken());
        self::assertSame('idp-refresh', $token->getIdpRefreshToken());
        self::assertInstanceOf(\DateTimeImmutable::class, $token->getIdpAccessTokenExpiresAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $token->getIdpRefreshTokenExpiresAt());
    }

    /**
     * create a user and token,
     * state the tokenRepo will return the token,
     * passing this data into the issueTokens method will mean the data gets persisted and correctly attached
     * @throws \Exception
     */
    public function test_it_issues_local_tokens_and_persists_them(): void
    {
        // arrange
        $token = new Token();
        $user = new User();

        $this->tokenRepository
            ->expects(self::once())
            ->method('save')
            ->with($token);

        $this->tokenRepository
            ->method('findOneBy')
            ->willReturn($token);

        // act
        $issuedToken = $this->tokenService->issueTokens($token, $user);

        // assert
        self::assertSame($user, $issuedToken->getUser());
        self::assertNotNull($issuedToken->getLocalAccessToken());
        self::assertNotNull($issuedToken->getLocalRefreshToken());
        self::assertInstanceOf(\DateTimeImmutable::class, $issuedToken->getLocalAccessTokenExpiresAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $issuedToken->getLocalRefreshTokenExpiresAt());
    }

    public function test_it_revokes_an_existing_token(): void
    {
        $accessToken = $this->createAccessToken();

        $token = new Token();
        $token->setLocalRefreshToken('local-refresh');

        $this->tokenRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn($token);

        $this->tokenRepository
            ->expects(self::once())
            ->method('save')
            ->with($token);

        $result = $this->tokenService->revokeToken($accessToken);

        self::assertTrue($result);
        self::assertTrue($token->isRevoked());
    }

    public function test_it_returns_false_when_revoking_a_nonexistent_token(): void
    {
        $accessToken = $this->createAccessToken();

        $this->tokenRepository
            ->method('findOneBy')
            ->willReturn(null);

        self::assertFalse(
            $this->tokenService->revokeToken($accessToken)
        );
    }

    private function createAccessToken(): AccessToken
    {
        return new AccessToken([
            'access_token' => 'idp-access',
            'refresh_token' => 'idp-refresh',
            'expires' => time() + 3600,
        ]);
    }
}
