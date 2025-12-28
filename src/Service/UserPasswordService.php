<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class UserPasswordService
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function hashPassword(User $user): void
    {
        if ($user->getPlainTextPassword() === null) {
            return;
        }

        $user->setPassword(
            $this->passwordHasher->hashPassword(
                $user,
                $user->getPlainTextPassword()
            )
        );

        $user->eraseCredentials();
    }
}
