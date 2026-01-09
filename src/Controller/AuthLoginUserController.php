<?php

namespace App\Controller;

use App\Form\Type\LoginFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/auth/login')]
class AuthLoginUserController extends AbstractController
{
    /**
     * We only display the form. form processing is done in the Authenticator
     * @see src/Authenticator/LoginFormAuthenticator
     */
    #[Route('/', name: 'app_auth_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $lastUsername = $authenticationUtils->getLastUsername();
        $error = $authenticationUtils->getLastAuthenticationError();

        $form = $this->createForm(LoginFormType::class);

        return $this->render('auth/login.html.twig', [
            'error' => $error,
            'last_username' => $lastUsername,
            'loginForm' => $form->createView(),
        ]);
    }

}
