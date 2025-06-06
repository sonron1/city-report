<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Entity\Utilisateur;
use App\Entity\Ville;
use App\Entity\Categorie;
use App\Entity\Departement;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-email',
    description: 'Test l\'envoi d\'emails de notification'
)]
class TestEmailCommand extends Command
{
  public function __construct(
      private EmailService $emailService,
      private EntityManagerInterface $entityManager
  ) {
    parent::__construct();
  }

  protected function configure(): void
  {
    $this
        ->addArgument('type', InputArgument::REQUIRED, 'Type d\'email à tester (validation, rejet, bienvenue, etc.)')
        ->addArgument('email', InputArgument::OPTIONAL, 'Email de test (optionnel)')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);
    $type = $input->getArgument('type');
    $testEmail = $input->getArgument('email') ?: 'test@example.com';

    try {
      switch ($type) {
        case 'validation':
          $signalement = $this->createTestSignalement($testEmail);
          $this->emailService->sendSignalementValidatedEmail($signalement);
          $io->success('Email de validation envoyé !');
          break;

        case 'rejet':
          $signalement = $this->createTestSignalement($testEmail);
          $this->emailService->sendSignalementRejectedEmail($signalement, 'Informations insuffisantes pour traiter le signalement.');
          $io->success('Email de rejet envoyé !');
          break;

        case 'bienvenue':
          $utilisateur = $this->createTestUtilisateur($testEmail);
          $this->emailService->sendWelcomeEmail($utilisateur);
          $io->success('Email de bienvenue envoyé !');
          break;

        case 'resolu':
          $signalement = $this->createTestSignalement($testEmail);
          $this->emailService->sendSignalementResolvedEmail($signalement);
          $io->success('Email de résolution envoyé !');
          break;

        case 'commentaire':
          $signalement = $this->createTestSignalement($testEmail);
          $this->emailService->sendSignalementCommentEmail($signalement, 'Nous travaillons actuellement sur votre signalement.', 'Équipe technique');
          $io->success('Email de commentaire envoyé !');
          break;

        case 'statut':
          $signalement = $this->createTestSignalement($testEmail);
          $this->emailService->sendSignalementStatusUpdateEmail($signalement, 'en_attente', 'en_cours');
          $io->success('Email de changement de statut envoyé !');
          break;

        case 'compte-valide':
          $utilisateur = $this->createTestUtilisateur($testEmail);
          $this->emailService->sendAccountValidatedEmail($utilisateur);
          $io->success('Email de validation de compte envoyé !');
          break;

        case 'reset-password':
          $utilisateur = $this->createTestUtilisateur($testEmail);
          $this->emailService->sendPasswordResetEmail($utilisateur, 'token-test-123456');
          $io->success('Email de réinitialisation envoyé !');
          break;

        case 'supprime':
          $utilisateur = $this->createTestUtilisateur($testEmail);
          $this->emailService->sendSignalementDeletedEmail($utilisateur, 'Nid de poule sur la route principale', 'Contenu inapproprié');
          $io->success('Email de suppression envoyé !');
          break;

        default:
          $io->error('Type d\'email non reconnu. Types disponibles : validation, rejet, bienvenue, resolu, commentaire, statut, compte-valide, reset-password, supprime');
          return Command::FAILURE;
      }

      $io->note("Email envoyé à : $testEmail");
      $io->note('Vérifiez votre boîte Mailtrap : https://mailtrap.io/inboxes');

    } catch (\Exception $e) {
      $io->error('Erreur lors de l\'envoi : ' . $e->getMessage());
      return Command::FAILURE;
    }

    return Command::SUCCESS;
  }

  private function createTestUtilisateur(string $testEmail): Utilisateur
  {
    // Récupérer ou créer les entités nécessaires
    $ville = $this->getOrCreateTestVille();

    // Créer l'utilisateur de test
    $utilisateur = new Utilisateur();
    $utilisateur->setEmail($testEmail);
    $utilisateur->setPrenom('Jean');
    $utilisateur->setNom('Dupont');
    $utilisateur->setPassword('test'); // Password sera hashé normalement
    $utilisateur->setVilleResidence($ville);
    $utilisateur->setDateInscription(new \DateTime());
    $utilisateur->setEstValide(true);

    return $utilisateur;
  }

  private function createTestSignalement(string $testEmail): Signalement
  {
    // Créer l'utilisateur avec tous les champs requis
    $utilisateur = $this->createTestUtilisateur($testEmail);

    // Récupérer ou créer les entités nécessaires
    $ville = $this->getOrCreateTestVille();
    $categorie = $this->getOrCreateTestCategorie();

    // Créer le signalement de test
    $signalement = new Signalement();
    $signalement->setTitre('Test - Nid de poule sur la route principale');
    $signalement->setDescription('Signalement de test pour vérifier les emails. Ce signalement sera supprimé automatiquement.');
    $signalement->setLatitude(48.8566);  // Coordonnées de Paris
    $signalement->setLongitude(2.3522);
    $signalement->setUtilisateur($utilisateur);
    $signalement->setVille($ville);
    $signalement->setCategorie($categorie);
    $signalement->setDateSignalement(new \DateTime());
    $signalement->setEtatValidation('en_attente');
    $signalement->setStatut(\App\Enum\StatutSignalement::NOUVEAU);
    $signalement->setPriorite(1);
    $signalement->setPhotoUrl('test-image.jpg');

    // Persister temporairement le signalement pour avoir un ID
    $this->entityManager->persist($utilisateur);
    $this->entityManager->persist($signalement);
    $this->entityManager->flush();

    return $signalement;
  }

  private function getOrCreateTestDepartement(): Departement
  {
    $departement = $this->entityManager->getRepository(Departement::class)
        ->findOneBy(['nom' => 'Test Département']);

    if (!$departement) {
      $departement = new Departement();
      $departement->setNom('Test Département');
      $departement->setDescription('Département de test pour les emails');
      $departement->setPays('Bénin');
      $this->entityManager->persist($departement);
      $this->entityManager->flush(); // Flush pour avoir l'ID
    }

    return $departement;
  }

  private function getOrCreateTestVille(): Ville
  {
    $ville = $this->entityManager->getRepository(Ville::class)
        ->findOneBy(['nom' => 'Test Ville']);

    if (!$ville) {
      $departement = $this->getOrCreateTestDepartement();

      $ville = new Ville();
      $ville->setNom('Test Ville');
      $ville->setDepartement($departement);
      $ville->setLatitudeCentre(48.8566);
      $ville->setLongitudeCentre(2.3522);
      $this->entityManager->persist($ville);
      $this->entityManager->flush(); // Flush pour avoir l'ID
    }

    return $ville;
  }

  private function getOrCreateTestCategorie(): Categorie
  {
    $categorie = $this->entityManager->getRepository(Categorie::class)
        ->findOneBy(['nom' => 'Test Catégorie']);

    if (!$categorie) {
      $categorie = new Categorie();
      $categorie->setNom('Test Catégorie');
      $categorie->setDescription('Catégorie de test pour les emails');
      $categorie->setIcone('fas fa-tools');
      $categorie->setCouleur('#007bff');
      $this->entityManager->persist($categorie);
      $this->entityManager->flush(); // Flush pour avoir l'ID
    }

    return $categorie;
  }
}