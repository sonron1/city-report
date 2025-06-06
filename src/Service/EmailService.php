<?php

namespace App\Service;

use App\Entity\Signalement;
use App\Entity\Utilisateur;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class EmailService
{
  public function __construct(
      private MailerInterface $mailer,
      private Environment $twig,
      private UrlGeneratorInterface $urlGenerator,
      private string $emailSender
  ) {}

  public function sendSignalementValidatedEmail(Signalement $signalement): void
  {
    $utilisateur = $signalement->getUtilisateur();

    if (!$utilisateur || !$utilisateur->getEmail()) {
      return;
    }

    $email = (new Email())
        ->from($this->emailSender)
        ->to($utilisateur->getEmail())
        ->subject('✅ Votre signalement a été validé')
        ->html($this->twig->render('emails/signalement_valide.html.twig', [
            'signalement' => $signalement,
            'utilisateur' => $utilisateur,
            'signalementUrl' => $this->generateSignalementUrl($signalement)
        ]));

    $this->mailer->send($email);
  }

  public function sendSignalementRejectedEmail(Signalement $signalement, string $commentaire = ''): void
  {
    $utilisateur = $signalement->getUtilisateur();

    if (!$utilisateur || !$utilisateur->getEmail()) {
      return;
    }

    $email = (new Email())
        ->from($this->emailSender)
        ->to($utilisateur->getEmail())
        ->subject('❌ Votre signalement a été rejeté')
        ->html($this->twig->render('emails/signalement_rejete.html.twig', [
            'signalement' => $signalement,
            'utilisateur' => $utilisateur,
            'commentaire' => $commentaire,
            'modifierUrl' => $this->generateSignalementModifierUrl($signalement)
        ]));

    $this->mailer->send($email);
  }

  public function sendSignalementStatusUpdateEmail(Signalement $signalement, string $ancienStatut, string $nouveauStatut): void
  {
    $utilisateur = $signalement->getUtilisateur();

    if (!$utilisateur || !$utilisateur->getEmail()) {
      return;
    }

    $email = (new Email())
        ->from($this->emailSender)
        ->to($utilisateur->getEmail())
        ->subject('🔄 Mise à jour de votre signalement')
        ->html($this->twig->render('emails/signalement_statut_update.html.twig', [
            'signalement' => $signalement,
            'utilisateur' => $utilisateur,
            'ancienStatut' => $ancienStatut,
            'nouveauStatut' => $nouveauStatut,
            'signalementUrl' => $this->generateSignalementUrl($signalement)
        ]));

    $this->mailer->send($email);
  }

  public function sendSignalementResolvedEmail(Signalement $signalement): void
  {
    $utilisateur = $signalement->getUtilisateur();

    if (!$utilisateur || !$utilisateur->getEmail()) {
      return;
    }

    $email = (new Email())
        ->from($this->emailSender)
        ->to($utilisateur->getEmail())
        ->subject('🎉 Votre signalement a été résolu')
        ->html($this->twig->render('emails/signalement_resolu.html.twig', [
            'signalement' => $signalement,
            'utilisateur' => $utilisateur,
            'signalementUrl' => $this->generateSignalementUrl($signalement)
        ]));

    $this->mailer->send($email);
  }

  public function sendSignalementCommentEmail(Signalement $signalement, string $commentaire, string $auteur): void
  {
    $utilisateur = $signalement->getUtilisateur();

    if (!$utilisateur || !$utilisateur->getEmail()) {
      return;
    }

    $email = (new Email())
        ->from($this->emailSender)
        ->to($utilisateur->getEmail())
        ->subject('💬 Nouveau commentaire sur votre signalement')
        ->html($this->twig->render('emails/signalement_commentaire.html.twig', [
            'signalement' => $signalement,
            'utilisateur' => $utilisateur,
            'commentaire' => $commentaire,
            'auteur' => $auteur,
            'signalementUrl' => $this->generateSignalementUrl($signalement)
        ]));

    $this->mailer->send($email);
  }

  public function sendWelcomeEmail(Utilisateur $utilisateur): void
  {
    if (!$utilisateur->getEmail()) {
      return;
    }

    $email = (new Email())
        ->from($this->emailSender)
        ->to($utilisateur->getEmail())
        ->subject('🎉 Bienvenue sur CityReport !')
        ->html($this->twig->render('emails/bienvenue.html.twig', [
            'utilisateur' => $utilisateur
        ]));

    $this->mailer->send($email);
  }

  public function sendAccountValidatedEmail(Utilisateur $utilisateur): void
  {
    if (!$utilisateur->getEmail()) {
      return;
    }

    $email = (new Email())
        ->from($this->emailSender)
        ->to($utilisateur->getEmail())
        ->subject('✅ Votre compte a été validé')
        ->html($this->twig->render('emails/compte_valide.html.twig', [
            'utilisateur' => $utilisateur
        ]));

    $this->mailer->send($email);
  }

  public function sendPasswordResetEmail(Utilisateur $utilisateur, string $token): void
  {
    if (!$utilisateur->getEmail()) {
      return;
    }

    $resetUrl = $this->urlGenerator->generate(
        'app_reset_password',
        ['token' => $token],
        UrlGeneratorInterface::ABSOLUTE_URL
    );

    $email = (new Email())
        ->from($this->emailSender)
        ->to($utilisateur->getEmail())
        ->subject('🔑 Réinitialisation de votre mot de passe')
        ->html($this->twig->render('emails/reset_password.html.twig', [
            'utilisateur' => $utilisateur,
            'resetUrl' => $resetUrl
        ]));

    $this->mailer->send($email);
  }

  public function sendSignalementDeletedEmail(Utilisateur $utilisateur, string $titreSignalement, string $motif): void
  {
    if (!$utilisateur->getEmail()) {
      return;
    }

    $email = (new Email())
        ->from($this->emailSender)
        ->to($utilisateur->getEmail())
        ->subject('🗑️ Signalement supprimé')
        ->html($this->twig->render('emails/signalement_supprime.html.twig', [
            'utilisateur' => $utilisateur,
            'titreSignalement' => $titreSignalement,
            'motif' => $motif
        ]));

    $this->mailer->send($email);
  }

  /**
   * Génère l'URL du signalement de manière sécurisée
   */
  private function generateSignalementUrl(Signalement $signalement): string
  {
    // Si le signalement n'a pas d'ID (test), utiliser une URL factice
    if (!$signalement->getId()) {
      return 'https://example.com/signalement/test';
    }

    return $this->urlGenerator->generate(
        'app_signalement_show',
        ['id' => $signalement->getId()],
        UrlGeneratorInterface::ABSOLUTE_URL
    );
  }

  /**
   * Génère l'URL de modification du signalement de manière sécurisée
   */
  private function generateSignalementModifierUrl(Signalement $signalement): string
  {
    // Si le signalement n'a pas d'ID (test), utiliser une URL factice
    if (!$signalement->getId()) {
      return 'https://example.com/signalement/test/modifier';
    }

    // Vérifier si la route existe
    try {
      return $this->urlGenerator->generate(
          'app_signalement_modifier',
          ['id' => $signalement->getId()],
          UrlGeneratorInterface::ABSOLUTE_URL
      );
    } catch (\Exception $e) {
      // Si la route n'existe pas, utiliser une URL factice
      return 'https://example.com/signalement/' . $signalement->getId() . '/modifier';
    }
  }
}