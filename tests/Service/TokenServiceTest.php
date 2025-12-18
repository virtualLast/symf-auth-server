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
        // arrange
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

        // act
        $result = $this->tokenService->revokeToken($accessToken);

        // assert
        self::assertTrue($result);
        self::assertTrue($token->isRevoked());
    }

    public function test_it_returns_false_when_revoking_a_nonexistent_token(): void
    {
        // arrange
        $accessToken = $this->createAccessToken();

        $this->tokenRepository
            ->method('findOneBy')
            ->willReturn(null);

        // act + assert
        self::assertFalse(
            $this->tokenService->revokeToken($accessToken)
        );
    }

    /**
     * You currently only assert type, not correctness.
     * This test would assert:
     * the expiry timestamp matches $accessToken->getExpires()
     * @throws \Exception
     */
    public function test_it_sets_idp_access_token_expiry_from_oauth_token(): void
    {
        // arrange
        $expectedExpiry = time() + 7200;
        $accessToken = new AccessToken([
            'access_token' => 'idp-access',
            'refresh_token' => 'idp-refresh',
            'expires' => $expectedExpiry,
        ]);

        // act
        $token = $this->tokenService->createToken($accessToken);

        // assert
        self::assertSame($expectedExpiry, $token->getIdpAccessTokenExpiresAt()->getTimestamp());
    }

    /**
     * If issueTokens() is called twice on the same Token,
     * Token values overrite each other.
     * @throws \Exception
     */
    public function test_it_overwrites_existing_local_tokens_when_reissuing(): void
    {
        // arrange
        $token = new Token();
        $user = new User();

        $this->tokenRepository
            ->method('findOneBy')
            ->willReturn($token);

        // act - issue tokens first time
        $firstIssuedToken = $this->tokenService->issueTokens($token, $user);
        $firstAccessToken = $firstIssuedToken->getLocalAccessToken();
        $firstRefreshToken = $firstIssuedToken->getLocalRefreshToken();

        // act - issue tokens second time
        $secondIssuedToken = $this->tokenService->issueTokens($token, $user);
        $secondAccessToken = $secondIssuedToken->getLocalAccessToken();
        $secondRefreshToken = $secondIssuedToken->getLocalRefreshToken();

        // assert - tokens are different
        self::assertNotEquals(
            $firstAccessToken,
            $secondAccessToken
        );
        self::assertNotEquals(
            $firstRefreshToken,
            $secondRefreshToken
        );
    }

    /**
     * Guards against future refactors that accidentally return an unpersisted token.
     * This is subtle but valuable in auth code.
     * @throws \Exception
     */
    public function test_issue_tokens_returns_persisted_token_from_repository(): void
    {
        // arrange
        $token = new Token();
        $user = new User();

        $this->tokenRepository
            ->expects(self::once())
            ->method('save')
            ->with($token);

        $persistedToken = new Token();
        $persistedToken->setLocalAccessToken('persisted-access');
        $persistedToken->setLocalRefreshToken('persisted-refresh');

        $this->tokenRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn($persistedToken);

        // act
        $result = $this->tokenService->issueTokens($token, $user);

        // assert - ensures the returned token is from the repository (persisted)
        self::assertSame('persisted-access', $result->getLocalAccessToken());
        self::assertSame('persisted-refresh', $result->getLocalRefreshToken());
    }

    /**
     * This locks down behaviour around double-revocation and prevents accidental re-writes later.
     */
    public function test_revoking_an_already_revoked_token_returns_true(): void
    {
        // arrange
        $accessToken = $this->createAccessToken();

        $token = new Token();
        $token->setLocalRefreshToken('local-refresh');
        $token->setRevoked(true);

        $this->tokenRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn($token);

        $this->tokenRepository
            ->expects(self::never())
            ->method('save')
            ->with($token);

        // act
        $result = $this->tokenService->revokeToken($accessToken);

        // assert
        self::assertTrue($result);
        self::assertTrue($token->isRevoked());
    }

    /**
     * You assert return value, but not side effects.
     * This ensures no accidental writes happen.
     */
    public function test_revoke_token_does_not_persist_when_token_not_found(): void
    {
        // arrange
        $accessToken = $this->createAccessToken();

        $token = new Token();
        $token->setLocalRefreshToken('local-refresh');

        $this->tokenRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->tokenRepository
            ->expects(self::never())
            ->method('save')
            ->with($token);

        // act
        $result = $this->tokenService->revokeToken($accessToken);

        // assert
        self::assertFalse($result);
    }

    public function test_local_access_and_refresh_tokens_are_distinct(): void
    {
        // arrange
        $token = new Token();
        $user = new User();

        $this->tokenRepository
            ->method('findOneBy')
            ->willReturn($token);

        // act
        $issuedToken = $this->tokenService->issueTokens($token, $user);

        // assert
        self::assertNotSame(
            $issuedToken->getLocalAccessToken(),
            $issuedToken->getLocalRefreshToken()
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
