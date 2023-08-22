<?php

namespace App\Security;

use App\Services\TokenService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use App\Repository\TokenRepository;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;


class AppAuthenticator extends AbstractAuthenticator
{
    /**
     * @var TokenRepository
     */
    private $tokenRepository;

    /**
     * @var TokenService
     */
    private $tokenService;

    public function __construct(TokenRepository $tokenRepository, TokenService $tokenService)
    {
        $this->tokenRepository = $tokenRepository;
        $this->tokenService = $tokenService;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $tokenValue = str_replace('Bearer ', '', $request->headers->get('authorization'));
        $token = $this->tokenRepository->findOneBy(['token' => $tokenValue]);
        if ($token === null) throw new CustomUserMessageAuthenticationException('Invalid token');
        if ($token->isNeedTOTPVerification() && $request->attributes->get('_route') !== 'app_security_2FA') throw new CustomUserMessageAuthenticationException('2FA verification needed');

        $user = $token->getUser();
        $this->tokenService->token = $token;

        return new SelfValidatingPassport(new UserBadge($user->getEmail()));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }


    private function manage2FA(Request $request)
    {
        $request->attributes->get('_route');
    }


//    public function start(Request $request, AuthenticationException $authException = null): Response
//    {
//        /*
//         * If you would like this class to control what happens when an anonymous user accesses a
//         * protected page (e.g. redirect to /login), uncomment this method and make this class
//         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
//         *
//         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
//         */
//    }
}
