<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/oidc')]
class OidcController extends AbstractController
{
    #[Route('/login', name: 'oidc_login')]
    public function login(): Response
    {
        return new Response('login.');
    }

    #[Route('/logout', name: 'oidc_logout')]
    public function logout(): Response
    {
        return new Response('logout.');
    }

    #[Route('/callback', name: 'oidc_callback')]
    public function callback(): Response
    {
        return new Response('callback.');
    }
}
