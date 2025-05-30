<?php

namespace App\DataFixtures;

use App\Entity\Arrondissement;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ArrondissementFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $arrondissementsParVille = [
            'cotonou' => [
                'Akpakpa', 'Cadjehoun', 'Fidjrossè', 'Gbégamey', 'Houéyiho', 'Agla', 
                'Jéricho', 'Midombo', 'Tokplégbé', 'Vossa', 'Xwlacodji', 'Zongo'
            ],
            'porto-novo' => [
                'Adjarra', 'Aguégués', 'Avrankou', 'Akpro-Missérété', 'Dangbo'
            ],
            'parakou' => [
                'Titirou', 'Kpébié', 'Ganhi', 'Albarika', 'Zongo'
            ],
            'abomey-calavi' => [
                'Godomey', 'Zinvié', 'Kpanroun', 'Togba', 'Hêvié'
            ],
            'djougou' => [
                'Kolokondé', 'Patargo', 'Sérou', 'Baréi', 'Bariénou'
            ],
            'bohicon' => [
                'Agongointo', 'Avogbanna', 'Lissèzoun', 'Saclo', 'Sodohomè'
            ],
            'abomey' => [
                'Agbokpa', 'Djègbé', 'Hounli', 'Vidolé', 'Zounzounmè'
            ],
        ];

        foreach ($arrondissementsParVille as $villeSlug => $arrondissements) {
            // Vérifier si la référence de ville existe
            if (!$this->hasReference('ville_' . $villeSlug, Ville::class)) {
                continue;
            }
            
            $ville = $this->getReference('ville_' . $villeSlug, Ville::class);
            
            foreach ($arrondissements as $nomArrondissement) {
                $arrondissement = new Arrondissement();
                $arrondissement->setNom($nomArrondissement);
                $arrondissement->setDescription("Arrondissement de " . $ville->getNom());
                $arrondissement->setVille($ville);
                
                $manager->persist($arrondissement);
                
                // Ajouter une référence pour utilisation future
                $this->addReference(
                    'arrondissement_' . $this->slugify($ville->getNom() . '-' . $nomArrondissement),
                    $arrondissement
                );
            }
        }
        
        $manager->flush();
    }
    
    public static function getGroups(): array
    {
        return ['arrondissements', 'geo'];
    }
    
    public function getDependencies(): array
    {
        return [
            VilleFixtures::class,
        ];
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