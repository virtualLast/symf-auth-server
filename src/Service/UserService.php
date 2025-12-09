<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;

readonly class UserService
{

    public function __construct(private UserRepository $userRepository)
    {
    }

    public function findOrCreate( ResourceOwnerInterface $remoteUser ): User
    {
        if ($remoteUser instanceof KeycloakResourceOwner) {
            $user = $this->findByEmail($remoteUser->getEmail());
        }
        return $user ?? $this->createUser($remoteUser);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->userRepository->findOneBy(['email' => $email]);
    }

    public function createUser( ResourceOwnerInterface $remoteUser ): User
    {
        $user = new User();
        $user->setTokenSub($remoteUser->getId());
        if ($remoteUser instanceof KeycloakResourceOwner) {
            $user->setEmail($remoteUser->getEmail());
        }
        $this->userRepository->save($user);

        return $user;
    }
}
