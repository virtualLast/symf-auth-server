<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Model\Dto\ResourceOwnerDto;
use App\Model\Enum\ProviderEnum;
use App\Repository\UserRepository;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userService = new UserService($this->userRepository);
    }

    public function test_user_is_returned_if_found(): void
    {
        // arrange
        $user = new User();

        $this->userRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn($user);

        $this->userRepository
            ->expects(self::never())
            ->method('save');

        // act
        $this->userService->findOrCreate($this->createResourceOwnerDto());
    }

    public function test_user_is_created_if_not_found(): void
    {
        // arrange
        $this->userRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->userRepository
            ->expects(self::once())
            ->method('save');

        // act
        $user = $this->userService->findOrCreate($this->createResourceOwnerDto());

        // assert
        self::assertNotNull($user);
        self::assertEquals(ProviderEnum::KEYCLOAK_LOCAL, $user->getProvider());
        self::assertEquals('super-secret-token-sub-value', $user->getTokenSub());
    }

    private function createResourceOwnerDto(): ResourceOwnerDto
    {
        return new ResourceOwnerDto(
            $this->createProvider(),
            'super-secret-token-sub-value',
            null
        );
    }

    private function createProvider(): ProviderEnum
    {
        return ProviderEnum::KEYCLOAK_LOCAL;
    }
}
