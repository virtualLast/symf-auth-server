<?php

namespace App\Tests\Service;

use App\Model\Dto\AccessRolesDto;
use App\Model\Enum\ProviderEnum;
use App\OAuth\Exception\OauthParseException;
use App\Service\TokenParamsService;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use PHPUnit\Framework\TestCase;

class TokenParamsServiceTest extends TestCase
{
    private TokenParamsService $tokenParamsService;

    protected function setUp(): void
    {
        $this->tokenParamsService = new TokenParamsService();
    }

    /**
     * Ensures that non-Tesco providers return null without processing.
     */
    public function test_it_returns_null_for_non_tesco_providers(): void
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface([
            'params' => [
                'AccessLevel' => ['LEVEL_1'],
                'HierCode' => ['CODE_1']
            ]
        ]);

        // act
        $result = $this->tokenParamsService->parse($resourceOwner, ProviderEnum::KEYCLOAK_LOCAL);

        // assert
        $this->assertNull($result);
    }

    /**
     * Ensures that parsing throws exception when params key is missing.
     */
    public function test_it_throws_exception_when_params_is_missing(): void
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface([]);

        // act + assert
        $this->expectException(OauthParseException::class);
        $this->expectExceptionMessage('Invalid or missing params in resource owner');
        $this->tokenParamsService->parse($resourceOwner, ProviderEnum::KEYCLOAK_TESCO);
    }

    /**
     * Ensures that parsing throws exception when params is not an array.
     */
    public function test_it_throws_exception_when_params_is_not_array(): void
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface([
            'params' => 'not-an-array'
        ]);

        // act + assert
        $this->expectException(OauthParseException::class);
        $this->expectExceptionMessage('Invalid or missing params in resource owner');
        $this->tokenParamsService->parse($resourceOwner, ProviderEnum::KEYCLOAK_TESCO);
    }

    /**
     * Ensures that parsing throws exception when AccessLevel is missing.
     */
    public function test_it_throws_exception_when_access_level_is_missing(): void
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface([
            'params' => [
                'HierCode' => ['CODE_1']
            ]
        ]);

        // act + assert
        $this->expectException(OauthParseException::class);
        $this->expectExceptionMessage('Malformed params: AccessLevel is required');
        $this->tokenParamsService->parse($resourceOwner, ProviderEnum::KEYCLOAK_TESCO);
    }

    /**
     * Ensures that parsing throws exception when AccessLevel is not an array.
     */
    public function test_it_throws_exception_when_access_level_is_not_array(): void
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface([
            'params' => [
                'AccessLevel' => 'not-an-array',
                'HierCode' => ['CODE_1']
            ]
        ]);

        // act + assert
        $this->expectException(OauthParseException::class);
        $this->expectExceptionMessage('Malformed params: AccessLevel is required');
        $this->tokenParamsService->parse($resourceOwner, ProviderEnum::KEYCLOAK_TESCO);
    }

    /**
     * Ensures that valid Tesco params with AccessLevel and HierCode are parsed correctly.
     */
    public function test_it_parses_valid_tesco_params_with_access_level_and_hier_code(): void
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface([
            'params' => [
                'AccessLevel' => ['GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin'],
                'HierCode' => [
                    'GG-XX-TescoGlobal-HierCode UK01001',
                    'GG-XX-TescoGlobal-HierCode UKGP001'
                ]
            ]
        ]);

        // act
        $result = $this->tokenParamsService->parse($resourceOwner, ProviderEnum::KEYCLOAK_TESCO);

        // assert
        $this->assertInstanceOf(AccessRolesDto::class, $result);
        $this->assertSame(['GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin'], $result->accessLevels);
        $this->assertSame([
            'GG-XX-TescoGlobal-HierCode UK01001',
            'GG-XX-TescoGlobal-HierCode UKGP001'
        ], $result->hierCodes);
    }

    /**
     * Ensures that valid Tesco params with only AccessLevel defaults HierCode to empty array.
     */
    public function test_it_parses_valid_tesco_params_with_only_access_level(): void
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface([
            'params' => [
                'AccessLevel' => ['GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin']
            ]
        ]);

        // act
        $result = $this->tokenParamsService->parse($resourceOwner, ProviderEnum::KEYCLOAK_TESCO);

        // assert
        $this->assertInstanceOf(AccessRolesDto::class, $result);
        $this->assertSame(['GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin'], $result->accessLevels);
        $this->assertSame([], $result->hierCodes);
    }

    /**
     * Ensures that empty AccessLevel array is handled correctly.
     */
    public function test_it_handles_empty_access_level_array(): void
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface([
            'params' => [
                'AccessLevel' => [],
                'HierCode' => ['GG-XX-TescoGlobal-HierCode UK01001']
            ]
        ]);

        // act
        $result = $this->tokenParamsService->parse($resourceOwner, ProviderEnum::KEYCLOAK_TESCO);

        // assert
        $this->assertInstanceOf(AccessRolesDto::class, $result);
        $this->assertSame([], $result->accessLevels);
        $this->assertSame(['GG-XX-TescoGlobal-HierCode UK01001'], $result->hierCodes);
    }

    /**
     * Ensures that empty HierCode array is handled correctly.
     */
    public function test_it_handles_empty_hier_code_array(): void
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface([
            'params' => [
                'AccessLevel' => ['GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin'],
                'HierCode' => []
            ]
        ]);

        // act
        $result = $this->tokenParamsService->parse($resourceOwner, ProviderEnum::KEYCLOAK_TESCO);

        // assert
        $this->assertInstanceOf(AccessRolesDto::class, $result);
        $this->assertSame(['GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin'], $result->accessLevels);
        $this->assertSame([], $result->hierCodes);
    }

    /**
     * Ensures that multiple AccessLevels are parsed correctly.
     */
    public function test_it_parses_multiple_access_levels(): void
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface([
            'params' => [
                'AccessLevel' => [
                    'GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin',
                    'GG-XX-TescoGlobal-LightFoot_PRD-Filter_ReadOnly'
                ],
                'HierCode' => []
            ]
        ]);

        // act
        $result = $this->tokenParamsService->parse($resourceOwner, ProviderEnum::KEYCLOAK_TESCO);

        // assert
        $this->assertInstanceOf(AccessRolesDto::class, $result);
        $this->assertCount(2, $result->accessLevels);
        $this->assertContains('GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin', $result->accessLevels);
        $this->assertContains('GG-XX-TescoGlobal-LightFoot_PRD-Filter_ReadOnly', $result->accessLevels);
    }

    private function createResourceOwnerInterface(array $data = []): ResourceOwnerInterface
    {
        $mock = $this->createMock(ResourceOwnerInterface::class);
        $mock
            ->method('toArray')
            ->willReturn($data);
        return $mock;
    }
}
