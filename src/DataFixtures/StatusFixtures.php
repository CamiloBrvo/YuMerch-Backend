<?php

namespace App\DataFixtures;

use App\Entity\Status;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class StatusFixtures extends Fixture implements OrderedFixtureInterface
{
    // Tableau contenant les catégories à créer
    public const TAB_STATUS = [
        'Online',
        'Offline',
        'Waiting',
    ];

    public function getOrder()
    {
        return 2;
    }

    public function load(ObjectManager $manager)
    {
        // Création et persistance des catégories
        foreach (self::TAB_STATUS as $status){
            $newStatus = new Status();
            $newStatus->setName($status);
            $manager->persist($newStatus);
        }

        // Flush pour appliquer les modifications en base de données
        $manager->flush();
    }
}
