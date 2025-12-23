<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


/**
 * Note about this class we will have to check before we create any new users.
 * Email + provider (Lightfoot) should be unique because the login form will be checking for this combo
 */
#[Route('/auth/create')]
class AuthCreateUserController extends AbstractController
{

    /**
     * define a user creation form
     */
    #[Route('/', name: 'app_auth_create', methods: ['GET'])]
    public function create(): Response
    {
        return new Response('Create User');
    }

    /**
     * handle user creation form submission.
     * check for unique email + provider combo
     * if no matching user found we can continue with the creation process
     * redirect to dashboard on completion
     */
    #[Route('/', name: 'app_auth_create_process', methods: ['POST'])]
    public function processCreate(): Response
    {
        return new Response('Create User');
    }

}
