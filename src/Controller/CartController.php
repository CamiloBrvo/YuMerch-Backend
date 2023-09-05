<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CartController extends AbstractController
{
    #[Route("/api/carts", name:"listCarts", methods:"GET")]
    public function getAllCarts(CartRepository $cartRepository, SerializerInterface $serializer): JsonResponse
    {
        $cartsList = $cartRepository->findAll();

        $jsonCartsList = $serializer->serialize($cartsList, 'json', ['groups' => 'public']);

        return new JsonResponse($jsonCartsList, Response::HTTP_OK, [], true);
    }

    #[Route("/api/cart/{id}", name:"detailCart", methods:"GET")]
    public function getDetailCart(Cart $cart, SerializerInterface $serializer): JsonResponse
    {
        // Check if status exist getDeleteAtand if delete
        if ($cart->getDeletedAt() !== null) {
            throw new BadRequestHttpException("This cart has been deleted and is no longer available.");
        }

        $jsonCart = $serializer->serialize($cart, 'json', ['groups' => 'public']);

        return new JsonResponse($jsonCart, Response::HTTP_OK, [], true);
    }

    #[Route("/api/cart/user/{id_user}", name:"detailCartsByUser", methods:"GET")]
    public function getDetailCartByUser(int $id_user, CartRepository $cartRepository, SerializerInterface $serializer): JsonResponse
    {
        $carts = $cartRepository->findBy(['user' => $id_user]);

        if (!$carts) {
            throw new NotFoundHttpException('No cart found for this user.');
        }

        $jsonCarts = $serializer->serialize($carts, 'json', ['groups' => 'public']);

        return new JsonResponse($jsonCarts, Response::HTTP_OK, [], true);
    }

    #[Route("/api/cart", name:"createCart", methods:"POST")]
    public function createCart(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $cartData = json_decode($request->getContent(), true);

        $userId = $cartData['user'];
        $productId = $cartData['product'];
        $quantity = $cartData['quantity'];

        $user = $em->getRepository(User::class)->find($userId);
        $product = $em->getRepository(Product::class)->find($productId);

        // Check if the user and the product exist
        if (!$user || !$product) {
            throw new BadRequestHttpException("User or product not found.");
        }

        // Vérifiez si un panier existe déjà pour cet utilisateur et ce produit
        $existingCart = $em->getRepository(Cart::class)->findOneBy(['user' => $userId, 'product' => $productId]);

        if ($existingCart) {
            throw new BadRequestHttpException('A cart already exists for this user and product.');
        }

        // Create a new Cart object
        $cart = new Cart();
        $cart->setUser($user);
        $cart->setProduct($product);
        $cart->setQuantity($quantity);

        $errors = $validator->validate($cart);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($user);
        $em->persist($product);
        $em->persist($cart);

        $em->flush();

        $jsonCart = $serializer->serialize($cart, 'json', ['groups' => 'public']);
        $location = $urlGenerator->generate('detailCart', ['id' => $cart->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonCart, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route("/api/cart/{id}", name:"updateCart", methods:"PUT")]
    public function updateCart(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, Cart $cart, EntityManagerInterface $em): JsonResponse
    {
        $cartData = json_decode($request->getContent(), true);
        $newQuantity = $cartData['quantity'];

        if (!is_numeric($newQuantity) || $newQuantity < 0) {
            throw new BadRequestHttpException('Invalid quantity.');
        }

        $quantityDifference = $newQuantity - $cart->getQuantity();

        $totalQuantity = $cart->getQuantity() + $quantityDifference;

        if ($totalQuantity > 100) {
            throw new BadRequestHttpException('Total quantity cannot exceed 100.');
        }

        if ($totalQuantity < 0) {
            throw new BadRequestHttpException('Total quantity cannot be negative.');
        }

        $cart->setQuantity($totalQuantity);

        $errors = $validator->validate($cart);
        if (count($errors) > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


    #[Route("/api/cart/{id}", name:"deleteCart", methods:"DELETE")]
    public function deleteCart(Cart $cart, CartRepository $cartRepository, EntityManagerInterface $em): JsonResponse
    {
        $cartRepository->remove($cart);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
