<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail(sprintf('user_%s@example.com', $i));
            $manager->persist($user);
        }

        $oidcUser = new User();
        $oidcUser->setEmail('oidc_normal_user@example.local');
        $manager->persist($oidcUser);

        $oidcAdminUser = new User();
        $oidcAdminUser->setEmail('oidc_admin_user@example.local');
        $manager->persist($oidcAdminUser);

        $samlUser = new User();
        $samlUser->setEmail('saml_normal_user@example.local');
        $manager->persist($samlUser);

        $samlAdminUser = new User();
        $samlAdminUser->setEmail('saml_admin_user@example.local');
        $manager->persist($samlAdminUser);

        $manager->flush();
    }
}
