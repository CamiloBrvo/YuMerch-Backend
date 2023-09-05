<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 1 - CategoryFixtures
        // 2 - StatusFixtures
        // 3 - ProductFixtures
        // 4 - UserFixtures
        // 5 - CartFixtures
    }
}
