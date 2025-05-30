<?php

namespace App\DataFixtures;

use App\Entity\Departement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class DepartementFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $departements = [
            ['nom' => 'Alibori', 'description' => 'Département du nord du Bénin'],
            ['nom' => 'Atacora', 'description' => 'Département du nord-ouest du Bénin'],
            ['nom' => 'Atlantique', 'description' => 'Département du sud du Bénin'],
            ['nom' => 'Borgou', 'description' => 'Département du nord-est du Bénin'],
            ['nom' => 'Collines', 'description' => 'Département du centre du Bénin'],
            ['nom' => 'Couffo', 'description' => 'Département du sud-ouest du Bénin'],
            ['nom' => 'Donga', 'description' => 'Département du nord-ouest du Bénin'],
            ['nom' => 'Littoral', 'description' => 'Département du sud du Bénin, comprenant Cotonou'],
            ['nom' => 'Mono', 'description' => 'Département du sud-ouest du Bénin'],
            ['nom' => 'Ouémé', 'description' => 'Département du sud-est du Bénin'],
            ['nom' => 'Plateau', 'description' => 'Département du sud-est du Bénin'],
            ['nom' => 'Zou', 'description' => 'Département du centre du Bénin'],
        ];

        foreach ($departements as $data) {
            $departement = new Departement();
            $departement->setNom($data['nom']);
            $departement->setDescription($data['description']);
            
            $manager->persist($departement);
            
            // Créer une référence pour utilisation dans d'autres fixtures
            $this->addReference('departement_'.$this->slugify($data['nom']), $departement);
        }

        $manager->flush();
    }
    
    public static function getGroups(): array
    {
        return ['departements', 'geo'];
    }
    
    private function slugify(string $text): string
    {
        // Remplacer les caractères non alphanumériques par un tiret
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // Translitérer
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // Supprimer les caractères indésirables
        $text = preg_replace('~[^-\w]+~', '', $text);
        // Trimmer
        $text = trim($text, '-');
        // Supprimer les tirets dupliqués
        $text = preg_replace('~-+~', '-', $text);
        // Mettre en minuscules
        $text = strtolower($text);

        return $text;
    }
}