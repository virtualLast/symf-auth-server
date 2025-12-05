<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CookieService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/oidc')]
class OidcController extends AbstractController
{
    public function __construct(private readonly ClientRegistry $clientRegistry, private readonly CookieService $cookieService)
    {
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
            $user = $client->fetchUserFromToken($accessToken);
        } catch (IdentityProviderException $e) {
            return new Response('callback error: '.$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response = new Response();

        if (
            $accessToken instanceof AccessTokenInterface
            && $user instanceof ResourceOwnerInterface
        ) {
            /**
             * todo validate user data against the db
             * todo store refresh token in db along with user data + access token
             * todo create custom access token cookie
             */
            $cookie = $this->cookieService->create($accessToken);
            $response->headers->setCookie($cookie);
        }

        $response->setContent(sprintf('callback success: %s', json_encode(
            [
                'user' => $user->toArray(),
                'access_token' => $accessToken
            ]
        )));

        return $response;
    }
}
