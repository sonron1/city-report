<?php

namespace App\DataFixtures;

use App\Entity\Departement;
use App\Entity\Ville;
use App\Repository\DepartementRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class VilleFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private DepartementRepository $departementRepository;

    public function __construct(DepartementRepository $departementRepository) 
    {
        $this->departementRepository = $departementRepository;
    }
    
    public static function getGroups(): array
    {
        return ['villes', 'geo'];
    }

    public function load(ObjectManager $manager): void
    {
        // S'assurer que le département Littoral existe
        $departement = $this->getReference('departement_littoral', Departement::class) ?? null;
        
        if (!$departement) {
            // Ne pas continuer si le département n'existe pas
            throw new \Exception("Le département 'Littoral' doit être créé avant les villes");
        }
        
        // Liste des principales villes du Bénin avec leurs coordonnées
        $villes = [
            ['nom' => 'Cotonou', 'lat' => 6.3676953, 'lng' => 2.4252507, 'dep' => 'littoral'],
            ['nom' => 'Porto-Novo', 'lat' => 6.4968547, 'lng' => 2.6288523, 'dep' => 'oueme'],
            ['nom' => 'Abomey-Calavi', 'lat' => 6.4487302, 'lng' => 2.3539533, 'dep' => 'atlantique'],
            ['nom' => 'Parakou', 'lat' => 9.3399874, 'lng' => 2.6303063, 'dep' => 'borgou'],
            ['nom' => 'Djougou', 'lat' => 9.7085852, 'lng' => 1.668051, 'dep' => 'donga'],
            ['nom' => 'Bohicon', 'lat' => 7.1793552, 'lng' => 2.0662997, 'dep' => 'zou'],
            ['nom' => 'Natitingou', 'lat' => 10.3049625, 'lng' => 1.3796304, 'dep' => 'atacora'],
            ['nom' => 'Ouidah', 'lat' => 6.3675925, 'lng' => 2.0884388, 'dep' => 'atlantique'],
            ['nom' => 'Lokossa', 'lat' => 6.6376766, 'lng' => 1.7183985, 'dep' => 'mono'],
            ['nom' => 'Abomey', 'lat' => 7.1864771, 'lng' => 1.9913368, 'dep' => 'zou'],
            ['nom' => 'Savalou', 'lat' => 7.9283025, 'lng' => 1.9753223, 'dep' => 'collines'],
            ['nom' => 'Kandi', 'lat' => 11.1342686, 'lng' => 2.9405859, 'dep' => 'alibori'],
            ['nom' => 'Dassa-Zoumè', 'lat' => 7.7772885, 'lng' => 2.1872477, 'dep' => 'collines'],
            ['nom' => 'Comè', 'lat' => 6.4073055, 'lng' => 1.8827411, 'dep' => 'mono'],
            ['nom' => 'Pobè', 'lat' => 7.084946, 'lng' => 2.6676421, 'dep' => 'plateau'],
            ['nom' => 'Kérou', 'lat' => 10.8256486, 'lng' => 2.1061996, 'dep' => 'atacora'],
            ['nom' => 'Savè', 'lat' => 8.0359601, 'lng' => 2.486731, 'dep' => 'collines'],
            ['nom' => 'Sakété', 'lat' => 6.7370868, 'lng' => 2.6586804, 'dep' => 'plateau'],
            ['nom' => 'Malanville', 'lat' => 11.8665307, 'lng' => 3.3800321, 'dep' => 'alibori'],
            ['nom' => 'Bembéréké', 'lat' => 10.2293498, 'lng' => 2.6636248, 'dep' => 'borgou']
        ];

        foreach ($villes as $villeData) {
            $ville = new Ville();
            $ville->setNom($villeData['nom']);
            $ville->setLatitudeCentre($villeData['lat']);
            $ville->setLongitudeCentre($villeData['lng']);
            
            // Récupérer le département correspondant
            $departementRef = "departement_" . ($villeData['dep'] ?? 'littoral');
            $departement = $this->hasReference($departementRef, Departement::class) 
                ? $this->getReference($departementRef, Departement::class) 
                : $this->getReference('departement_littoral', Departement::class);
            
            $ville->setDepartement($departement);
            
            $manager->persist($ville);
            
            // Créer une référence pour utilisation ultérieure
            $this->addReference('ville_' . strtolower($villeData['nom']), $ville);
        }

        $manager->flush();
    }
    
    public function getDependencies(): array
    {
        return [
            DepartementFixtures::class,
        ];
    }
}