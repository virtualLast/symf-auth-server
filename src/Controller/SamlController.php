<?php

declare(strict_types=1);

namespace App\Controller;

use App\Mapper\ResourceOwnerMapper;
use App\Model\Dto\SamlBasicUserDto;
use App\Model\Enum\ProviderEnum;
use App\OAuth\Exception\OauthException;
use App\Service\CookieService;
use App\Service\MetadataService;
use App\Service\TokenService;
use App\Service\UserService;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\ValidationError;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[Route('/saml')]
class SamlController extends AbstractController
{

    public function __construct(
        private readonly MetadataService $metadataService,
        private readonly CookieService $cookieService,
        private readonly UserService $userService,
        private readonly TokenService $tokenService,
        private readonly ResourceOwnerMapper $resourceOwnerMapper,
        private readonly LoggerInterface $logger
    )
    {
    }

    /**
     * @throws Error|OauthException
     */
    #[Route('/login', name: 'saml_login')]
    public function login(): Response
    {
        $auth = $this->getAuth();
        $auth->login();
        return new Response('login.');
    }

    /**
     * @throws Error|OauthException
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
     * @throws OAuthException
     */
    #[Route('/acs', name: 'saml_acs')]
    public function acs(Request $request): Response
    {
        $auth = $this->getAuth();
        $auth->processResponse();

        $errors = $auth->getErrors();
        if(count($errors) > 0) {
            $this->logger->error('Saml2 Error: ' . implode(', ', $errors));
            throw new OauthException('Acs SAML error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $nameId = $auth->getNameId();

        $remoteUser = new SamlBasicUserDto();
        $remoteUser->setId($nameId);

        try {
            $dto = $this->resourceOwnerMapper->map($remoteUser, ProviderEnum::KEYCLOAK_SAML);
            $localUser = $this->userService->findOrCreate($dto);

            $internalTokenData = $this->tokenService->createSamlToken();
            $tokenData = $this->tokenService->issueTokens($internalTokenData, $localUser);

            $cookieAccess = $this->cookieService->createAccess($tokenData->getLocalAccessToken(), $tokenData->getLocalAccessTokenExpiresAt());
            $cookieRefresh = $this->cookieService->createRefresh($tokenData->getLocalRefreshToken(), $tokenData->getLocalRefreshTokenExpiresAt());

            $response = $this->redirectToRoute('app_dashboard_index');
            $response->headers->setCookie($cookieAccess);
            $response->headers->setCookie($cookieRefresh);

            return $response;

        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            throw new OauthException('Token Issuance SAML error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

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
            $this->logger->error('Invalid SP metadata: ' . implode(', ', $errors));
            throw new OauthException('Invalid SAML Provider metadata');
        }

        return new Response($metadata, Response::HTTP_OK, ['Content-Type' => 'application/xml']);
    }

    /**
     * @throws OauthException
     */
    private function getAuth(): Auth
    {
        try {
            $settings = require $this->getParameter('kernel.project_dir') . '/config/saml/settings.php';
            $metaData = $this->metadataService->getMetadata();
            if(isset($settings[MetadataService::SETTINGS_KEY_IDP])) {
                $settings[MetadataService::SETTINGS_KEY_IDP][MetadataService::SETTINGS_KEY_CERT] = $metaData->getCertificate();
            }
            return new Auth($settings);
        } catch (Error|\Exception|InvalidArgumentException|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            $this->logger->error($e->getMessage());
            throw new OauthException('Unable to create SAML Auth instance', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
