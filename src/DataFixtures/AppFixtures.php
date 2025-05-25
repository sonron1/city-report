<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use App\Entity\Signalement;
use App\Entity\Utilisateur;
use App\Entity\Ville;
use App\Enum\StatutSignalement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
  private $passwordHasher;

  public function __construct(UserPasswordHasherInterface $passwordHasher)
  {
    $this->passwordHasher = $passwordHasher;
  }

  public function load(ObjectManager $manager): void
  {
    // Création de villes
    $villes = [];
    $villesData = [
        ['nom' => 'Paris', 'latitude' => 48.8566, 'longitude' => 2.3522],
        ['nom' => 'Lyon', 'latitude' => 45.7640, 'longitude' => 4.8357],
        ['nom' => 'Marseille', 'latitude' => 43.2965, 'longitude' => 5.3698],
        ['nom' => 'Bordeaux', 'latitude' => 44.8378, 'longitude' => -0.5792],
        ['nom' => 'Lille', 'latitude' => 50.6292, 'longitude' => 3.0573],
    ];

    foreach ($villesData as $villeData) {
      $ville = new Ville();
      $ville->setNom($villeData['nom']);
      $ville->setLatitude($villeData['latitude']);
      $ville->setLongitude($villeData['longitude']);
      $manager->persist($ville);
      $villes[] = $ville;
    }

    // Création de catégories
    $categories = [];
    $categoriesData = [
        ['nom' => 'Voirie', 'description' => 'Problèmes liés à la chaussée, trottoirs, etc.'],
        ['nom' => 'Éclairage', 'description' => 'Problèmes liés à l\'éclairage public'],
        ['nom' => 'Propreté', 'description' => 'Problèmes de déchets, graffitis, etc.'],
        ['nom' => 'Espaces verts', 'description' => 'Problèmes dans les parcs et jardins'],
        ['nom' => 'Mobilier urbain', 'description' => 'Problèmes avec les bancs, poubelles, etc.'],
    ];

    foreach ($categoriesData as $categorieData) {
      $categorie = new Categorie();
      $categorie->setNom($categorieData['nom']);
      $categorie->setDescription($categorieData['description']);
      $manager->persist($categorie);
      $categories[] = $categorie;
    }

    // Création d'utilisateurs
    $utilisateurs = [];

    // Admin
    $admin = new Utilisateur();
    $admin->setEmail('admin@cityflow.fr');
    $admin->setNom('Admin');
    $admin->setPrenom('Super');
    $admin->setRoles(['ROLE_ADMIN']);
    $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
    $admin->setVilleResidence($villes[0]);
    $admin->setDateInscription(new \DateTime());
    $admin->setEstValide(true);
    $manager->persist($admin);
    $utilisateurs[] = $admin;

    // Modérateur
    $moderateur = new Utilisateur();
    $moderateur->setEmail('modo@cityflow.fr');
    $moderateur->setNom('Modo');
    $moderateur->setPrenom('Super');
    $moderateur->setRoles(['ROLE_MODERATOR']);
    $moderateur->setPassword($this->passwordHasher->hashPassword($moderateur, 'modo123'));
    $moderateur->setVilleResidence($villes[1]);
    $moderateur->setDateInscription(new \DateTime());
    $moderateur->setEstValide(true);
    $manager->persist($moderateur);
    $utilisateurs[] = $moderateur;

    // Utilisateurs normaux
    for ($i = 1; $i <= 5; $i++) {
      $utilisateur = new Utilisateur();
      $utilisateur->setEmail("user{$i}@cityflow.fr");
      $utilisateur->setNom("Nom{$i}");
      $utilisateur->setPrenom("Prénom{$i}");
      $utilisateur->setRoles(['ROLE_USER']);
      $utilisateur->setPassword($this->passwordHasher->hashPassword($utilisateur, 'user123'));
      $utilisateur->setVilleResidence($villes[$i % count($villes)]);
      $utilisateur->setDateInscription(new \DateTime());
      $utilisateur->setEstValide(true);
      $manager->persist($utilisateur);
      $utilisateurs[] = $utilisateur;
    }

    // Création de signalements
    $statuts = [
        StatutSignalement::NOUVEAU,
        StatutSignalement::EN_COURS,
        StatutSignalement::RESOLU,
        StatutSignalement::ANNULE
    ];

    $titres = [
        'Nid de poule dangereux',
        'Lampadaire en panne',
        'Dépôt sauvage de déchets',
        'Banc cassé dans le parc',
        'Graffiti sur mur public',
        'Arbre tombé sur la voie',
        'Fuite d\'eau sur la chaussée',
        'Panneau de signalisation abîmé',
        'Trottoir endommagé',
        'Passage piéton effacé'
    ];

    $descriptions = [
        'Un nid de poule profond est apparu après les dernières pluies, représentant un danger pour les cyclistes et motards.',
        'Le lampadaire ne fonctionne plus depuis plusieurs jours, rendant la rue dangereusement sombre la nuit.',
        'Des déchets ont été déposés illégalement au coin de la rue, créant une nuisance visuelle et olfactive.',
        'Le banc principal du parc est cassé, le rendant inutilisable et dangereux.',
        'Un large graffiti est apparu sur le mur de l\'école municipale, avec des propos inappropriés.',
        'Un arbre est tombé suite à la tempête d\'hier soir, bloquant partiellement la voie.',
        'Une fuite d\'eau importante sur la chaussée depuis ce matin, risque de verglas en cas de températures négatives.',
        'Le panneau de signalisation a été tordu, probablement suite à un choc, et n\'est plus lisible.',
        'Le trottoir présente une fissure importante qui s\'agrandit et représente un risque de chute pour les piétons.',
        'Les marquages du passage piéton sont presque entièrement effacés, créant un danger à cette intersection fréquentée.'
    ];

    // Créer 20 signalements
    for ($i = 0; $i < 20; $i++) {
      $signalement = new Signalement();
      $signalement->setTitre($titres[$i % count($titres)]);
      $signalement->setDescription($descriptions[$i % count($descriptions)]);
      $signalement->setPhotoUrl('default.jpg'); // Assurez-vous d'avoir cette image dans public/uploads/

      // Coordonnées aléatoires proches de la ville
      $ville = $villes[$i % count($villes)];
      $latOffset = (mt_rand(-100, 100) / 1000);
      $lngOffset = (mt_rand(-100, 100) / 1000);
      $signalement->setLatitude($ville->getLatitude() + $latOffset);
      $signalement->setLongitude($ville->getLongitude() + $lngOffset);

      $signalement->setDateSignalement(new \DateTime("- {$i} days"));
      $signalement->setStatut($statuts[$i % count($statuts)]);
      $signalement->setPriorite(($i % 3) + 1);
      $signalement->setEtatValidation('validé');
      $signalement->setUtilisateur($utilisateurs[$i % count($utilisateurs)]);
      $signalement->setCategorie($categories[$i % count($categories)]);
      $signalement->setVille($ville);

      $manager->persist($signalement);
    }

    $manager->flush();
  }
}