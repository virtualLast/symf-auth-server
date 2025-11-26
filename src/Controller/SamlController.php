<?php

declare(strict_types=1);

namespace App\Controller;

use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\ValidationError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/saml')]
class SamlController extends AbstractController
{

    /**
     * @throws Error
     */
    private function getAuth(): Auth
    {
        $settings = require $this->getParameter('kernel.project_dir') . '/config/saml/settings.php';
        try {
            return new Auth($settings);
        } catch (Error|\Exception $e) {
            throw new Error('Unable to create OneLogin_Saml2_Auth instance: ' . $e->getMessage());
        }
    }

    /**
     * @throws Error
     */
    #[Route('/login', name: 'saml_login')]
    public function login(): Response
    {
        $auth = $this->getAuth();
        $settings = $auth->getSettings();
        $auth->login();
        return new Response('login.');
    }

    /**
     * @throws Error
     */
    #[Route('/logout', name: 'saml_logout')]
    public function logout(): Response
    {
        $auth = $this->getAuth();
        $auth->logout();
        return new Response('logout.');
    }

    /**
     * @throws Error
     * @throws ValidationError
     */
    #[Route('/acs', name: 'saml_acs')]
    public function acs(Request $request): Response
    {
        $auth = $this->getAuth();
        $auth->processResponse();

        $errors = $auth->getErrors();
        if(count($errors) > 0) {
            throw new Error('Saml2 Error: ' . implode(', ', $errors));
        }

        $attributes = $auth->getAttributes();
        $nameId = $auth->getNameId();

        $request->getSession()->set('saml_user', [
            'attributes' => $attributes,
            'nameId' => $nameId,
        ]);

        return $this->redirectToRoute('app_dashboard_index');
    }

    /**
     * Endpoint used by keycloak to find out data about us, the service provider.
     * @throws Error
     * @throws \Exception
     */
    #[Route('/meta', name: 'saml_meta')]
    public function meta(): Response
    {
        $auth = $this->getAuth();
        $settings = $auth->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (count($errors) > 0) {
            throw new Error('Invalid SP metadata: ' . implode(', ', $errors));
        }

        return new Response($metadata, Response::HTTP_OK, ['Content-Type' => 'application/xml']);
    }
}
