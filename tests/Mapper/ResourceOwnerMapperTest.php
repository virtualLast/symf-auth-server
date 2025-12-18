<?php

namespace App\Tests\Mapper;

use App\Mapper\ResourceOwnerMapper;
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
        return ProviderEnum::KEYCLOAK;
    }
}
