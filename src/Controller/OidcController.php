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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

    #[Route('/login/{provider}', name: 'oidc_login')]
    public function login(string $provider): NotFoundHttpException|RedirectResponse
    {
        try {
            $client = $this->clientRegistry->getClient($provider);
        } catch (\InvalidArgumentException $e) {
            return $this->createNotFoundException();
        }

        return $client->redirect([
            'openid', 'profile', 'email'
        ]);
    }

    #[Route('/logout/{provider}', name: 'oidc_logout')]
    public function logout(string $provider): Response
    {
        return new Response('logout.');
    }

    #[Route('/callback/{provider}', name: 'oidc_callback')]
    public function callback(string $provider): NotFoundHttpException|Response
    {
        try {
            $client = $this->clientRegistry->getClient($provider);
        } catch (\InvalidArgumentException $e) {
            return $this->createNotFoundException();
        }

        try {
            $accessToken = $client->getAccessToken();
            $remoteUser = $client->fetchUserFromToken($accessToken);
        } catch (IdentityProviderException $e) {
            return new Response('callback error: '.$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $localUser = $this->userService->findOrCreate($remoteUser);
            $internalTokenData = $this->tokenService->createToken($accessToken);
            $tokenData = $this->tokenService->issueTokens($internalTokenData, $localUser);

            $cookieAccess = $this->cookieService->createAccess($tokenData->getLocalAccessToken(), $tokenData->getLocalAccessTokenExpiresAt());
            $cookieRefresh = $this->cookieService->createRefresh($tokenData->getLocalRefreshToken(), $tokenData->getLocalRefreshTokenExpiresAt());

            $response = $this->redirectToRoute('app_dashboard_index');
            $response->headers->setCookie($cookieAccess);
            $response->headers->setCookie($cookieRefresh);

            return $response;

        } catch (\Throwable $e) {
            return new Response(sprintf('callback error: %s', $e->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
