<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;

class UserFixtures extends Fixture implements OrderedFixtureInterface
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function getOrder()
    {
        return 4;
    }

    public function load(ObjectManager $manager)
    {

        // Implémentation de Faker
        $faker = Faker\Factory::create('fr_FR');

        // Récupération des Roles
        $productRep = $manager->getRepository(Product::class);
        $products = $productRep->findAll();

        // Création et persistance des users
        for ($i = 0; $i <= 2; $i++) {
            $user = new User();
            $user->setFirstName($faker->firstName)
                ->setLastName($faker->lastName)
                ->setEmail($faker->email)
                ->setPassword("password")
                ->setPassword($this->userPasswordHasher->hashPassword($user, "password"))
                ->setRoles(['ROLE_USER'])
                ->addLike($products[rand(0, count($products) -1)]);
            $manager->persist($user);
        }

        // Flush pour appliquer les modifications en base de données
        $manager->flush();
    }
}
