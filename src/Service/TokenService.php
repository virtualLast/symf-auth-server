<?php

namespace App\Service;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
use League\OAuth2\Client\Token\AccessToken;

readonly class TokenService
{

    public function __construct(private TokenRepository $tokenRepository)
    {
    }

    // create the tokens in the db
    public function createToken( AccessToken $accessToken ): void
    {
        $token = new Token();
        $token->setRefreshToken($accessToken->getRefreshToken());
        $token->setExpiry($accessToken->getExpires());

        $this->tokenRepository->save($token);
    }

    // update token expiry
    public function refreshToken(AccessToken $accessToken): void
    {}

    // set revoked property on the token
    public function revokeToken(AccessToken $accessToken): void
    {}

    // attach tokens to user
    public function issueTokens(AccessToken $accessToken, User $user): void
    {}
}
