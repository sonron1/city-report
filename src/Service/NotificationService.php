<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\Signalement;
use App\Entity\Utilisateur;
use App\Enum\EtatValidation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NotificationService
{
  public function __construct(
      private EmailService $emailService,
      private EntityManagerInterface $entityManager,
      private UrlGeneratorInterface $urlGenerator
  ) {}

  /**
   * Envoie une notification de validation de signalement
   */
  public function notifySignalementValidated(Signalement $signalement, string $commentaire = ''): void
  {
    $utilisateur = $signalement->getUtilisateur();

    if (!$utilisateur) {
      return;
    }

    // 1. Envoyer l'email
    $this->emailService->sendSignalementValidatedEmail($signalement);

    // 2. Créer un message interne
    $this->createInternalMessage(
        $utilisateur,
        "✅ Signalement validé : {$signalement->getTitre()}",
        $this->generateValidatedMessageContent($signalement, $commentaire),
        $signalement
    );
  }

  /**
   * Envoie une notification de rejet de signalement
   */
  public function notifySignalementRejected(Signalement $signalement, string $commentaire = ''): void
  {
    $utilisateur = $signalement->getUtilisateur();

    if (!$utilisateur) {
      return;
    }

    // 1. Envoyer l'email
    $this->emailService->sendSignalementRejectedEmail($signalement, $commentaire);

    // 2. Créer un message interne
    $this->createInternalMessage(
        $utilisateur,
        "❌ Signalement rejeté : {$signalement->getTitre()}",
        $this->generateRejectedMessageContent($signalement, $commentaire),
        $signalement
    );
  }

  /**
   * Envoie une notification de changement de statut
   */
  public function notifySignalementStatusChange(Signalement $signalement, string $ancienStatut, string $nouveauStatut): void
  {
    $utilisateur = $signalement->getUtilisateur();

    if (!$utilisateur) {
      return;
    }

    // 1. Envoyer l'email
    $this->emailService->sendSignalementStatusUpdateEmail($signalement, $ancienStatut, $nouveauStatut);

    // 2. Créer un message interne
    $this->createInternalMessage(
        $utilisateur,
        "🔄 Mise à jour du statut : {$signalement->getTitre()}",
        $this->generateStatusChangeMessageContent($signalement, $ancienStatut, $nouveauStatut),
        $signalement
    );
  }

  /**
   * Crée un message interne dans la messagerie de l'utilisateur
   */
  private function createInternalMessage(
      Utilisateur $destinataire,
      string $sujet,
      string $contenu,
      Signalement $signalement = null
  ): void {
    // Créer un utilisateur système comme expéditeur
    $expediteurSysteme = $this->getSystemUser();

    $message = new Message();
    $message->setExpediteur($expediteurSysteme);
    $message->setDestinataire($destinataire);
    $message->setSujet($sujet);
    $message->setContenu($contenu);
    $message->setSignalementConcerne($signalement);
    $message->setDateEnvoi(new \DateTime());

    $this->entityManager->persist($message);
    $this->entityManager->flush();
  }

  /**
   * Génère le contenu du message pour une validation
   */
  private function generateValidatedMessageContent(Signalement $signalement, string $commentaire): string
  {
    $content = "Bonjour {$signalement->getUtilisateur()->getPrenom()},\n\n";
    $content .= "Bonne nouvelle ! Votre signalement \"{$signalement->getTitre()}\" a été validé par notre équipe de modération.\n\n";

    if ($commentaire) {
      $content .= "Commentaire du modérateur :\n{$commentaire}\n\n";
    }

    $content .= "Votre signalement est maintenant visible publiquement et sera traité par les services compétents.\n\n";
    $content .= "Vous pouvez consulter votre signalement en cliquant sur ce lien :\n";
    $content .= $this->generateSignalementUrl($signalement) . "\n\n";
    $content .= "Merci pour votre contribution à l'amélioration de notre ville !\n\n";
    $content .= "L'équipe CityReport";

    return $content;
  }

  /**
   * Génère le contenu du message pour un rejet
   */
  private function generateRejectedMessageContent(Signalement $signalement, string $commentaire): string
  {
    $content = "Bonjour {$signalement->getUtilisateur()->getPrenom()},\n\n";
    $content .= "Nous vous informons que votre signalement \"{$signalement->getTitre()}\" a été rejeté par notre équipe de modération.\n\n";

    if ($commentaire) {
      $content .= "Motif du rejet :\n{$commentaire}\n\n";
    }

    $content .= "Vous pouvez modifier votre signalement en tenant compte de ces remarques et le soumettre à nouveau.\n\n";
    $content .= "Pour modifier votre signalement, cliquez sur ce lien :\n";
    $content .= $this->generateSignalementModifierUrl($signalement) . "\n\n";
    $content .= "Si vous avez des questions, n'hésitez pas à nous contacter.\n\n";
    $content .= "L'équipe CityReport";

    return $content;
  }

  /**
   * Génère le contenu du message pour un changement de statut
   */
  private function generateStatusChangeMessageContent(Signalement $signalement, string $ancienStatut, string $nouveauStatut): string
  {
    $content = "Bonjour {$signalement->getUtilisateur()->getPrenom()},\n\n";
    $content .= "Le statut de votre signalement \"{$signalement->getTitre()}\" a été mis à jour.\n\n";
    $content .= "Ancien statut : {$ancienStatut}\n";
    $content .= "Nouveau statut : {$nouveauStatut}\n\n";
    $content .= "Vous pouvez consulter votre signalement en cliquant sur ce lien :\n";
    $content .= $this->generateSignalementUrl($signalement) . "\n\n";
    $content .= "L'équipe CityReport";

    return $content;
  }

  /**
   * Génère l'URL du signalement
   */
  private function generateSignalementUrl(Signalement $signalement): string
  {
    if (!$signalement->getId()) {
      return '#';
    }

    return $this->urlGenerator->generate(
        'app_signalement_show',
        ['id' => $signalement->getId()],
        UrlGeneratorInterface::ABSOLUTE_URL
    );
  }

  /**
   * Génère l'URL de modification du signalement
   */
  private function generateSignalementModifierUrl(Signalement $signalement): string
  {
    if (!$signalement->getId()) {
      return '#';
    }

    return $this->urlGenerator->generate(
        'app_signalement_modifier',
        ['id' => $signalement->getId()],
        UrlGeneratorInterface::ABSOLUTE_URL
    );
  }

  /**
   * Récupère ou crée l'utilisateur système
   */
  private function getSystemUser(): Utilisateur
  {
    $systemUser = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => 'system@cityreport.com']);

    if (!$systemUser) {
      $systemUser = new Utilisateur();
      $systemUser->setEmail('system@cityreport.com');
      $systemUser->setPrenom('Système');
      $systemUser->setNom('CityReport');
      $systemUser->setPassword(''); // Pas de mot de passe pour l'utilisateur système
      $systemUser->setRoles(['ROLE_SYSTEM']);
      $systemUser->setEstValide(true);

      $this->entityManager->persist($systemUser);
      $this->entityManager->flush();
    }

    return $systemUser;
  }
}