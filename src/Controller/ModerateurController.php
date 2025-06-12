<?php

namespace App\Controller;

use App\Entity\JournalValidation;
use App\Entity\Signalement;
use App\Enum\StatutSignalement;
use App\Service\EmailService;
use App\Repository\SignalementRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\JournalValidationRepository;
use App\Repository\CategorieRepository;
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
  // =====================
  // DASHBOARD MODÉRATEUR
  // =====================

  #[Route('/', name: 'app_moderation_dashboard')]
  public function dashboard(
      SignalementRepository $signalementRepository,
      JournalValidationRepository $journalRepository,
      UtilisateurRepository $utilisateurRepository,
      CategorieRepository $categorieRepository
  ): Response {
    $moderateur = $this->getUser();

    // 📊 Statistiques générales
    $stats = [
        'en_attente' => $signalementRepository->count(['etatValidation' => 'en_attente']),
        'en_cours' => $signalementRepository->count(['statut' => StatutSignalement::EN_COURS]),
        'resolus_today' => $this->getSignalementsResolusAujourdhui($signalementRepository),
        'mes_validations' => $this->getMesValidationsAujourdhui($journalRepository, $moderateur),
        'total_signalements' => $signalementRepository->count([]),
        'taux_resolution' => $this->getTauxResolution($signalementRepository)
    ];

    // 🔥 Signalements prioritaires (en attente)
    $signalements_prioritaires = $signalementRepository->findBy(
        ['etatValidation' => 'en_attente'],
        ['dateSignalement' => 'ASC'], // Les plus anciens en premier
        6
    );

    // 📈 Mes activités récentes
    $mes_activites = $journalRepository->createQueryBuilder('j')
        ->where('j.moderation = :moderateur')
        ->setParameter('moderateur', $moderateur)
        ->orderBy('j.dateValidation', 'DESC')
        ->setMaxResults(8)
        ->getQuery()
        ->getResult();

    // 🎯 Performance du modérateur
    $ma_performance = $this->getMaPerformance($journalRepository, $moderateur);

    // ⚠️ Alertes importantes
    $alertes = $this->getAlertes($signalementRepository);

    // 📋 Signalements récents traités
    $signalements_recents = $signalementRepository->createQueryBuilder('s')
        ->where('s.etatValidation IN (:etats)')
        ->setParameter('etats', ['valide', 'rejete'])
        ->orderBy('s.dateSignalement', 'DESC')
        ->setMaxResults(5)
        ->getQuery()
        ->getResult();

    return $this->render('moderation/index.html.twig', [
        'stats' => $stats,
        'signalements_prioritaires' => $signalements_prioritaires,
        'mes_activites' => $mes_activites,
        'ma_performance' => $ma_performance,
        'alertes' => $alertes,
        'signalements_recents' => $signalements_recents
    ]);
  }

  #[Route('/signalements', name: 'app_moderation_signalements')]
  public function listSignalements(
      SignalementRepository $signalementRepository,
      Request $request
  ): Response {
    $filter = $request->query->get('filter', 'new');

    $signalements = match($filter) {
      'new' => $signalementRepository->findBy(['etatValidation' => 'en_attente'], ['dateSignalement' => 'DESC']),
      'urgent' => $this->getSignalementsUrgents($signalementRepository),
      'in_progress' => $signalementRepository->findBy(['statut' => StatutSignalement::EN_COURS], ['dateSignalement' => 'DESC']),
      'all' => $signalementRepository->findBy([], ['dateSignalement' => 'DESC'], 50),
      default => $signalementRepository->findBy(['etatValidation' => 'en_attente'], ['dateSignalement' => 'DESC'])
    };

    $stats_rapides = [
        'en_attente' => $signalementRepository->count(['etatValidation' => 'en_attente']),
        'en_cours' => $signalementRepository->count(['statut' => StatutSignalement::EN_COURS]),
        'resolus' => $signalementRepository->count(['statut' => StatutSignalement::RESOLU])
    ];

    return $this->render('moderation/signalements/index.html.twig', [
        'signalements' => $signalements,
        'current_filter' => $filter,
        'stats' => $stats_rapides
    ]);
  }

  #[Route('/mon-activite', name: 'app_moderation_mon_activite')]
  public function monActivite(JournalValidationRepository $journalRepository): Response
  {
    $moderateur = $this->getUser();

    // Activités récentes du modérateur
    $activites = $journalRepository->createQueryBuilder('j')
        ->where('j.moderation = :moderateur')
        ->setParameter('moderateur', $moderateur)
        ->orderBy('j.dateValidation', 'DESC')
        ->setMaxResults(50)
        ->getQuery()
        ->getResult();

    // Statistiques personnelles
    $stats_perso = $this->getStatistiquesPersonnelles($journalRepository, $moderateur);

    return $this->render('moderation/activite.html.twig', [
        'activites' => $activites,
        'stats' => $stats_perso
    ]);
  }

  // =====================
  // VOS MÉTHODES EXISTANTES (gardez-les telles quelles)
  // =====================

  #[Route('/signalement/{id}/valider', name: 'app_moderation_valider')]
  public function validerSignalement(
      Signalement $signalement,
      EntityManagerInterface $entityManager,
      Request $request,
      EmailService $emailService
  ): Response {
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
        $this->addFlash('warning', 'Signalement validé, mais l\'email n\'a pas pu être envoyé.');
      }

      $this->addFlash('success', 'Le signalement a été validé avec succès. Un email a été envoyé à l\'utilisateur.');

    } catch (\Exception $e) {
      $this->addFlash('error', 'Une erreur est survenue lors de la validation du signalement.');
    }

    // Rediriger vers le dashboard si on vient de là
    if ($request->headers->get('referer') && str_contains($request->headers->get('referer'), 'moderation')) {
      return $this->redirectToRoute('app_moderation_dashboard');
    }

    return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
  }

  #[Route('/signalement/{id}/rejeter', name: 'app_moderation_rejeter')]
  public function rejeterSignalement(
      Signalement $signalement,
      EntityManagerInterface $entityManager,
      Request $request,
      EmailService $emailService
  ): Response {
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
        $this->addFlash('warning', 'Signalement rejeté, mais l\'email n\'a pas pu être envoyé.');
      }

      $this->addFlash('success', 'Le signalement a été rejeté avec succès. Un email a été envoyé à l\'utilisateur.');

    } catch (\Exception $e) {
      $this->addFlash('error', 'Une erreur est survenue lors du rejet du signalement.');
    }

    // Rediriger vers le dashboard si on vient de là
    if ($request->headers->get('referer') && str_contains($request->headers->get('referer'), 'moderation')) {
      return $this->redirectToRoute('app_moderation_dashboard');
    }

    return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
  }

  #[Route('/signalement/{id}/modifier-statut', name: 'app_moderation_modifier_statut')]
  public function modifierStatutSignalement(
      Signalement $signalement,
      EntityManagerInterface $entityManager,
      Request $request
  ): Response {
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
    }

    return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
  }

  #[Route('/signalement/{id}/supprimer', name: 'app_moderation_supprimer', methods: ['POST'])]
  #[IsGranted('ROLE_ADMIN')]  // Seuls les admins peuvent supprimer définitivement
  public function supprimerSignalement(
      Request $request,
      Signalement $signalement,
      EntityManagerInterface $entityManager
  ): Response {
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
    }

    return $this->redirectToRoute('app_signalements');
  }

  // =====================
  // MÉTHODES PRIVÉES POUR LE DASHBOARD
  // =====================

  private function getSignalementsResolusAujourdhui(SignalementRepository $repository): int
  {
    $aujourd_hui = new \DateTime();
    $aujourd_hui->setTime(0, 0, 0);

    return $repository->createQueryBuilder('s')
        ->select('COUNT(s.id)')
        ->where('s.statut = :statut')
        ->andWhere('s.dateSignalement >= :today')
        ->setParameter('statut', StatutSignalement::RESOLU)
        ->setParameter('today', $aujourd_hui)
        ->getQuery()
        ->getSingleScalarResult();
  }

  private function getMesValidationsAujourdhui(JournalValidationRepository $repository, $moderateur): int
  {
    $aujourd_hui = new \DateTime();
    $aujourd_hui->setTime(0, 0, 0);

    return $repository->createQueryBuilder('j')
        ->select('COUNT(j.id)')
        ->where('j.moderation = :moderateur')
        ->andWhere('j.dateValidation >= :today')
        ->andWhere('j.action IN (:actions)')
        ->setParameter('moderateur', $moderateur)
        ->setParameter('today', $aujourd_hui)
        ->setParameter('actions', ['Validation', 'Rejet'])
        ->getQuery()
        ->getSingleScalarResult();
  }

  private function getTauxResolution(SignalementRepository $repository): int
  {
    $total = $repository->count([]);
    $resolus = $repository->count(['statut' => StatutSignalement::RESOLU]);

    return $total > 0 ? round(($resolus / $total) * 100) : 0;
  }

  private function getMaPerformance(JournalValidationRepository $repository, $moderateur): array
  {
    $debut_mois = new \DateTime('first day of this month');

    $validations_mois = $repository->createQueryBuilder('j')
        ->select('COUNT(j.id)')
        ->where('j.moderation = :moderateur')
        ->andWhere('j.action = :action')
        ->andWhere('j.dateValidation >= :debut_mois')
        ->setParameter('moderateur', $moderateur)
        ->setParameter('action', 'Validation')
        ->setParameter('debut_mois', $debut_mois)
        ->getQuery()
        ->getSingleScalarResult();

    $rejets_mois = $repository->createQueryBuilder('j')
        ->select('COUNT(j.id)')
        ->where('j.moderation = :moderateur')
        ->andWhere('j.action = :action')
        ->andWhere('j.dateValidation >= :debut_mois')
        ->setParameter('moderateur', $moderateur)
        ->setParameter('action', 'Rejet')
        ->setParameter('debut_mois', $debut_mois)
        ->getQuery()
        ->getSingleScalarResult();

    $total_actions = $validations_mois + $rejets_mois;

    return [
        'validations_mois' => $validations_mois,
        'rejets_mois' => $rejets_mois,
        'total_actions' => $total_actions,
        'taux_validation' => $total_actions > 0 ? round(($validations_mois / $total_actions) * 100) : 0
    ];
  }

  private function getAlertes(SignalementRepository $repository): array
  {
    $alertes = [];

    // Signalements très anciens en attente
    $anciens = $repository->createQueryBuilder('s')
        ->select('COUNT(s.id)')
        ->where('s.etatValidation = :attente')
        ->andWhere('s.dateSignalement < :limit')
        ->setParameter('attente', 'en_attente')
        ->setParameter('limit', new \DateTime('-7 days'))
        ->getQuery()
        ->getSingleScalarResult();

    if ($anciens > 0) {
      $alertes[] = [
          'type' => 'warning',
          'icon' => 'fas fa-clock',
          'titre' => 'Signalements anciens',
          'message' => "{$anciens} signalement(s) en attente depuis plus de 7 jours",
          'count' => $anciens
      ];
    }

    // Pic d'activité aujourd'hui
    $aujourd_hui = new \DateTime();
    $aujourd_hui->setTime(0, 0, 0);

    $nouveaux_aujourd_hui = $repository->createQueryBuilder('s')
        ->select('COUNT(s.id)')
        ->where('s.dateSignalement >= :today')
        ->setParameter('today', $aujourd_hui)
        ->getQuery()
        ->getSingleScalarResult();

    if ($nouveaux_aujourd_hui > 10) {
      $alertes[] = [
          'type' => 'info',
          'icon' => 'fas fa-chart-line',
          'titre' => 'Forte activité',
          'message' => "{$nouveaux_aujourd_hui} nouveaux signalements aujourd'hui",
          'count' => $nouveaux_aujourd_hui
      ];
    }

    return $alertes;
  }

  private function getSignalementsUrgents(SignalementRepository $repository): array
  {
    // Signalements en attente depuis plus de 3 jours
    return $repository->createQueryBuilder('s')
        ->where('s.etatValidation = :attente')
        ->andWhere('s.dateSignalement < :limit')
        ->setParameter('attente', 'en_attente')
        ->setParameter('limit', new \DateTime('-3 days'))
        ->orderBy('s.dateSignalement', 'ASC')
        ->getQuery()
        ->getResult();
  }

  private function getStatistiquesPersonnelles(JournalValidationRepository $repository, $moderateur): array
  {
    $debut_semaine = new \DateTime('monday this week');
    $debut_mois = new \DateTime('first day of this month');

    return [
        'total_actions' => $repository->count(['moderation' => $moderateur]),
        'actions_semaine' => $repository->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.moderation = :moderateur')
            ->andWhere('j.dateValidation >= :debut_semaine')
            ->setParameter('moderateur', $moderateur)
            ->setParameter('debut_semaine', $debut_semaine)
            ->getQuery()
            ->getSingleScalarResult(),
        'actions_mois' => $repository->createQueryBuilder('j')
            ->select('COUNT(j.id)')
            ->where('j.moderation = :moderateur')
            ->andWhere('j.dateValidation >= :debut_mois')
            ->setParameter('moderateur', $moderateur)
            ->setParameter('debut_mois', $debut_mois)
            ->getQuery()
            ->getSingleScalarResult()
    ];
  }
}