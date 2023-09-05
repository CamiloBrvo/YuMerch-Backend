<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Status;
use App\Repository\StatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StatusController extends AbstractController
{
    #[Route('/api/status', name: 'listStatus', methods: "GET")]
    public function getAllRoles(StatusRepository $statusRepository, SerializerInterface $serializer, Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 50);

        $statusList = $statusRepository->findAllWithPagination($page, $limit);

        $jsonStatusList = $serializer->serialize($statusList, 'json', ['groups' => 'public']);

        return new JsonResponse($jsonStatusList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/status/{id}', name: 'detailStatus', methods: "GET")]
    public function getDetailStatus(Status $status, SerializerInterface $serializer): JsonResponse
    {
        // Check if status exist and if delete
        if ($status->getDeletedAt() !== null) {
            throw new BadRequestHttpException("This status has been deleted and is no longer available.");
        }

        $jsonStatus = $serializer->serialize($status, 'json', ['groups' => 'public']);

        return new JsonResponse($jsonStatus, Response::HTTP_OK, [], true);
    }

    #[Route('/api/status', name: 'createStatus', methods: "POST")]
    #[IsGranted('ROLE_ADMIN', message: "You don't have the permission")]
    public function createStatus(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $status = $serializer->deserialize($request->getContent(), Status::class, 'json');

        $errors = $validator->validate($status);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // Check if status exist with same name exist
        $existingStatus = $em->getRepository(Status::class)->findOneBy(['name' => $status->getName()]);

        if ($existingStatus) {
            throw new BadRequestHttpException("The status cannot be created, as a status with this name already exists.");
        }

        $em->persist($status);
        $em->flush();

        $jsonStatus = $serializer->serialize($status, 'json', ['groups' => 'public']);
        $location = $urlGenerator->generate('detailStatus', ['id' => $status->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonStatus, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/status/{id}', name: 'updateStatus', methods: "PUT")]
    #[IsGranted('ROLE_ADMIN', message: "You don't have the permission")]
    public function updateStatus(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, Status $currentStatus, EntityManagerInterface $em): JsonResponse
    {
        // Check if user exist and if delete
        if ($currentStatus->getDeletedAt() !== null) {
            throw new BadRequestHttpException("This status has been deleted you cannot edit it.");
        }

        $updatedStatus = $serializer->deserialize($request->getContent(), Status::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentStatus]);

        $errors = $validator->validate($updatedStatus);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // Check if status exist with same name exist
        $existingStatus = $em->getRepository(Status::class)->findOneBy(['name' => $updatedStatus->getName()]);

        if ($existingStatus) {
            throw new BadRequestHttpException("The status cannot be created, as a status with this name already exists.");
        }

        $em->persist($updatedStatus);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/status/{id}', name: 'deleteStatus', methods: "DELETE")]
    #[IsGranted('ROLE_ADMIN', message: "You don't have the permission")]
    public function detleteStatus(Status $status, StatusRepository $statusRepository, EntityManagerInterface $em): JsonResponse
    {
        // Check if user is link to delete status
        $productUseStatus = $em->getRepository(Product::class)->findOneBy(['status' => $status]);

        if(!empty($productUseStatus)) {
            throw new BadRequestHttpException("The status cannot be deleted, a product(s) use this status.");
        }

        $statusRepository->remove($status);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
