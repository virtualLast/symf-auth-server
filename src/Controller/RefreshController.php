<?php

namespace App\Controller;

use App\OAuth\Exception\OauthException;
use App\Service\CookieService;
use App\Service\TokenService;
use App\Service\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/refresh')]
class RefreshController
{
    private const REFRESH_TOKEN_REQUEST_ATTRIBUTE = 'refresh_token';

    public function __construct(
        private readonly TokenService $tokenService,
        private readonly UserService $userService,
        private readonly CookieService $cookieService,
        private readonly LoggerInterface $logger
    )
    {
    }

    /**
     * todo look to implement expiration
     * @throws \Exception
     */
    #[Route('/', name: 'app_refresh')]
    public function refresh(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data[self::REFRESH_TOKEN_REQUEST_ATTRIBUTE] ?? null;
        if($refreshToken === null) {
            throw new OauthException('Refresh token not found', Response::HTTP_BAD_REQUEST);
        }

        // call token service to find token
        $tokenData = $this->tokenService->findByLocalRefreshToken($refreshToken);
        if($tokenData === null) {
            $this->logger->error(sprintf('Refresh token (%s) not found', $refreshToken));
            throw new OauthException('Refresh token not found', Response::HTTP_BAD_REQUEST);
        }
        if ($tokenData->isRevoked()) {
            throw new OauthException('Refresh token revoked', Response::HTTP_BAD_REQUEST);
        }
        if ($tokenData->getLocalRefreshTokenExpiresAt() < new \DateTimeImmutable()) {
            throw new OauthException('Refresh token expired', Response::HTTP_BAD_REQUEST);
        }
        // call to user service to find user, use the user we get from the token
        $user = $tokenData->getUser();
        $userObject = $this->userService->findByTokenSub($user->getTokenSub(), $user->getProvider());
        if($userObject === null) {
            throw new OauthException('User not found', Response::HTTP_BAD_REQUEST);
        }
        // revoke existing refresh and access token
        $this->tokenService->revokeToken($refreshToken);
        // create new refresh and access token
        $token = $this->tokenService->createSimpleToken();
        // issue tokens to user
        $newTokenData = $this->tokenService->issueTokens($token, $user);
        // create a json response for the access token and set a new cookie for the refresh token
        $response = new JsonResponse(['access_token' => $newTokenData->getLocalAccessToken()]);
        $response->headers->setCookie($this->cookieService->createRefresh($newTokenData->getRawLocalRefreshToken(), $newTokenData->getLocalRefreshTokenExpiresAt()));
        $response->headers->setCookie($this->cookieService->createAccess($newTokenData->getLocalAccessToken(), $newTokenData->getLocalAccessTokenExpiresAt()));

        return $response;

    }
}
