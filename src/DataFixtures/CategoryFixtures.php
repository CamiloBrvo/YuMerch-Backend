<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class CategoryFixtures extends Fixture implements OrderedFixtureInterface
{
    // Tableau contenant les catégories à créer
    public const TAB_CATEGORY = [
        'Men',
        'Women',
        'Jacket',
        'Pants',
        'Short',
    ];

    public function getOrder()
    {
        return 1;
    }

    public function load(ObjectManager $manager)
    {
        // Création et persistance des catégories
        foreach (self::TAB_CATEGORY as $category){
            $newCategory = new Category();
            $newCategory->setName($category);
            $manager->persist($newCategory);
        }

        // Flush pour appliquer les modifications en base de données
        $manager->flush();
    }
}
