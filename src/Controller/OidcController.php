<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CookieService;
use App\Service\TokenService;
use App\Service\UserService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/oidc')]
class OidcController extends AbstractController
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly CookieService $cookieService,
        private readonly UserService $userService,
        private readonly TokenService $tokenService
    ) {
    }

    #[Route('/login', name: 'oidc_login')]
    public function login(): Response
    {
        /* @var $client KeycloakClient */
        $client = $this->clientRegistry->getClient('keycloak');
        return $client->redirect([
            'openid', 'profile', 'email'
        ]);
    }

    #[Route('/logout', name: 'oidc_logout')]
    public function logout(): Response
    {
        return new Response('logout.');
    }

    #[Route('/callback', name: 'oidc_callback')]
    public function callback(Request $request): Response
    {
        /* @var $client KeycloakClient */
        $client = $this->clientRegistry->getClient('keycloak');

        try {
            $accessToken = $client->getAccessToken();
            $remoteUser = $client->fetchUserFromToken($accessToken);
        } catch (IdentityProviderException $e) {
            return new Response('callback error: '.$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $localUser = $this->userService->findOrCreate($remoteUser);
            $internalTokenData = $this->tokenService->createToken($accessToken); // create tokens
            // issue tokens is where we connect token to the user

            $cookieAccess = $this->cookieService->createAccess($internalTokenData['access_token']);
            $cookieRefresh = $this->cookieService->createRefresh($internalTokenData['refresh_token']);

            $response = $this->redirectToRoute('app_dashboard_index');
            $response->headers->setCookie($cookieAccess);
            $response->headers->setCookie($cookieRefresh);

            return $response;

        } catch (\Throwable $e) {
            return new Response(sprintf('callback error: %s', $e->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
