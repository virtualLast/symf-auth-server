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

    public function __construct(private TokenRepository $tokenRepository)
    {
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

        $token->setIdpAccessTokenExpiresAt((new \DateTimeImmutable())->setTimestamp($accessToken->getExpires()));

        $token->setIdpRefreshTokenExpiresAt($this->generateExpiry(self::TOKEN_EXPIRY_1_MONTH));

        return $token;
    }

    public function createSamlToken(): Token
    {
        return new Token();
    }

    public function revokeToken(AccessToken $accessToken): bool
    {
        $token = $this->findByLocalRefreshToken($accessToken->getRefreshToken());

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

        $token->setLocalRefreshToken($this->generateToken());
        $token->setLocalRefreshTokenExpiresAt($this->generateExpiry(self::TOKEN_EXPIRY_1_MONTH));

        $token->setUser($user);
        $this->tokenRepository->save($token);

        return $this->findByLocalRefreshToken($token->getLocalRefreshToken());
    }

    public function findByLocalRefreshToken(string $refreshToken): ?Token
    {
        return $this->tokenRepository->findOneBy([
            'localRefreshToken' => $refreshToken,
            'revoked' => false
        ]);
    }

    private function generateToken(): Ulid
    {
        return new Ulid();
    }

    /**
     * @throws \Exception
     */
    private function generateExpiry(int $length = self::TOKEN_EXPIRY_1_DAY): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->setTimestamp(time() + $length);
    }
}
