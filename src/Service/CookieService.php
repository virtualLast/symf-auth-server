<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Cookie;

class CookieService
{
    public const ACCESS_COOKIE_NAME = 'access_token';
    public const REFRESH_COOKIE_NAME = 'refresh_token';

    public function createAccess(string $accessToken, \DateTimeImmutable $expiry): Cookie
    {
        return new Cookie(
            self::ACCESS_COOKIE_NAME,
            $accessToken,
            $expiry,
            '/',
            null,
            true,
            true,
            false,
            Cookie::SAMESITE_LAX
        );
    }

    public function createRefresh(string $refreshToken, \DateTimeImmutable $expiry): Cookie
    {
        return new Cookie(
            self::REFRESH_COOKIE_NAME,
            $refreshToken,
            $expiry,
            '/',
            null,
            true,
            true,
            false,
            Cookie::SAMESITE_LAX
        );
    }
}
