<?php

namespace App\Service;

use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\HttpFoundation\Cookie;

class CookieService
{
    public const COOKIE_NAME = 'access_token';

    public function create(AccessTokenInterface $accessToken): Cookie
    {
        return new Cookie(
            self::COOKIE_NAME,
            $accessToken->getToken(),
            new \DateTimeImmutable('+1 day'),
            '/',
            null,
            true,
            true,
            false,
            Cookie::SAMESITE_LAX
        );
    }
}
