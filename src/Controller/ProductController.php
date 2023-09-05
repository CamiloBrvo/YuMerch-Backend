<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\StatusRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

class ProductController extends AbstractController
{
    #[Route('api/products', name: 'listProducts', methods: "GET")]
    public function getAllProducts(ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {
        $productList = $productRepository->findAll();

        $jsonProductList = $serializer->serialize($productList, 'json', ['groups' => 'public']);

        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }

    #[Route('api/product/{id}', name: 'detailProduct', methods: "GET")]
    public function getDetailProduct(Product $product, SerializerInterface $serializer): JsonResponse
    {
        // Check if role exist and if delete
        if ($product->getDeletedAt() !== null) {
            throw new BadRequestHttpException("This product has been deleted and is no longer available.");
        }

        $jsonProduct = $serializer->serialize($product, 'json', ['groups' => 'public']);

        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }

    #[Route('api/product', name: 'createProduct', methods: "POST")]
    public function createProduct(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, CategoryRepository $categoryRepository, StatusRepository $statusRepository): JsonResponse
    {
        $productData = json_decode($request->getContent(), true);

        $product = new Product();

        $serializer->deserialize(json_encode($productData), Product::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $product]);
        $content = json_decode($request->getContent(), true);

        $categoryIds = $productData['categories'] ?? [];
        $status = $content['status'] ?? -1;

        $categories = new ArrayCollection();

        foreach ($categoryIds as $categoryId) {
            $category = $categoryRepository->find($categoryId);
            if ($category && !$categories->contains($category)) {
                $categories->add($category);
            }
        }

        $product->setCategories($categories);

        // Check if role exist
        if (!$statusRepository->find($status)) {
            throw new BadRequestHttpException("This status does not exist.");
        }

        $product->setStatus($statusRepository->find($status));

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // Check if product exist with same title exist
        $existingProduct = $em->getRepository(Product::class)->findOneBy(['title' => $product->getTitle()]);

        if ($existingProduct) {
            throw new BadRequestHttpException("The product cannot be created, as a product with this title already exists.");
        }

        $em->persist($product);
        $em->flush();

        $jsonProduct = $serializer->serialize($product, 'json', ['groups' => 'public']);
        $location = $urlGenerator->generate('detailProduct', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonProduct, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('api/product/{id}', name: 'updateProduct', methods: "PUT")]
    public function updateProduct(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, Product $currentProduct, EntityManagerInterface $em, CategoryRepository $categoryRepository, StatusRepository $statusRepository): JsonResponse
    {
        // Check if product exist and if delete
        if ($currentProduct->getDeletedAt() !== null) {
            throw new BadRequestHttpException("This product has been deleted you cannot edit it.");
        }

        $updatedProduct = $serializer->deserialize($request->getContent(), Product::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentProduct]);

        $content = json_decode($request->getContent(), true);

        $categoryIds = $content['categories'] ?? [];
        $status = $content['status'] ?? -1;

        $categories = new ArrayCollection();

        foreach ($categoryIds as $categoryId) {
            $category = $categoryRepository->find($categoryId);
            if ($category && !$categories->contains($category)) {
                $categories->add($category);
            }
        }

        $updatedProduct->setCategories($categories);

        // Check if role exist
        if (!$statusRepository->find($status)) {
            throw new BadRequestHttpException("This status does not exist.");
        }

        $updatedProduct->setStatus($statusRepository->find($status));

        $errors = $validator->validate($updatedProduct);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // Check if product exist with same title exist
        $existingProductsSameTitle = $em->getRepository(Product::class)->findBy(['title' => $updatedProduct->getTitle()]);

        if (!empty($existingProductsSameTitle)) {
            $existingProductsSameTitle = array_filter($existingProductsSameTitle, function ($product) use ($currentProduct) {
                return $product->getId() !== $currentProduct->getId();
            });

            if (!empty($existingProductsSameTitle)) {
                throw new BadRequestHttpException("The product cannot be created, as a product with this title already exists.");
            }
        }

        $em->persist($updatedProduct);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('api/product/{id}', name: 'deleteProduct', methods: "DELETE")]
    public function deleteProduct(Product $product, ProductRepository $productRepository): JsonResponse
    {
        $productRepository->remove($product);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
