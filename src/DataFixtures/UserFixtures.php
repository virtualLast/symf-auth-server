<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Model\Enum\ProviderEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail(sprintf('user_%s@example.com', $i));
            $user->setTokenSub(sprintf('token_sub_%s', $i));
            $user->setProvider(ProviderEnum::KEYCLOAK_LOCAL);
            $user->setRoles(['ROLE_USER']);
            $manager->persist($user);
        }

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail(sprintf('login_user_%s@example.com', $i));
            $user->setTokenSub(sprintf('login_user_token_sub_%s', $i));
            $user->setPlainTextPassword('password');
            $user->setProvider(ProviderEnum::LIGHTFOOT);
            $user->setRoles(['ROLE_USER']);
            $manager->persist($user);
        }

        $oidcUser = new User();
        $oidcUser->setEmail('oidc_normal_user@example.local');
        $oidcUser->setTokenSub('oidc_normal_user_1');
        $oidcUser->setProvider(ProviderEnum::KEYCLOAK_LOCAL);
        $oidcUser->setRoles(['ROLE_USER']);
        $manager->persist($oidcUser);

        $oidcAdminUser = new User();
        $oidcAdminUser->setEmail('oidc_admin_user@example.local');
        $oidcAdminUser->setTokenSub('oidc_admin_user_1');
        $oidcAdminUser->setProvider(ProviderEnum::KEYCLOAK_LOCAL);
        $oidcAdminUser->setRoles(['ROLE_ADMIN']);
        $manager->persist($oidcAdminUser);

        $samlUser = new User();
        $samlUser->setEmail('saml_normal_user@example.local');
        $samlUser->setTokenSub('saml_normal_user_1');
        $samlUser->setProvider(ProviderEnum::KEYCLOAK_LOCAL);
        $samlUser->setRoles(['ROLE_USER']);
        $manager->persist($samlUser);

        $samlAdminUser = new User();
        $samlAdminUser->setEmail('saml_admin_user@example.local');
        $samlAdminUser->setTokenSub('saml_admin_user_1');
        $samlAdminUser->setProvider(ProviderEnum::KEYCLOAK_LOCAL);
        $samlAdminUser->setRoles(['ROLE_ADMIN']);
        $manager->persist($samlAdminUser);

        $manager->flush();
    }
}
