<?php

declare(strict_types=1);

namespace App\Controller;

use App\Mapper\ResourceOwnerMapper;
use App\Model\Enum\ProviderEnum;
use App\OAuth\Exception\OauthException;
use App\Service\CookieService;
use App\Service\ScopeService;
use App\Service\TokenParamsService;
use App\Service\TokenService;
use App\Service\UserService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use KnpU\OAuth2ClientBundle\DependencyInjection\InvalidOAuth2ClientException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        private readonly TokenService $tokenService,
        private readonly ResourceOwnerMapper $resourceOwnerMapper,
        private readonly ScopeService $scopeService,
        private readonly TokenParamsService $tokenParamsService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/login/{provider}', name: 'oidc_login')]
    public function login(string $provider): RedirectResponse
    {
        $client = $this->getOAuthClientOr404($provider);
        $providerEnum = ProviderEnum::from($provider);
        $scopes = $this->scopeService->getScopesForProvider($providerEnum);

        return $client->redirect($scopes);
    }

    /**
     * @throws OauthException
     */
    #[Route('/callback/{provider}', name: 'oidc_callback')]
    public function callback(string $provider): Response
    {
        $client = $this->getOAuthClientOr404($provider);

        $provider = ProviderEnum::from($provider);

        try {
            $accessToken = $client->getAccessToken();
            $remoteUser = $client->fetchUserFromToken($accessToken); // contains params['AccessLevel', 'HierCode']
        } catch (IdentityProviderException $e) {
            $this->logger->error($e->getMessage());
            throw new OauthException('Token callback error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $accessRoles = $this->tokenParamsService->parse($remoteUser, $provider);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            throw new OauthException('Access Roles callback error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $dto = $this->resourceOwnerMapper->map($remoteUser, $provider, $accessRoles);
            $localUser = $this->userService->findOrCreate($dto);

            $internalTokenData = $this->tokenService->createToken($accessToken);
            $tokenData = $this->tokenService->issueTokens($internalTokenData, $localUser);

            $cookieAccess = $this->cookieService->createAccess($tokenData->getLocalAccessToken(), $tokenData->getLocalAccessTokenExpiresAt());
            $cookieRefresh = $this->cookieService->createRefresh($tokenData->getRawLocalRefreshToken(), $tokenData->getLocalRefreshTokenExpiresAt());

            $response = $this->redirectToRoute('app_dashboard_index');
            $response->headers->setCookie($cookieAccess);
            $response->headers->setCookie($cookieRefresh);

            return $response;

        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            throw new OauthException('Token Issuance callback error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getOAuthClientOr404(string $provider): OAuth2ClientInterface
    {
        $providerEnum = ProviderEnum::tryFrom($provider);
        if (!$providerEnum) {
            throw $this->createNotFoundException();
        }

        try {
            return $this->clientRegistry->getClient($providerEnum->value);
        } catch (InvalidOAuth2ClientException) {
            // Enum exists but client is not configured
            throw $this->createNotFoundException();
        }
    }
}
