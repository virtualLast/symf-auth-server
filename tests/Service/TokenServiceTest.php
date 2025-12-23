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

    private const REFRESH_TOKEN_SALT = 'this_is_the_refresh_token_salt';

    protected function setUp(): void
    {
        $this->tokenRepository = $this->createMock(TokenRepository::class);
        $this->tokenService = new TokenService($this->tokenRepository, self::REFRESH_TOKEN_SALT);
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
        $rawToken = 'raw-refresh';

        $token = new Token();
        $token->setLocalRefreshToken($rawToken);

        $this->tokenRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn($token);

        $this->tokenRepository
            ->expects(self::once())
            ->method('save')
            ->with($token);

        // act
        $result = $this->tokenService->revokeToken($rawToken);

        // assert
        self::assertTrue($result);
        self::assertTrue($token->isRevoked());
    }

    public function test_it_returns_false_when_revoking_a_nonexistent_token(): void
    {
        // arrange
        $rawToken = 'raw-refresh';

        $this->tokenRepository
            ->method('findOneBy')
            ->willReturn(null);

        // act + assert
        self::assertFalse(
            $this->tokenService->revokeToken($rawToken)
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
     * This locks down behaviour around double-revocation and prevents accidental re-writes later.
     */
    public function test_revoking_an_already_revoked_token_returns_true(): void
    {
        // arrange
        $rawToken = 'raw-refresh';

        $token = new Token();
        $token->setLocalRefreshToken($rawToken);
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
        $result = $this->tokenService->revokeToken($rawToken);

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
        $rawToken = 'raw-refresh';

        $token = new Token();
        $token->setLocalRefreshToken($rawToken);

        $this->tokenRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->tokenRepository
            ->expects(self::never())
            ->method('save')
            ->with($token);

        // act
        $result = $this->tokenService->revokeToken($rawToken);

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

    /**
     * When the IdP access token has no expiry,
     * TokenService must generate a default expiry instead of failing.
     * @return void
     * @throws \Exception
     */
    public function test_it_generates_idp_access_token_expiry_when_oauth_expiry_is_missing(): void
    {

        // $expiry = $accessToken->getExpires() ?? $this->generateExpiry()->getTimestamp();
        // arrange
        $accessToken = new AccessToken([
            'access_token' => 'idp-access',
            'refresh_token' => 'idp-refresh',
        ]);

        $token = $this->tokenService->createToken($accessToken);

        // act + assert
        self::assertNotNull($token->getIdpAccessTokenExpiresAt());
    }

    /**
     * @throws \Exception
     */
    public function test_issue_tokens_persists_hashed_refresh_token_only(): void
    {
        // arrange
        $token = new Token();
        $user = new User();

        $this->tokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($token);

        // act
        $issuedToken = $this->tokenService->issueTokens($token, $user);

        // assert
        $this->assertNotNull($issuedToken->getRawLocalRefreshToken());
        $this->assertNotNull($issuedToken->getLocalRefreshToken());

        $this->assertNotSame(
            $issuedToken->getRawLocalRefreshToken(),
            $issuedToken->getLocalRefreshToken(),
            'Persisted refresh token must be hashed, not raw'
        );
    }


    public function test_find_by_local_refresh_token_hashes_input_before_lookup(): void
    {
        // arrange
        $rawToken = 'raw-refresh';
        $hashedToken = hash('sha512', self::REFRESH_TOKEN_SALT.$rawToken);

        $this->tokenRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with([
                'localRefreshToken' => $hashedToken,
                'revoked' => false,
            ])
            ->willReturn(null);

        // act
        $this->tokenService->findByLocalRefreshToken($rawToken);
    }

    /**
     * Behaviour to test:
     * Issuing tokens always results in exactly one persistence operation.
     *
     * Why this matters:
     * Prevents accidental double-save
     * Prevents early returns skipping persistence
     * @throws \Exception
     */
    public function test_issue_tokens_persists_token_once(): void
    {
        // arrange, assert
        $this->tokenRepository
            ->expects(self::once())
            ->method('save');

        // act
        $this->tokenService->issueTokens(new Token(), new User());
    }

    /**
     * Why this matters
     * Prevents accidental downgrade (e.g. sha1)
     */
    public function test_refresh_token_hash_algorithm_is_sha512() :void
    {
        self::assertSame('sha512', $this->tokenService->getHashAlgo());
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
