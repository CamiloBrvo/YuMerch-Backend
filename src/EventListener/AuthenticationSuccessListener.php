<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthenticationSuccessListener
{
    private $serializer;
    private $jwtTokenManager;

    public function __construct(JWTTokenManagerInterface $jwtTokenManager, SerializerInterface $serializer)
    {
        $this->jwtTokenManager = $jwtTokenManager;
        $this->serializer = $serializer;
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        $payload = $event->getData();

        if (!$user instanceof User) {
            return;
        }

        // Add information to user payload
        /*
        $payload['user'] = [
            $user->getId(),
            $user->getUserIdentifier(),
        ];
        */
        $event->setData($payload);
    }
}