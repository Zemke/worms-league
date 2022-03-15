<?php
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRepository;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
class UsernameOrEmailAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(private UserRepository $userRepository, private HttpUtils $httpUtils)
    {
        $this->userRepository = $userRepository;
        $this->httpUtils = $httpUtils;
    }

    protected function getLoginUrl(Request $request): string
    {
        //return $this->httpUtils->generateUri($request, $this->options['login_path']);
        return $this->httpUtils->generateUri($request, '/login');
    }

    public function authenticate(Request $request): Passport
    {
        dump($request->request->all());
        [
            'usernameOrEmail' => $usernameOrEmail,
            'password' => $password,
            '_csrf_token' => $csrfToken,
        ] = $request->request->all();
        // TODO forbid @ signs in usernames
        $isUsername = strpos($usernameOrEmail, '@') === false;
        if ($isUsername) {
            $username = $usernameOrEmail;
        } else {
            $username = $this->userRepository->findOneBy(['email' => $usernameOrEmail])->getUsername(); // TODO case-insensitive
        }
        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password),
            [new CsrfTokenBadge('authenticate', $csrfToken), new RememberMeBadge(),],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST')
            && $this->httpUtils->checkRequestPath($request, '/login')
            && 'form' === $request->getContentType();
    }
}

