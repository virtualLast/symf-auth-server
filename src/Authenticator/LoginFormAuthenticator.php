<?php

namespace App\Authenticator;

use App\Entity\User;
use App\Service\CookieService;
use App\Service\TokenService;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{

    public function __construct(
        private readonly UserService $userService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TokenService $tokenService,
        private readonly CookieService $cookieService
    ) {
    }

    /**
     * Return the URL to the login page.
     */
    protected function getLoginUrl(Request $request): string
    {
        return '/auth/login';
    }

    /**
     * Create a passport for the current request.
     *
     * The passport contains the user, credentials and any additional information
     * that has to be checked by the Symfony Security system. For example, a login
     * form authenticator will probably return a passport containing the user, the
     * presented password and the CSRF token value.
     *
     * You may throw any AuthenticationException in this method in case of error (e.g.
     * a UserNotFoundException when the user cannot be found).
     *
     * @throws AuthenticationException
     */
    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('csrf_token');


        if (
            $email === null
            || $password === null
            || $csrfToken === null
        ) {
            throw new AuthenticationException('Invalid credentials.');
        }

        return new Passport(
            new UserBadge($email, function (string $email): ?User {
                return $this->userService->findByEmail($email);
            }),
            new PasswordCredentials($password),
            [new CsrfTokenBadge('authenticate', $csrfToken)]
        );
    }

    /**
     * @throws \Exception
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        /** @var User $user */
        $user = $token->getUser();

        // Mirror OIDC behaviour from this point onward
        $internalTokenData = $this->tokenService->createSimpleToken();
        $tokenData = $this->tokenService->issueTokens($internalTokenData, $user);

        $cookieAccess = $this->cookieService->createAccess(
            $tokenData->getLocalAccessToken(),
            $tokenData->getLocalAccessTokenExpiresAt()
        );

        $cookieRefresh = $this->cookieService->createRefresh(
            $tokenData->getRawLocalRefreshToken(),
            $tokenData->getLocalRefreshTokenExpiresAt()
        );

        $response = new RedirectResponse(
            $this->urlGenerator->generate('app_dashboard_index')
        );

        $response->headers->setCookie($cookieAccess);
        $response->headers->setCookie($cookieRefresh);

        return $response;
    }
}
