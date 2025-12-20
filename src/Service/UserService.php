<?php

namespace App\Service;

use App\Entity\User;
use App\Model\Dto\ResourceOwnerDto;
use App\Model\Enum\ProviderEnum;
use App\Repository\UserRepository;

readonly class UserService
{

    public function __construct(
        private UserRepository $userRepository,
        private AccessLevelRoleMapper $roleMapper
    ) {
    }

    public function findOrCreate( ResourceOwnerDto $remoteUser ): User
    {
        $user = $this->findByTokenSub($remoteUser->tokenSub, $remoteUser->provider);

        if ($user) {
            return $this->synchronizeUserAccessRoles($user, $remoteUser);
        }

        return $this->createUser($remoteUser);
    }

    public function findByTokenSub(string $tokenSub, ProviderEnum $provider): ?User
    {
        return $this->userRepository->findOneBy(
            [
                'tokenSub' => $tokenSub,
                'provider' => $provider
            ]
        );
    }

    public function createUser(ResourceOwnerDto $remoteUser): User
    {
        $user = new User();
        $user->setProvider($remoteUser->provider);
        $user->setTokenSub($remoteUser->tokenSub);
        $user->setEmail($remoteUser->email);

        $this->applyAccessRoles($user, $remoteUser);

        $this->userRepository->save($user);

        return $user;
    }

    private function synchronizeUserAccessRoles(User $user, ResourceOwnerDto $resourceOwnerDto): User
    {
        // Update email if changed
        if ($resourceOwnerDto->email !== null) {
            $user->setEmail($resourceOwnerDto->email);
        }

        // Update access roles
        $this->applyAccessRoles($user, $resourceOwnerDto);

        $this->userRepository->save($user);

        return $user;
    }

    private function applyAccessRoles(User $user, ResourceOwnerDto $resourceOwnerDto): void
    {
        // Store raw access levels
        $user->setAccessLevels($resourceOwnerDto->accessLevels);

        if (
            count($resourceOwnerDto->userRoles) > 0
        ) {
            $roles = $this->roleMapper->mapToRoles($resourceOwnerDto->userRoles);
            $user->setRoles(array_unique($roles));
        } else {
            // Default role for users without hier codes
            $user->setRoles(['ROLE_USER']);
        }
    }
}
