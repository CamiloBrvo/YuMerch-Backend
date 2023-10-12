<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\ExpiredTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AuthController extends AbstractController
{
    private $jwtTokenManager;
    private $security;

    public function __construct(JWTTokenManagerInterface $jwtTokenManager,AuthorizationCheckerInterface $authorizationChecker, Security $security)
    {
        $this->jwtTokenManager = $jwtTokenManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->security = $security;
    }

    public function login(Request $request, CacheInterface $cache): JsonResponse
    {
        // Récupérer les informations d'identification depuis la demande JSON
        $credentials = json_decode($request->getContent(), true);

        // Votre logique d'authentification ici (vérification des identifiants, etc.)
        // ...

        // Si l'authentification réussit, générer un jeton JWT
        $user = $this->security->getUser();

        // Récupérez les rôles de l'utilisateur
        $roles = $user->getRoles();

        // Créez les données à inclure dans le token (y compris les rôles)
        $tokenData = [
            'username' => $user->getUsername(),
            'roles' => $roles, // Ajoutez les rôles ici
        ];

        $token = $this->jwtTokenManager->create($tokenData);

        // Enregistrez le token en cache
        $cache->get('user_token_' . $user->getId(), function (ItemInterface $item) use ($token) {
            $item->expiresAfter(3600); // Temps d'expiration du cache en secondes
            $item->set($token);

            return $token;
        });

        return new JsonResponse(['token' => $token]);
    }

    public function checkToken(Request $request, CacheInterface $cache, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        // Récupérez le token depuis l'en-tête Authorization
        $authorizationHeader = $request->headers->get('Authorization');

        if (!$authorizationHeader || strpos($authorizationHeader, 'Bearer ') !== 0) {
            return new Response('Token invalide', 401);
        }

        $token = substr($authorizationHeader, 7);

        try {
            $this->jwtTokenManager->parse($token);
            return new Response('Token valide', 200);
        } catch (ExpiredTokenException $e) {
            return new Response('Token expiré', 401);
        } catch (InvalidTokenException $e) {
            return new Response('Token invalide', 401);
        }
    }
    public function checkAdmin(Request $request): Response
    {
        // Récupérez le token depuis l'en-tête Authorization
        $authorizationHeader = $request->headers->get('Authorization');

        if (!$authorizationHeader || strpos($authorizationHeader, 'Bearer ') !== 0) {
            return new Response('Token invalide', 401);
        }

        $token = substr($authorizationHeader, 7);

        try {
            $tokenData = $this->jwtTokenManager->parse($token);
            // Vérification du rôle ROLE_ADMIN
            if (in_array('ROLE_ADMIN', $tokenData['roles'])) {
                return new Response('User Admin', 200);
            } else {
                return new Response('No Admin', 401);
            }
        } catch (\Exception $e) {
            return new Response('Token invalide', 401);
        }
    }




}
