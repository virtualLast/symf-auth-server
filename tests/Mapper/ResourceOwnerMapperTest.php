<?php

namespace App\Tests\Mapper;

use App\Mapper\ResourceOwnerMapper;
use App\Model\Dto\AccessRolesDto;
use App\Model\Enum\ProviderEnum;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ResourceOwnerMapperTest extends TestCase
{

    private ResourceOwnerMapper $resourceMapper;

    protected function setUp(): void
    {
        $this->resourceMapper = new ResourceOwnerMapper();
    }

    public function test_map_returns_dto_with_null_email()
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface(
            [
                'sub' => '123456789'
            ]
        );

        // act
        $owner = $this->resourceMapper->map($resourceOwner, $this->createProvider());

        // assert
        self::assertNull($owner->email);
        self::assertSame('123456789', $owner->tokenSub);
        self::assertEmpty($owner->accessLevels);
        self::assertIsArray($owner->accessLevels);
        self::assertEmpty($owner->userRoles);
        self::assertIsArray($owner->userRoles);
    }

    public function test_map_returns_dto_with_null_email_if_invalid_email()
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface(
            [
                'email' => 'invalid',
                'sub' => '123456789'
            ]
        );

        // act
        $owner = $this->resourceMapper->map($resourceOwner, $this->createProvider());

        // assert
        self::assertNull($owner->email);
        self::assertSame('123456789', $owner->tokenSub);
        self::assertEmpty($owner->accessLevels);
        self::assertIsArray($owner->accessLevels);
        self::assertEmpty($owner->userRoles);
        self::assertIsArray($owner->userRoles);
    }

    public function test_map_returns_dto_with_email()
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface([
            'email' => 'example@me.com',
            'sub' => '123456789'
        ]);

        // act
        $owner = $this->resourceMapper->map($resourceOwner, $this->createProvider());

        // assert
        self::assertNotNull($owner->email);
        self::assertSame('example@me.com', $owner->email);
        self::assertEmpty($owner->accessLevels);
        self::assertIsArray($owner->accessLevels);
        self::assertEmpty($owner->userRoles);
        self::assertIsArray($owner->userRoles);
    }

    public function test_map_returns_access_levels_user_roles(): void
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface([
            'email' => 'example@me.com',
            'sub' => '123456789'
        ]);

        $accessRoles = new AccessRolesDto(
            accessLevels: ['LEVEL_1', 'LEVEL_2'],
            hierCodes: ['HIER_CODE_A', 'HIER_CODE_B']
        );

        // act
        $owner = $this->resourceMapper->map($resourceOwner, $this->createProvider(), $accessRoles);

        // assert
        self::assertSame('example@me.com', $owner->email);
        self::assertSame('123456789', $owner->tokenSub);
        self::assertSame(['LEVEL_1', 'LEVEL_2'], $owner->accessLevels);
        self::assertSame(['HIER_CODE_A', 'HIER_CODE_B'], $owner->userRoles);
    }

    public function test_map_returns_exception_without_sub()
    {
        // arrange
        $resourceOwner = $this->createResourceOwnerInterface(
            []
        );

        // act + assert
        $this->expectException(RuntimeException::class);
        $this->resourceMapper->map($resourceOwner, $this->createProvider());
    }

    private function createResourceOwnerInterface(array $data = []): ResourceOwnerInterface
    {
        $mock = $this->createMock(ResourceOwnerInterface::class);
        $mock
            ->method('toArray')
            ->willReturn(
                $data
            );
        return $mock;
    }

    private function createProvider(): ProviderEnum
    {
        return ProviderEnum::KEYCLOAK_LOCAL;
    }
}
