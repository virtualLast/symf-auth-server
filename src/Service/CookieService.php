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
            $this->getPath(),
            $this->getDomain(),
            $this->getSecure(),
            $this->getHttpOnly(),
            $this->getRaw(),
            $this->getSameSite()
        );
    }

    public function createRefresh(string $refreshToken, \DateTimeImmutable $expiry): Cookie
    {
        return new Cookie(
            self::REFRESH_COOKIE_NAME,
            $refreshToken,
            $expiry,
            $this->getPath(),
            $this->getDomain(),
            $this->getSecure(),
            $this->getHttpOnly(),
            $this->getRaw(),
            $this->getSameSite()
        );
    }

    private function getPath(): string
    {
        return '/';
    }

    private function getDomain(): ?string
    {
        return null;
    }

    private function getSecure(): bool
    {
        return true;
    }

    private function getHttpOnly(): bool
    {
        return true;
    }

    private function getRaw(): bool
    {
        return false;
    }

    private function getSameSite(): string
    {
        return Cookie::SAMESITE_LAX;
    }
}
