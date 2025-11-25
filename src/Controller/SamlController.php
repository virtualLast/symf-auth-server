<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/saml')]
class SamlController extends AbstractController
{

    #[Route('/login', name: 'saml_login')]
    public function login(): Response
    {
        return new Response('login.');
    }

    #[Route('/logout', name: 'saml_logout')]
    public function logout(): Response
    {
        return new Response('logout.');
    }

    #[Route('/callback', name: 'saml_callback')]
    public function callback(): Response
    {
        return new Response('callback.');
    }

    #[Route('/acs', name: 'saml_acs')]
    public function acs(): Response
    {
        return new Response('acs.');
    }

    #[Route('/meta', name: 'saml_meta')]
    public function meta(): Response
    {
        return new Response('meta.');
    }
}
