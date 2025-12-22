<?php

namespace App\Tests\Service;

use App\Model\Enum\ProviderEnum;
use App\Service\ScopeService;
use PHPUnit\Framework\TestCase;

class ScopeServiceTest extends TestCase
{
    private ScopeService $scopeService;

    protected function setUp(): void
    {
        $this->scopeService = new ScopeService();
    }

    public function test_getScopesForProvider_returns_base_scopes_for_default_providers(): void
    {
        // act
        $scopes = $this->scopeService->getScopesForProvider(ProviderEnum::KEYCLOAK_LOCAL);

        // assert
        self::assertEquals(['openid', 'profile', 'email'], $scopes);
    }

    public function test_getScopesForProvider_returns_base_scopes_for_auth0(): void
    {
        // act
        $scopes = $this->scopeService->getScopesForProvider(ProviderEnum::AUTH0);

        // assert
        self::assertEquals(['openid', 'profile', 'email'], $scopes);
    }

    public function test_getScopesForProvider_returns_base_scopes_for_okta(): void
    {
        // act
        $scopes = $this->scopeService->getScopesForProvider(ProviderEnum::OKTA);

        // assert
        self::assertEquals(['openid', 'profile', 'email'], $scopes);
    }

    public function test_getScopesForProvider_returns_scopes_with_params_for_keycloak_tesco(): void
    {
        // act
        $scopes = $this->scopeService->getScopesForProvider(ProviderEnum::KEYCLOAK_TESCO);

        // assert
        self::assertEquals(['openid', 'profile', 'email', 'params'], $scopes);
    }

    public function test_getScopesForProvider_returns_array_of_strings(): void
    {
        // act
        $scopes = $this->scopeService->getScopesForProvider(ProviderEnum::KEYCLOAK_LOCAL);

        // assert
        self::assertIsArray($scopes);
        foreach ($scopes as $scope) {
            self::assertIsString($scope);
        }
    }
}



