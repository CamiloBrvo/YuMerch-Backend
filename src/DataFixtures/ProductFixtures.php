<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Status;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class ProductFixtures extends Fixture implements OrderedFixtureInterface
{
    // Tableau contenant les title des products à créer
    public const TAB_TITLE = [
        "Mens Casual Premium Slim Fit T-Shirts",
        "Womens Casual Premium Slim Fit T-Shirts",
        "Men Jacket",
        "Womens Jacket",
        "Womens Pants",
        "Womens Short"
    ];

    // Tableau contenant les description des products à créer
    public const TAB_DESCRIPTION = [
        "Slim-fitting style, contrast raglan long sleeve, three-button henley placket, light weight & soft fabric for breathable and comfortable wearing. And Solid stitched shirts with round neck made for durability and a great fit for casual fashion wear and diehard baseball fans. The Henley style round neckline includes a three-button placket.",
        "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum vel velit euismod, aliquam metus in, sodales turpis.",
        "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum vel velit euismod, aliquam metus in, sodales turpis.",
        "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum vel velit euismod, aliquam metus in, sodales turpis.",
        "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum vel velit euismod, aliquam metus in, sodales turpis.",
        "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum vel velit euismod, aliquam metus in, sodales turpis."
    ];

    // Tableau contenant les images des products à créer
    public const TAB_IMAGE = [
        "https://fakestoreapi.com/img/71-3HjGNDUL._AC_SY879._SX._UX._SY._UY_.jpg",
        "https://fakestoreapi.com/img/71li-ujtlUL._AC_UX679_.jpg",
        "https://fakestoreapi.com/img/71YXzeOuslL._AC_UY879_.jpg",
        "https://fakestoreapi.com/img/61sbMiUnoGL._AC_UL640_QL65_ML3_.jpg",
        "https://fakestoreapi.com/img/71YAIFU48IL._AC_UL640_QL65_ML3_.jpg",
        "https://fakestoreapi.com/img/61IBBVJvSDL._AC_SY879_.jpg",
    ];

    public function getOrder()
    {
        return 3;
    }

    public function load(ObjectManager $manager)
    {
        // Récupération des Catégories
        $categoryRep = $manager->getRepository(Category::class);
        $categorysList = $categoryRep->findAll();

        // Récupération des Status
        $statusRep = $manager->getRepository(Status::class);
        $statusList = $statusRep->findAll();

        // Créer les products
        for ($i = 0; $i <= 5; $i++) {
            $product = new Product();
            $product->setTitle(self::TAB_TITLE[$i])
                ->setPrice(rand(10, 100))
                ->setDescription(self::TAB_DESCRIPTION[$i])
                ->addCategory($categorysList[rand(0, count($categorysList) - 1)])
                ->setImage(self::TAB_IMAGE[$i])
                ->setStatus($statusList[rand(0, count($statusList) - 1)]);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
