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

    // create the tokens in the db
    public function createToken( AccessToken $accessToken ): Token
    {
        $token = new Token();
        $token->setIdpAccessToken($accessToken->getToken());
        $token->setIdpRefreshToken($accessToken->getRefreshToken());

        $expiry = new \DateTimeImmutable();
        $expiry->setTimestamp($accessToken->getExpires());
        $token->setIdpAccessTokenExpiresAt($expiry);

        return $token;
    }

    public function refreshToken(Token $token): bool
    {
        /**
         * Check the local refresh token expiry.
         *
         * If the current time is before that expiry:
         *
         * Use the refresh token to request a new access token from the IdP.
         *
         * Update your local access token value.
         *
         * Update the local access token expiry based on the new token’s lifetime.
         *
         * If the refresh token has expired, the user must re-authenticate via the IdP.
         *
         * This way your local tokens stay in sync with the IdP, but you never expose the IdP refresh token to the client.
         */

        return true;
    }

    // set revoked property on the token
    public function revokeToken(AccessToken $accessToken): bool
    {
        $token = $this->findByIdpRefresh($accessToken->getRefreshToken());
        if ($token !== null) {
            $token->setRevoked(true);
            $this->tokenRepository->save($token);
            return $this->findByIdpRefresh($accessToken->getRefreshToken())->isRevoked();
        }
        return false;
    }

    // attach tokens to user
    public function issueTokens(Token $token, User $user): Token
    {
        $token->setLocalAccessToken($this->generateToken());
        $token->setLocalAccessTokenExpiresAt($this->generateExpiry());

        $token->setLocalRefreshToken($this->generateToken());
        $token->setLocalRefreshTokenExpiresAt($this->generateExpiry(self::TOKEN_EXPIRY_1_MONTH));

        $token->setUser($user);
        $this->tokenRepository->save($token);

        return $this->findByIdpRefresh($token->getIdpRefreshToken());
    }

    public function findByIdpRefresh(string $refreshToken): ?Token
    {
        return $this->tokenRepository->findOneBy([
            'idpRefreshToken' => $refreshToken,
            'revoked' => false
        ]);
    }

    private function generateToken(): Ulid
    {
        return new Ulid();
    }

    private function generateExpiry(int $length = self::TOKEN_EXPIRY_1_DAY): \DateTimeImmutable
    {
        return new \DateTimeImmutable('@' . (time() + $length));
    }
}
