<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
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

class CategoryController extends AbstractController
{
    #[Route('/api/categories', name: 'listCategories', methods: "GET")]
    public function getAllCategorys(CategoryRepository $categoryRepository, SerializerInterface $serializer, Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 50);

        $categoryList = $categoryRepository->findAllWithPagination($page, $limit);
        $jsonCategoryList = $serializer->serialize($categoryList, 'json', ['groups' => 'public']);

        return new JsonResponse($jsonCategoryList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/category/{id}', name: 'detailCategory', methods: "GET")]
    public function getDetailCategory(Category $category, SerializerInterface $serializer): JsonResponse
    {
        // Check if role exist and if delete
        if ($category->getDeletedAt() !== null) {
            throw new BadRequestHttpException("This category has been deleted and is no longer available.");
        }

        $jsonCategory = $serializer->serialize($category, 'json', ['groups' => 'public']);

        return new JsonResponse($jsonCategory, Response::HTTP_OK, [], true);
    }

    #[Route('/api/category', name: 'createCategory', methods: "POST")]
    #[IsGranted('ROLE_ADMIN', message: "You don't have the permission")]
    public function createCategory(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $category = $serializer->deserialize($request->getContent(), Category::class, 'json');

        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // Check if category exist with same name exist
        $existingCategory = $em->getRepository(Category::class)->findOneBy(['name' => $category->getName()]);

        if ($existingCategory) {
            throw new BadRequestHttpException("The category cannot be created, as a category with this name already exists.");
        }

        $em->persist($category);
        $em->flush();

        $jsonCategory = $serializer->serialize($category, 'json');
        $location = $urlGenerator->generate('detailCategory', ['id' => $category->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonCategory, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/category/{id}', name: 'updateCategory', methods: "PUT")]
    #[IsGranted('ROLE_ADMIN', message: "You don't have the permission")]
    public function updateCategory(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, Category $currentCategory, EntityManagerInterface $em): JsonResponse
    {
            // Check if user exist and if delete
        if ($currentCategory->getDeletedAt() !== null) {
            throw new BadRequestHttpException("This category has been deleted you cannot edit it.");
        }

        $updatedCategory = $serializer->deserialize($request->getContent(), Category::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentCategory]);

        $errors = $validator->validate($updatedCategory);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // Check if category exist with same name exist
        $existingCategory = $em->getRepository(Category::class)->findOneBy(['name' => $updatedCategory->getName()]);

        if ($existingCategory) {
            throw new BadRequestHttpException("The category cannot be created, as a category with this name already exists.");
        }

        $em->persist($updatedCategory);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/category/{id}', name: 'deleteCategory', methods: "DELETE")]
    #[IsGranted('ROLE_ADMIN', message: "You don't have the permission")]
    public function deleteCategory(Category $category, CategoryRepository $categoryRepository, EntityManagerInterface $em): JsonResponse
    {
        // Check if product is link to delete category
        $productUseCategory = $em->getRepository(Product::class)->findByCategory($category);

        if(!empty($productUseCategory)) {
            throw new BadRequestHttpException("The category cannot be deleted, a product(s) use this category.");
        }

        $categoryRepository->remove($category);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
