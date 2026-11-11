<?php

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public const HEADER = 'X-API-KEY';

    public function __construct(
        #[Autowire(env: 'API_KEY')]
        private readonly string $apiKey,
    ) {
    }

    // Toutes les requêtes du firewall /api doivent s'authentifier : header absent = 401.
    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $providedKey = $request->headers->get(self::HEADER) ?? '';

        // hash_equals : comparaison en temps constant (évite les attaques par timing).
        // Garde sur '' : si API_KEY n'est pas configurée, on refuse tout au lieu d'accepter une clé vide.
        if ('' === $this->apiKey || !hash_equals($this->apiKey, $providedKey)) {
            throw new CustomUserMessageAuthenticationException('Invalid or missing API key.');
        }

        return new SelfValidatingPassport(new UserBadge('api_client'));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // on laisse la requête continuer vers le contrôleur
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse(
            ['error' => strtr($exception->getMessageKey(), $exception->getMessageData())],
            Response::HTTP_UNAUTHORIZED,
            // Obligatoire sur un 401 (RFC 9110) : indique au client comment s'authentifier.
            ['WWW-Authenticate' => 'ApiKey header="'.self::HEADER.'"'],
        );
    }
}
