<?php

namespace App\Controller;

use App\OAuth\Exception\OauthException;
use App\Service\CookieService;
use App\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/logout', methods: ['POST'])]
class LogoutController extends AbstractController
{
    private const REDIRECT_URL_REQUEST_ATTRIBUTE = 'redirect_url';

    public function __construct(
        private readonly TokenService  $tokenService,
    ) {}

    #[Route('/' , name: 'app_logout')]
    public function logout(Request $request): Response
    {
        $refreshToken = $request->cookies->get(CookieService::REFRESH_COOKIE_NAME);
        if($refreshToken === null) {
            throw new OauthException('Refresh token not found', Response::HTTP_BAD_REQUEST);
        }

        $redirectUrl = $request->request->get(self::REDIRECT_URL_REQUEST_ATTRIBUTE);
        if($redirectUrl === null) {
            throw new OauthException('Redirect location not found', Response::HTTP_BAD_REQUEST);
        }
        if(!filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            throw new OauthException('Invalid redirect location', Response::HTTP_BAD_REQUEST);
        }

        $this->tokenService->revokeToken($refreshToken);

        $redirectResponse = new RedirectResponse($redirectUrl);
        $redirectResponse->headers->clearCookie(CookieService::REFRESH_COOKIE_NAME);
        $redirectResponse->headers->clearCookie(CookieService::ACCESS_COOKIE_NAME);

        return $this->redirect($redirectUrl);
    }
}
