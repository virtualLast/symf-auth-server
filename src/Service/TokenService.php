<?php

namespace App\Service;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\Uid\Ulid;

readonly class TokenService
{

    private const TOKEN_EXPIRY_1_DAY = 86400;
    private const TOKEN_EXPIRY_1_MONTH = 2592000;
    private const REFRESH_TOKEN_HASH_ALGO = 'sha512';

    public function __construct(
        private TokenRepository $tokenRepository,
        private string $refreshTokenSalt
    ) {
    }

    /**
     * create the tokens in the db
     * @throws \Exception
     */
    public function createToken(AccessToken $accessToken ): Token
    {
        $token = new Token();
        $token->setIdpAccessToken($accessToken->getToken());
        $token->setIdpRefreshToken($accessToken->getRefreshToken());

        $expiry = $accessToken->getExpires() ?? $this->generateExpiry()->getTimestamp();
        $token->setIdpAccessTokenExpiresAt((new \DateTimeImmutable())->setTimestamp($expiry));

        $token->setIdpRefreshTokenExpiresAt($this->generateExpiry(self::TOKEN_EXPIRY_1_MONTH));

        return $token;
    }

    public function createSimpleToken(): Token
    {
        return new Token();
    }

    public function revokeToken(string $refreshToken): bool
    {
        $token = $this->findByLocalRefreshToken($refreshToken);

        if ($token === null) {
            return false;
        }
        if ($token->isRevoked() === true) {
            return true;
        }

        $token->setRevoked(true);
        $this->tokenRepository->save($token);

        return true;
    }


    /**
     * @throws \Exception
     */
    public function issueTokens(Token $token, User $user): Token
    {
        $token->setLocalAccessToken($this->generateToken());
        $token->setLocalAccessTokenExpiresAt($this->generateExpiry());

        $refreshToken = $this->generateToken();
        $token->setRawLocalRefreshToken($refreshToken);
        $token->setLocalRefreshToken($this->hashRefreshToken($refreshToken));
        $token->setLocalRefreshTokenExpiresAt(
            $this->generateExpiry(self::TOKEN_EXPIRY_1_MONTH)
        );

        $token->setUser($user);
        $this->tokenRepository->save($token);

        return $token;
    }

    public function findByLocalRefreshToken(string $refreshToken): ?Token
    {
        $refreshToken = $this->hashRefreshToken($refreshToken);
        return $this->tokenRepository->findOneBy([
            'localRefreshToken' => $refreshToken,
            'revoked' => false
        ]);
    }

    public function getHashAlgo(): string
    {
        return self::REFRESH_TOKEN_HASH_ALGO;
    }

    private function generateToken(): Ulid
    {
        return new Ulid();
    }

    private function hashRefreshToken(string $refreshToken): string
    {
        return hash(self::REFRESH_TOKEN_HASH_ALGO, $this->refreshTokenSalt.$refreshToken);
    }

    /**
     * @throws \Exception
     */
    private function generateExpiry(int $length = self::TOKEN_EXPIRY_1_DAY): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->setTimestamp(time() + $length);
    }
}
