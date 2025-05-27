<?php

namespace App\DataFixtures;

use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class VilleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Liste des principales villes du Bénin avec leurs coordonnées
        $villes = [
            ['nom' => 'Cotonou', 'lat' => 6.3676953, 'lng' => 2.4252507],
            ['nom' => 'Porto-Novo', 'lat' => 6.4968547, 'lng' => 2.6288523],
            ['nom' => 'Abomey-Calavi', 'lat' => 6.4487302, 'lng' => 2.3539533],
            ['nom' => 'Parakou', 'lat' => 9.3399874, 'lng' => 2.6303063],
            ['nom' => 'Djougou', 'lat' => 9.7085852, 'lng' => 1.668051],
            ['nom' => 'Bohicon', 'lat' => 7.1793552, 'lng' => 2.0662997],
            ['nom' => 'Natitingou', 'lat' => 10.3049625, 'lng' => 1.3796304],
            ['nom' => 'Ouidah', 'lat' => 6.3675925, 'lng' => 2.0884388],
            ['nom' => 'Lokossa', 'lat' => 6.6376766, 'lng' => 1.7183985],
            ['nom' => 'Abomey', 'lat' => 7.1864771, 'lng' => 1.9913368],
            ['nom' => 'Savalou', 'lat' => 7.9283025, 'lng' => 1.9753223],
            ['nom' => 'Kandi', 'lat' => 11.1342686, 'lng' => 2.9405859],
            ['nom' => 'Dassa-Zoumè', 'lat' => 7.7772885, 'lng' => 2.1872477],
            ['nom' => 'Comè', 'lat' => 6.4073055, 'lng' => 1.8827411],
            ['nom' => 'Pobè', 'lat' => 7.084946, 'lng' => 2.6676421],
            ['nom' => 'Kérou', 'lat' => 10.8256486, 'lng' => 2.1061996],
            ['nom' => 'Savè', 'lat' => 8.0359601, 'lng' => 2.486731],
            ['nom' => 'Sakété', 'lat' => 6.7370868, 'lng' => 2.6586804],
            ['nom' => 'Malanville', 'lat' => 11.8665307, 'lng' => 3.3800321],
            ['nom' => 'Bembéréké', 'lat' => 10.2293498, 'lng' => 2.6636248]
        ];

        foreach ($villes as $villeData) {
            $ville = new Ville();
            $ville->setNom($villeData['nom']);
            // Utiliser les méthodes correctes
            $ville->setLatitudeCentre($villeData['lat']);
            $ville->setLongitudeCentre($villeData['lng']);
            
            $manager->persist($ville);
        }

        $manager->flush();
    }
}