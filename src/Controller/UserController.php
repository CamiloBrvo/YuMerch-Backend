<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    #[Route('/api/users', name: 'listUsers', methods: "GET")]
    public function getAllUsers(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $userList = $userRepository->findAll();

        $jsonUserList = $serializer->serialize($userList, 'json', ['groups' => 'public']);

        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/user/{id}', name: 'detailUser', methods: "GET")]
    public function getDetailUser(User $user, SerializerInterface $serializer): JsonResponse
    {
        // Check if user exist and if delete
        if ($user->getDeletedAt() !== null) {
            throw new BadRequestHttpException("This user has been deleted and is no longer available.");
        }

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'public']);

        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route('/api/user', name: 'createUser', methods: "POST")]
    public function createUser(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $content = json_decode($request->getContent(), true);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // Check if user exist with same email exist
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);

        if ($existingUser) {
            throw new BadRequestHttpException("The user cannot be created, as a user with this email already exists.");
        }

        $em->persist($user);
        $em->flush();

        $jsonUser = $serializer->serialize($user, 'json');
        $location = $urlGenerator->generate('detailUser', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/user/{id}', name: 'updateUser', methods: "PUT")]
    public function updateUser(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, User $currentUser, EntityManagerInterface $em): JsonResponse
    {
        // Check if user exist and if delete
        if ($currentUser->getDeletedAt() !== null) {
            throw new BadRequestHttpException("This user has been deleted you cannot edit it.");
        }

        $updatedUser = $serializer->deserialize($request->getContent(), User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);

        $content = json_decode($request->getContent(), true);

        $errors = $validator->validate($updatedUser);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // Check if any user with the same email exists
        $usersWithSameEmail = $em->getRepository(User::class)->findBy(['email' => $updatedUser->getEmail()]);

        $usersWithSameEmail = array_filter($usersWithSameEmail, function ($user) use ($currentUser) {
            return $user->getId() !== $currentUser->getId();
        });

        if ($usersWithSameEmail) {
            throw new BadRequestHttpException("The user cannot be created, as a user with this email already exists.");
        }

        $em->persist($updatedUser);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/user/{id}', name: 'deleteUser', methods: "DELETE")]
    public function detleteUser(User $user, UserRepository $userRepository): JsonResponse
    {
        $userRepository->remove($user);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
