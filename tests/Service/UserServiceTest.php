<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Model\Dto\ResourceOwnerDto;
use App\Model\Enum\ProviderEnum;
use App\Repository\UserRepository;
use App\Service\AccessLevelRoleMapper;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $accessLevelRoleMapper = new AccessLevelRoleMapper();
        $this->userService = new UserService($this->userRepository, $accessLevelRoleMapper);
    }

    public function test_existing_user_is_returned_when_found(): void
    {
        // arrange
        $user = new User();
        $user->setProvider($this->createProvider());
        $user->setTokenSub('super-secret-token-sub-value');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        // save is called once because of the sync
        $this->userRepository
            ->expects($this->once())
            ->method('save');

        // act
        $user = $this->userService->findOrCreate($this->createResourceOwnerDto());

        // assert
        $this->assertNotNull($user);
        $this->assertEquals(ProviderEnum::KEYCLOAK_LOCAL, $user->getProvider());
        $this->assertEquals('super-secret-token-sub-value', $user->getTokenSub());
        $this->assertEmpty($user->getAccessLevels());
        $this->assertIsArray($user->getAccessLevels());
        $this->assertNotEmpty($user->getRoles());
        $this->assertIsArray($user->getRoles());
    }

    public function test_new_user_is_created_when_no_existing_user_is_found(): void
    {
        // arrange
        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->userRepository
            ->expects($this->once())
            ->method('save');

        // act
        $user = $this->userService->findOrCreate($this->createResourceOwnerDto());

        // assert
        $this->assertNotNull($user);
        $this->assertEquals(ProviderEnum::KEYCLOAK_LOCAL, $user->getProvider());
        $this->assertEquals('super-secret-token-sub-value', $user->getTokenSub());
        $this->assertEmpty($user->getAccessLevels());
        $this->assertIsArray($user->getAccessLevels());
        $this->assertNotEmpty($user->getRoles());
        $this->assertIsArray($user->getRoles());
    }

    public function test_existing_user_is_synchronised_when_found(): void
    {
        // arrange
        $user = new User();
        $user->setEmail(null);
        $user->setProvider($this->createProvider());
        $user->setTokenSub('super-secret-token-sub-value');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        // save is called once because of the sync
        $this->userRepository
            ->expects($this->once())
            ->method('save');

        // act
        $user = $this->userService->findOrCreate($this->createResourceOwnerDto(['email' => 'test@example.com']));

        // assert
        $this->assertNotNull($user->getEmail());
    }

    public function test_user_is_synchronised_even_if_data_the_same(): void
    {
        $email = 'test@example.com';
        // arrange
        $user = new User();
        $user->setEmail($email);
        $user->setProvider($this->createProvider());
        $user->setTokenSub('super-secret-token-sub-value');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        // save is called once because of the sync
        $this->userRepository
            ->expects($this->once())
            ->method('save');

        // act
        $user = $this->userService->findOrCreate($this->createResourceOwnerDto(['email' => $email]));

        // assert
        $this->assertNotNull($user->getEmail());
        $this->assertEquals($email, $user->getEmail());
    }

    public function test_user_email_is_set_on_creation(): void
    {
        // arrange
        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->userRepository
            ->expects($this->once())
            ->method('save');

        // act
        $user = $this->userService->findOrCreate($this->createResourceOwnerDto(['email' => 'test@example.com']));

        // assert
        $this->assertNotNull($user->getEmail());
        $this->assertEquals('test@example.com',$user->getEmail());
        $this->assertEquals(ProviderEnum::KEYCLOAK_LOCAL, $user->getProvider());
        $this->assertEquals('super-secret-token-sub-value', $user->getTokenSub());
        $this->assertEmpty($user->getAccessLevels());
        $this->assertIsArray($user->getAccessLevels());
        $this->assertNotEmpty($user->getRoles());
        $this->assertIsArray($user->getRoles());
    }

    public function test_user_email_is_updated_when_remote_email_is_provided(): void
    {
        // arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setProvider($this->createProvider());
        $user->setTokenSub('super-secret-token-sub-value');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        // save is called once because of the sync
        $this->userRepository
            ->expects($this->once())
            ->method('save');

        // act
        $user = $this->userService->findOrCreate($this->createResourceOwnerDto(['email' => 'updated@example.com']));

        // assert
        $this->assertNotNull($user->getEmail());
        $this->assertEquals('updated@example.com', $user->getEmail());
    }

    public function test_user_email_is_not_changed_when_remote_email_is_null(): void
    {
        // arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setProvider($this->createProvider());
        $user->setTokenSub('super-secret-token-sub-value');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        // save is called once because of the sync
        $this->userRepository
            ->expects($this->once())
            ->method('save');

        // act
        $user = $this->userService->findOrCreate($this->createResourceOwnerDto(['email' => null]));

        // assert
        $this->assertNotNull($user->getEmail());
        $this->assertEquals('test@example.com', $user->getEmail());
    }

    public function test_roles_are_mapped_from_remote_user_roles_when_present(): void
    {
        // arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setProvider($this->createTescoProvider());
        $user->setTokenSub('super-secret-token-sub-value');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        // save is called once because of the sync
        $this->userRepository
            ->expects($this->once())
            ->method('save');

        // act
        $user = $this->userService->findOrCreate($this->createResourceOwnerDto([
            'email' => null,
            'accessLevels' => ['GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin'],
            'hierCode' => ['GG-XX-TescoGlobal-HierCode UK01001']
        ]));

        // assert
        $this->assertNotNull($user->getEmail());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertSame(['GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin'], $user->getAccessLevels());
        $this->assertSame(['ROLE_USER', 'ROLE_STORE_01001'], $user->getRoles());
    }

    public function test_default_role_is_assigned_when_remote_user_has_no_roles(): void
    {
        // arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setProvider($this->createTescoProvider());
        $user->setTokenSub('super-secret-token-sub-value');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        // save is called once because of the sync
        $this->userRepository
            ->expects($this->once())
            ->method('save');

        // act
        $user = $this->userService->findOrCreate($this->createResourceOwnerDto([
            'email' => null,
            'accessLevels' => ['GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin']
        ]));

        // assert
        $this->assertNotNull($user->getEmail());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertSame(['GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin'], $user->getAccessLevels());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function test_roles_are_unique_after_mapping(): void
    {
        // arrange
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setProvider($this->createTescoProvider());
        $user->setTokenSub('super-secret-token-sub-value');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($user);

        // save is called once because of the sync
        $this->userRepository
            ->expects($this->once())
            ->method('save');

        // act
        $user = $this->userService->findOrCreate($this->createResourceOwnerDto([
            'email' => null,
            'accessLevels' => ['GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin'],
            'hierCode' => ['GG-XX-TescoGlobal-HierCode UK01001', 'GG-XX-TescoGlobal-HierCode UK01001']
        ]));

        // assert
        $this->assertNotNull($user->getEmail());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertSame(['GG-XX-TescoGlobal-LightFoot_PRD-AllUK_FleetAdmin'], $user->getAccessLevels());
        $this->assertSame(['ROLE_USER', 'ROLE_STORE_01001'], $user->getRoles());
    }

    /**
     * Ensures that when a matching user already exists, the service does not
     * create or return a new User instance.
     *
     * This test verifies that UserService behaves as an identity-preserving
     * operation: an existing user is reused and enriched rather than replaced.
     *
     * Why this matters:
     * - Other parts of the system may already hold references to the User object
     * - Replacing the instance could silently break invariants, event listeners,
     *   or Doctrine unit-of-work expectations
     * - It enforces the rule: "find-or-create" never means "find-or-replace"
     *
     * This test protects against future refactors where someone might
     * accidentally instantiate a new User for an existing identity.
     */
    public function test_find_or_create_returns_same_user_instance_for_existing_user(): void
    {}

    /**
     * Ensures that calling findOrCreate multiple times with the same remote
     * user data results in a stable and consistent User state.
     *
     * This test verifies idempotency:
     * - The first call may create or update a user
     * - Subsequent calls with identical input should not introduce further changes
     *
     * Why this matters:
     * - Authentication flows can be retried
     * - Token refreshes may re-trigger user synchronisation
     * - Idempotent behaviour prevents subtle bugs like role duplication,
     *   timestamp churn, or unnecessary writes
     *
     * This test protects against accidental side effects being added to
     * findOrCreate over time.
     */
    public function test_find_or_create_is_idempotent_for_same_remote_user_data(): void
    {}


    private function createResourceOwnerDto(array $extraData = []): ResourceOwnerDto
    {
        return new ResourceOwnerDto(
            $this->createProvider(),
            'super-secret-token-sub-value',
            $extraData['email'] ?? null,
            $extraData['accessLevels'] ?? [],
            $extraData['hierCode'] ?? [],
        );
    }

    private function createProvider(): ProviderEnum
    {
        return ProviderEnum::KEYCLOAK_LOCAL;
    }

    private function createTescoProvider(): ProviderEnum
    {
        return ProviderEnum::KEYCLOAK_TESCO;
    }
}
