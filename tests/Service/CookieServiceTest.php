<?php

namespace App\Tests\Service;

use App\Service\CookieService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

final class CookieServiceTest extends TestCase
{
    public function test_it_creates_an_access_cookie_with_correct_name_and_value(): void
    {
        $service = new CookieService();
        $expiry = new \DateTimeImmutable('+1 hour');

        $cookie = $service->createAccess('ACCESS123', $expiry);

        self::assertSame(CookieService::ACCESS_COOKIE_NAME, $cookie->getName());
        self::assertSame('ACCESS123', $cookie->getValue());
    }
    public function test_it_creates_a_refresh_cookie_with_correct_name_and_value(): void
    {
        $service = new CookieService();
        $expiry = new \DateTimeImmutable('+2 hours');

        $cookie = $service->createRefresh('REFRESH456', $expiry);

        self::assertSame(CookieService::REFRESH_COOKIE_NAME, $cookie->getName());
        self::assertSame('REFRESH456', $cookie->getValue());
    }

    public function test_access_cookie_uses_provided_expiry(): void
    {
        $service = new CookieService();
        $expiry = new \DateTimeImmutable('+30 minutes');

        $cookie = $service->createAccess('A', $expiry);

        self::assertSame($expiry->getTimestamp(), $cookie->getExpiresTime());
    }
    public function test_refresh_cookie_uses_provided_expiry(): void
    {
        $service = new CookieService();
        $expiry = new \DateTimeImmutable('+3 hours');

        $cookie = $service->createRefresh('R', $expiry);

        self::assertSame($expiry->getTimestamp(), $cookie->getExpiresTime());
    }

    public function test_access_cookie_is_secure_and_http_only(): void
    {
        $service = new CookieService();
        $expiry = new \DateTimeImmutable('+1 hour');

        $cookie = $service->createAccess('X', $expiry);

        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
    }
    public function test_refresh_cookie_is_secure_and_http_only(): void
    {
        $service = new CookieService();
        $expiry = new \DateTimeImmutable('+1 hour');

        $cookie = $service->createRefresh('Y', $expiry);

        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
    }

    public function test_cookies_use_lax_same_site_policy(): void
    {
        $service = new CookieService();
        $expiry = new \DateTimeImmutable('+1 hour');

        $access = $service->createAccess('A', $expiry);
        $refresh = $service->createRefresh('R', $expiry);

        self::assertSame(Cookie::SAMESITE_LAX, $access->getSameSite());
        self::assertSame(Cookie::SAMESITE_LAX, $refresh->getSameSite());
    }

    public function test_cookies_are_set_for_root_path(): void
    {
        $service = new CookieService();
        $expiry = new \DateTimeImmutable('+1 hour');

        $access = $service->createAccess('A', $expiry);
        $refresh = $service->createRefresh('R', $expiry);

        self::assertSame('/', $access->getPath());
        self::assertSame('/', $refresh->getPath());
        self::assertNull($access->getDomain());
        self::assertNull($refresh->getDomain());
    }
}

