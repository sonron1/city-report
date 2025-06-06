<?php

namespace App\Controller;

use App\Entity\JournalValidation;
use App\Entity\Signalement;
use App\Enum\StatutSignalement;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/moderation')]
#[IsGranted('ROLE_MODERATOR')]
class ModerationController extends AbstractController
{
  #[Route('/signalement/{id}/valider', name: 'app_moderation_valider')]
  public function validerSignalement(
      Signalement $signalement,
      EntityManagerInterface $entityManager,
      Request $request,
      EmailService $emailService
  ): Response
  {
    if ($signalement->getEtatValidation() === 'valide') {
      $this->addFlash('info', 'Ce signalement est déjà validé.');
      return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
    }

    // Vérifier le token CSRF
    if (!$this->isCsrfTokenValid('validate' . $signalement->getId(), $request->request->get('_token'))) {
      $this->addFlash('error', 'Token de sécurité invalide.');
      return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
    }

    $commentaire = $request->request->get('commentaire', '');

    try {
      // Mettre à jour le statut
      $signalement->setEtatValidation('valide');

      // Créer une entrée dans le journal
      $journal = new JournalValidation();
      $journal->setSignalement($signalement);
      $journal->setModerateur($this->getUser());
      $journal->setDateValidation(new \DateTime());
      $journal->setAction('Validation');
      $journal->setCommentaire($commentaire);

      $entityManager->persist($journal);
      $entityManager->flush();

      // Envoyer l'email de validation
      try {
        $emailService->sendSignalementValidatedEmail($signalement);
      } catch (\Exception $e) {
        // Log l'erreur mais ne pas faire échouer la validation
        error_log('Erreur envoi email validation: ' . $e->getMessage());
      }

      $this->addFlash('success', 'Le signalement a été validé avec succès. Un email a été envoyé à l\'utilisateur.');

    } catch (\Exception $e) {
      $this->addFlash('error', 'Une erreur est survenue lors de la validation du signalement.');
      error_log('Erreur validation signalement: ' . $e->getMessage());
    }

    return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
  }

  #[Route('/signalement/{id}/rejeter', name: 'app_moderation_rejeter')]
  public function rejeterSignalement(
      Signalement $signalement,
      EntityManagerInterface $entityManager,
      Request $request,
      EmailService $emailService
  ): Response
  {
    if ($signalement->getEtatValidation() === 'rejete') {
      $this->addFlash('info', 'Ce signalement est déjà rejeté.');
      return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
    }

    // Vérifier le token CSRF
    if (!$this->isCsrfTokenValid('reject' . $signalement->getId(), $request->request->get('_token'))) {
      $this->addFlash('error', 'Token de sécurité invalide.');
      return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
    }

    $commentaire = $request->request->get('commentaire', '');

    if (empty(trim($commentaire))) {
      $this->addFlash('error', 'Un motif de rejet est obligatoire.');
      return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
    }

    try {
      // Mettre à jour le statut
      $signalement->setEtatValidation('rejete');

      // Créer une entrée dans le journal
      $journal = new JournalValidation();
      $journal->setSignalement($signalement);
      $journal->setModerateur($this->getUser());
      $journal->setDateValidation(new \DateTime());
      $journal->setAction('Rejet');
      $journal->setCommentaire($commentaire);

      $entityManager->persist($journal);
      $entityManager->flush();

      // Envoyer l'email de rejet
      try {
        $emailService->sendSignalementRejectedEmail($signalement, $commentaire);
      } catch (\Exception $e) {
        // Log l'erreur mais ne pas faire échouer la validation
        error_log('Erreur envoi email rejet: ' . $e->getMessage());
      }

      $this->addFlash('success', 'Le signalement a été rejeté avec succès. Un email a été envoyé à l\'utilisateur.');

    } catch (\Exception $e) {
      $this->addFlash('error', 'Une erreur est survenue lors du rejet du signalement.');
      error_log('Erreur rejet signalement: ' . $e->getMessage());
    }

    return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
  }

  #[Route('/signalement/{id}/modifier-statut', name: 'app_moderation_modifier_statut')]
  public function modifierStatutSignalement(
      Signalement $signalement,
      EntityManagerInterface $entityManager,
      Request $request
  ): Response
  {
    // Vérifier le token CSRF
    if (!$this->isCsrfTokenValid('status' . $signalement->getId(), $request->request->get('_token'))) {
      $this->addFlash('error', 'Token de sécurité invalide.');
      return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
    }

    $nouveauStatut = $request->request->get('statut');
    $commentaire = $request->request->get('commentaire', '');

    $statutsValides = array_map(fn($case) => $case->value, StatutSignalement::cases());

    if (!in_array($nouveauStatut, $statutsValides)) {
      $this->addFlash('error', 'Le statut fourni n\'est pas valide.');
      return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
    }

    try {
      $ancienStatut = $signalement->getStatut() ? $signalement->getStatut()->value : 'null';

      if ($ancienStatut === $nouveauStatut) {
        $this->addFlash('info', 'Le signalement a déjà ce statut.');
        return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
      }

      $signalement->setStatut(StatutSignalement::from($nouveauStatut));
      $entityManager->persist($signalement);

      // Créer une entrée dans le journal
      $journal = new JournalValidation();
      $journal->setSignalement($signalement);
      $journal->setModerateur($this->getUser());
      $journal->setDateValidation(new \DateTime());
      $journal->setAction('Modification statut');
      $journal->setCommentaire("Statut modifié de '{$ancienStatut}' vers '{$nouveauStatut}'. {$commentaire}");

      $entityManager->persist($journal);
      $entityManager->flush();

      $this->addFlash('success', "Le statut du signalement a été modifié avec succès.");

    } catch (\ValueError $e) {
      $this->addFlash('error', 'Erreur lors de la modification du statut : ' . $e->getMessage());
    } catch (\Exception $e) {
      $this->addFlash('error', 'Une erreur est survenue lors de la modification du statut.');
      error_log('Erreur modification statut: ' . $e->getMessage());
    }

    return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
  }

  #[Route('/signalement/{id}/supprimer', name: 'app_moderation_supprimer', methods: ['POST'])]
  #[IsGranted('ROLE_ADMIN')]  // Seuls les admins peuvent supprimer définitivement
  public function supprimerSignalement(
      Request $request,
      Signalement $signalement,
      EntityManagerInterface $entityManager
  ): Response
  {
    // Vérification du token CSRF
    if (!$this->isCsrfTokenValid('delete' . $signalement->getId(), $request->request->get('_token'))) {
      $this->addFlash('error', 'Token de sécurité invalide.');
      return $this->redirectToRoute('app_signalements');
    }

    try {
      // Vérifications de sécurité avant suppression
      $titre = $signalement->getTitre();
      $utilisateurNom = $signalement->getUtilisateur() ?
          $signalement->getUtilisateur()->getNom() . ' ' . $signalement->getUtilisateur()->getPrenom() :
          'Utilisateur inconnu';

      // Créer une entrée dans le journal avant suppression
      $journal = new JournalValidation();
      $journal->setSignalement($signalement);
      $journal->setModerateur($this->getUser());
      $journal->setDateValidation(new \DateTime());
      $journal->setAction('Suppression définitive');
      $journal->setCommentaire("Signalement supprimé par l'administrateur: {$titre} (Utilisateur: {$utilisateurNom})");

      $entityManager->persist($journal);
      $entityManager->flush(); // Sauvegarder le journal avant suppression

      // Supprimer le signalement
      $entityManager->remove($signalement);
      $entityManager->flush();

      $this->addFlash('success', "Le signalement \"{$titre}\" a été supprimé avec succès.");

    } catch (\Exception $e) {
      $this->addFlash('error', 'Une erreur est survenue lors de la suppression du signalement.');
      error_log('Erreur suppression signalement: ' . $e->getMessage());
    }

    return $this->redirectToRoute('app_signalements');
  }
}