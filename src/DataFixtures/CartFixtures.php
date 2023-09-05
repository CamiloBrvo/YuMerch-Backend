<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\Cart;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class CartFixtures extends Fixture implements OrderedFixtureInterface
{
    public function getOrder()
    {
        return 5;
    }

    public function load(ObjectManager $manager)
    {

        // Récupération des Products
        $productRep = $manager->getRepository(Product::class);
        $productList = $productRep->findAll();

        // Récupération des User
        $userRep = $manager->getRepository(User::class);
        $userList = $userRep->findAll();

        for ($i = 0; $i <= count($productList) -1; $i++) {
            $cart = new Cart();
            $cart->setProduct($productList[$i])
                ->setUser($userList[rand(0, count($userList) - 1)])
                ->setQuantity(rand(0, 10));
            $manager->persist($cart);
        }

        // Flush pour appliquer les modifications en base de données
        $manager->flush();
    }
}
