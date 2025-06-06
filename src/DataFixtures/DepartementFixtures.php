<?php

namespace App\DataFixtures;

use App\Entity\Departement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class DepartementFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['departements', 'geo'];
    }

    public function load(ObjectManager $manager): void
    {
        $departements = [
            ['nom' => 'Alibori', 'description' => 'Département du nord du Bénin, chef-lieu: Kandi'],
            ['nom' => 'Atacora', 'description' => 'Département du nord-ouest du Bénin, chef-lieu: Natitingou'],
            ['nom' => 'Atlantique', 'description' => 'Département du sud du Bénin, chef-lieu: Allada'],
            ['nom' => 'Borgou', 'description' => 'Département du nord-est du Bénin, chef-lieu: Parakou'],
            ['nom' => 'Collines', 'description' => 'Département du centre du Bénin, chef-lieu: Dassa-Zoumè'],
            ['nom' => 'Couffo', 'description' => 'Département du sud-ouest du Bénin, chef-lieu: Aplahoué'],
            ['nom' => 'Donga', 'description' => 'Département du nord-ouest du Bénin, chef-lieu: Djougou'],
            ['nom' => 'Littoral', 'description' => 'Département du sud du Bénin, composé uniquement de la ville de Cotonou'],
            ['nom' => 'Mono', 'description' => 'Département du sud-ouest du Bénin, chef-lieu: Lokossa'],
            ['nom' => 'Ouémé', 'description' => 'Département du sud-est du Bénin, chef-lieu: Porto-Novo'],
            ['nom' => 'Plateau', 'description' => 'Département du sud-est du Bénin, chef-lieu: Pobè'],
            ['nom' => 'Zou', 'description' => 'Département du centre-sud du Bénin, chef-lieu: Abomey']
        ];

        foreach ($departements as $data) {
            $departement = new Departement();
            $departement->setNom($data['nom']);
            $departement->setDescription($data['description']);
            
            $manager->persist($departement);
            
            // Ajouter une référence pour utilisation future
            $this->addReference('departement_' . $this->slugify($data['nom']), $departement);
        }

        $manager->flush();
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