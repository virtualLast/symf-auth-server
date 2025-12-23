<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth/login')]
class AuthLoginUserController extends AbstractController
{
    /**
     * define user login form
     */
    #[Route('/', name: 'app_auth_login', methods: ['GET'])]
    public function login(): Response
    {
        return new Response('Login User');
    }

    /**
     * process form submission,
     * search for matching user
     */
    #[Route('/', name: 'app_auth_login_process', methods: ['POST'])]
    public function processLogin(): Response
    {
        return new Response('Login User');
    }
}
