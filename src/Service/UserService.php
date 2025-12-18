<?php

namespace App\Service;

use App\Entity\User;
use App\Model\Dto\ResourceOwnerDto;
use App\Model\Enum\ProviderEnum;
use App\Repository\UserRepository;
use Foxworth42\OAuth2\Client\Provider\OktaUser;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Riskio\OAuth2\Client\Provider\Auth0ResourceOwner;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;

readonly class UserService
{

    public function __construct(private UserRepository $userRepository)
    {
    }

    public function findOrCreate( ResourceOwnerDto $remoteUser ): User
    {
        $user = $this->findByTokenSub($remoteUser->tokenSub, $remoteUser->provider);

        if ($user) {
            return $user;
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

    public function createUser( ResourceOwnerDto $remoteUser ): User
    {
        $user = new User();
        $user->setProvider($remoteUser->provider);
        $user->setTokenSub($remoteUser->tokenSub);
        $user->setEmail($remoteUser->email);

        $this->userRepository->save($user);

        return $user;
    }
}
