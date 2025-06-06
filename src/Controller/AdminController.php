<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\JournalValidation;
use App\Entity\Signalement;
use App\Entity\Utilisateur;
use App\Entity\Ville;
use App\Enum\DemandeSuppressionStatut;
use App\Enum\StatutSignalement;
use App\Form\AdminUserCreateTypeForm;
use App\Form\AdminUserEditTypeForm;
use App\Form\CategorieTypeForm;
use App\Form\VilleTypeForm;
use App\Repository\CategorieRepository;
use App\Repository\SignalementRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
  #[Route('', name: 'app_admin_dashboard')]
  public function index(
      SignalementRepository $signalementRepository,
      UtilisateurRepository $utilisateurRepository,
      CategorieRepository $categorieRepository,
      VilleRepository $villeRepository
  ): Response
  {
    // Récupérer les statistiques
    $stats = [
        'totalSignalements' => $signalementRepository->count([]),
        'signalementsEnAttente' => $signalementRepository->count(['etatValidation' => 'en_attente']),
        'signalementsValides' => $signalementRepository->count(['etatValidation' => 'valide']),
        'signalementsRejetes' => $signalementRepository->count(['etatValidation' => 'rejete']),
        'signalementsResolus' => $signalementRepository->count(['statut' => StatutSignalement::RESOLU->value]),
        'totalUtilisateurs' => $utilisateurRepository->count([]),
        'utilisateursNonValides' => $utilisateurRepository->count(['estValide' => false]),
        'demandesSuppressions' => $signalementRepository->count(['demandeSuppressionStatut' => DemandeSuppressionStatut::DEMANDEE->value]),
        'totalCategories' => $categorieRepository->count([]),
        'totalVilles' => $villeRepository->count([]),
    ];

    // Récupérer les 10 derniers signalements
    $derniersSignalements = $signalementRepository->findBy(
        [],
        ['dateSignalement' => 'DESC'],
        10
    );

    return $this->render('admin/index.html.twig', [
        'stats' => $stats,
        'derniersSignalements' => $derniersSignalements,
    ]);
  }

  // =====================
  // GESTION DES UTILISATEURS
  // =====================

  #[Route('/utilisateurs', name: 'app_admin_users')]
  public function listUsers(UtilisateurRepository $utilisateurRepository): Response
  {
    $users = $utilisateurRepository->findAll();

    return $this->render('admin/users/index.html.twig', [
        'users' => $users,
    ]);
  }

  #[Route('/utilisateurs/nouveau', name: 'app_admin_users_new')]
  public function newUser(
      Request $request,
      EntityManagerInterface $entityManager,
      UserPasswordHasherInterface $passwordHasher
  ): Response
  {
    $user = new Utilisateur();
    $form = $this->createForm(AdminUserCreateTypeForm::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $user->setPassword($passwordHasher->hashPassword(
          $user,
          $form->get('plainPassword')->getData()
      ));

      // L'administrateur crée des comptes déjà validés
      $user->setEstValide(true);

      $entityManager->persist($user);
      $entityManager->flush();

      $this->addFlash('success', 'L\'utilisateur a été créé avec succès.');

      return $this->redirectToRoute('app_admin_users');
    }

    return $this->render('admin/users/new.html.twig', [
        'form' => $form->createView(),
    ]);
  }

  #[Route('/utilisateurs/{id}/modifier', name: 'app_admin_users_edit')]
  public function editUser(
      Request $request,
      Utilisateur $user,
      EntityManagerInterface $entityManager,
      UserPasswordHasherInterface $passwordHasher
  ): Response
  {
    $form = $this->createForm(AdminUserEditTypeForm::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Récupérer les rôles soumis
      $submittedRoles = $form->get('roles')->getData();

      // S'assurer qu'au moins ROLE_USER est présent si aucun rôle n'est sélectionné
      if (empty($submittedRoles) || !is_array($submittedRoles)) {
        $submittedRoles = ['ROLE_USER'];
      }

      // Mettre à jour les rôles
      $user->setRoles($submittedRoles);

      // Mettre à jour le mot de passe si fourni
      if ($plainPassword = $form->get('plainPassword')->getData()) {
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
      }

      $entityManager->flush();

      $this->addFlash('success', 'L\'utilisateur a été modifié avec succès. Rôles: ' . implode(', ', $submittedRoles));

      return $this->redirectToRoute('app_admin_users');
    }

    // Gestion des erreurs de formulaire
    if ($form->isSubmitted()) {
      $errors = $form->getErrors(true);
      foreach ($errors as $error) {
        $this->addFlash('error', 'Erreur de formulaire: ' . $error->getMessage());
      }
    }

    return $this->render('admin/users/edit.html.twig', [
        'form' => $form->createView(),
        'user' => $user,
    ]);
  }

  #[Route('/utilisateurs/{id}/supprimer', name: 'app_admin_users_delete', methods: ['POST'])]
  public function deleteUser(
      Request $request,
      Utilisateur $user,
      EntityManagerInterface $entityManager
  ): Response
  {
    if (!$this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
      $this->addFlash('error', 'Token de sécurité invalide.');
      return $this->redirectToRoute('app_admin_users');
    }

    try {
      // Vérifier si l'utilisateur a des signalements
      if (count($user->getSignalements()) > 0) {
        $this->addFlash('error', 'Impossible de supprimer cet utilisateur car il a des signalements associés.');
        return $this->redirectToRoute('app_admin_users');
      }

      $entityManager->remove($user);
      $entityManager->flush();

      $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');

    } catch (\Exception $e) {
      $this->addFlash('error', 'Une erreur est survenue lors de la suppression de l\'utilisateur.');
    }

    return $this->redirectToRoute('app_admin_users');
  }

  // =====================
  // GESTION DES DEMANDES DE SUPPRESSION
  // =====================

  #[Route('/demandes-suppression', name: 'app_admin_demandes_suppression')]
  public function demandesSuppressions(SignalementRepository $signalementRepository): Response
  {
    $demandes = $signalementRepository->findBy(
        ['demandeSuppressionStatut' => DemandeSuppressionStatut::DEMANDEE->value],
        ['dateDemandeSuppressionStatut' => 'DESC']
    );

    return $this->render('admin/demandes_suppression.html.twig', [
        'demandes' => $demandes
    ]);
  }

  #[Route('/demande-suppression/{id}/approve', name: 'app_admin_approve_suppression')]
  public function approuveSuppression(
      Signalement $signalement,
      EntityManagerInterface $entityManager
  ): Response
  {
    // Vérifier que la demande est en attente
    if ($signalement->getDemandeSuppressionStatut() !== DemandeSuppressionStatut::DEMANDEE->value) {
      $this->addFlash('error', 'Cette demande ne peut pas être approuvée.');
      return $this->redirectToRoute('app_admin_demandes_suppression');
    }

    try {
      // Créer une entrée dans le journal avant suppression
      $journal = new JournalValidation();
      $journal->setSignalement($signalement);
      $journal->setModerateur($this->getUser());
      $journal->setDateValidation(new \DateTime());
      $journal->setAction('Suppression approuvée');
      $journal->setCommentaire('Demande de suppression approuvée par l\'administrateur');

      $entityManager->persist($journal);
      $entityManager->flush(); // Sauvegarder le journal avant suppression

      // Supprimer le signalement
      $entityManager->remove($signalement);
      $entityManager->flush();

      $this->addFlash('success', 'Le signalement a été supprimé avec succès.');

    } catch (\Exception $e) {
      $this->addFlash('error', 'Une erreur est survenue lors de la suppression du signalement.');
    }

    return $this->redirectToRoute('app_admin_demandes_suppression');
  }

  #[Route('/demande-suppression/{id}/reject', name: 'app_admin_reject_suppression')]
  public function rejetSuppression(
      Signalement $signalement,
      Request $request,
      EntityManagerInterface $entityManager
  ): Response
  {
    // Vérifier que la demande est en attente
    if ($signalement->getDemandeSuppressionStatut() !== DemandeSuppressionStatut::DEMANDEE->value) {
      $this->addFlash('error', 'Cette demande ne peut pas être rejetée.');
      return $this->redirectToRoute('app_admin_demandes_suppression');
    }

    $commentaire = $request->request->get('commentaire', '');

    if (empty(trim($commentaire))) {
      $this->addFlash('error', 'Un motif de rejet est obligatoire.');
      return $this->redirectToRoute('app_admin_demandes_suppression');
    }

    try {
      // Mettre à jour le statut
      $signalement->setDemandeSuppressionStatut(DemandeSuppressionStatut::REJETEE->value);

      // Créer une entrée dans le journal
      $journal = new JournalValidation();
      $journal->setSignalement($signalement);
      $journal->setModerateur($this->getUser());
      $journal->setDateValidation(new \DateTime());
      $journal->setAction('Demande suppression rejetée');
      $journal->setCommentaire($commentaire);

      $entityManager->persist($journal);
      $entityManager->flush();

      $this->addFlash('success', 'La demande de suppression a été rejetée.');

    } catch (\Exception $e) {
      $this->addFlash('error', 'Une erreur est survenue lors du rejet de la demande.');
    }

    return $this->redirectToRoute('app_admin_demandes_suppression');
  }

  // =====================
  // GESTION DES SIGNALEMENTS (CONSULTATION UNIQUEMENT)
  // =====================

  #[Route('/signalements', name: 'app_admin_signalements')]
  public function listSignalements(
      SignalementRepository $signalementRepository,
      CategorieRepository $categorieRepository,
      Request $request
  ): Response
  {
    // Récupérer les paramètres de filtrage
    $filters = [
        'etat' => $request->query->get('etat'),
        'statut' => $request->query->get('statut'),
        'categorie' => $request->query->get('categorie'),
    ];

    // Nettoyer les filtres vides
    $filters = array_filter($filters);

    // Si aucun filtre d'état n'est spécifié, afficher tous les signalements
    if (empty($filters)) {
      $signalements = $signalementRepository->findBy([], ['dateSignalement' => 'DESC']);
    } else {
      $signalements = $signalementRepository->findByFilters($filters);
    }

    $categories = $categorieRepository->findAll();

    // Récupérer les statistiques pour le menu latéral
    $stats = $this->getAdminStats($signalementRepository, $categorieRepository);

    return $this->render('admin/signalements/index.html.twig', [
        'signalements' => $signalements,
        'categories' => $categories,
        'stats' => $stats,
        'currentFilters' => $filters,
    ]);
  }

  #[Route('/signalements/{id}', name: 'app_admin_signalements_show')]
  public function showSignalement(Signalement $signalement): Response
  {
    return $this->render('admin/signalements/show.html.twig', [
        'signalement' => $signalement,
    ]);
  }

  #[Route('/signalements/{id}/supprimer-force', name: 'app_admin_signalements_delete_force', methods: ['POST'])]
  public function deleteSignalementForce(
      Request $request,
      Signalement $signalement,
      EntityManagerInterface $entityManager
  ): Response
  {
    // Vérification du token CSRF
    if (!$this->isCsrfTokenValid('delete_force' . $signalement->getId(), $request->request->get('_token'))) {
      $this->addFlash('error', 'Token de sécurité invalide.');
      return $this->redirectToRoute('app_admin_signalements');
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
      $journal->setAction('Suppression forcée par admin');
      $journal->setCommentaire("Signalement supprimé définitivement par l'administrateur: {$titre} (Utilisateur: {$utilisateurNom})");

      $entityManager->persist($journal);
      $entityManager->flush(); // Sauvegarder le journal avant suppression

      // Supprimer le signalement
      $entityManager->remove($signalement);
      $entityManager->flush();

      $this->addFlash('success', "Le signalement \"{$titre}\" a été supprimé définitivement.");

    } catch (\Exception $e) {
      $this->addFlash('error', 'Une erreur est survenue lors de la suppression du signalement.');
    }

    return $this->redirectToRoute('app_admin_signalements');
  }

  // =====================
  // GESTION DES CATÉGORIES
  // =====================

  #[Route('/categories', name: 'app_admin_categories')]
  public function listCategories(
      CategorieRepository $categorieRepository,
      SignalementRepository $signalementRepository
  ): Response
  {
    $categories = $categorieRepository->findAll();

    // Récupérer les statistiques pour le menu latéral
    $stats = $this->getAdminStats($signalementRepository, $categorieRepository);

    return $this->render('admin/categories/index.html.twig', [
        'categories' => $categories,
        'stats' => $stats,
    ]);
  }

  #[Route('/categories/nouvelle', name: 'app_admin_categories_new')]
  public function newCategorie(Request $request, EntityManagerInterface $entityManager): Response
  {
    $categorie = new Categorie();
    $form = $this->createForm(CategorieTypeForm::class, $categorie);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $entityManager->persist($categorie);
      $entityManager->flush();

      $this->addFlash('success', 'La catégorie a été créée avec succès.');

      return $this->redirectToRoute('app_admin_categories');
    }

    return $this->render('admin/categories/form.html.twig', [
        'form' => $form->createView(),
        'categorie' => $categorie
    ]);
  }

  #[Route('/categories/{id}/modifier', name: 'app_admin_categories_edit')]
  public function editCategorie(
      Request $request,
      Categorie $categorie,
      EntityManagerInterface $entityManager
  ): Response
  {
    $form = $this->createForm(CategorieTypeForm::class, $categorie);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $entityManager->flush();

      $this->addFlash('success', 'La catégorie a été modifiée avec succès.');

      return $this->redirectToRoute('app_admin_categories');
    }

    return $this->render('admin/categories/form.html.twig', [
        'form' => $form->createView(),
        'categorie' => $categorie,
    ]);
  }

  #[Route('/categories/{id}/supprimer', name: 'app_admin_categories_delete', methods: ['POST'])]
  public function deleteCategorie(
      Request $request,
      Categorie $categorie,
      EntityManagerInterface $entityManager
  ): Response
  {
    if (!$this->isCsrfTokenValid('delete' . $categorie->getId(), $request->request->get('_token'))) {
      $this->addFlash('error', 'Token de sécurité invalide.');
      return $this->redirectToRoute('app_admin_categories');
    }

    // Vérifier si la catégorie est utilisée
    $signalements = $categorie->getSignalements();

    if (count($signalements) > 0) {
      $this->addFlash('error', 'Cette catégorie ne peut pas être supprimée car elle est utilisée par ' . count($signalements) . ' signalement(s).');
      return $this->redirectToRoute('app_admin_categories');
    }

    try {
      $entityManager->remove($categorie);
      $entityManager->flush();

      $this->addFlash('success', 'La catégorie a été supprimée avec succès.');

    } catch (\Exception $e) {
      $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la catégorie.');
    }

    return $this->redirectToRoute('app_admin_categories');
  }

  // =====================
  // GESTION DES VILLES
  // =====================

  #[Route('/villes', name: 'app_admin_villes')]
  public function listVilles(
      VilleRepository $villeRepository,
      SignalementRepository $signalementRepository,
      CategorieRepository $categorieRepository,
      Request $request
  ): Response
  {
    // Récupérer le numéro de page depuis la requête (par défaut: 1)
    $page = $request->query->getInt('page', 1);
    $limit = 12; // Nombre de villes par page

    // Récupérer les villes paginées
    $paginator = $villeRepository->findPaginated($page, $limit);

    // Récupérer les statistiques pour le menu latéral
    $stats = $this->getAdminStats($signalementRepository, $categorieRepository);

    return $this->render('admin/villes/index.html.twig', [
        'villes' => $paginator->getItems(),
        'paginator' => $paginator,
        'stats' => $stats,
    ]);
  }

  #[Route('/villes/nouvelle', name: 'app_admin_villes_new')]
  public function newVille(Request $request, EntityManagerInterface $entityManager): Response
  {
    $ville = new Ville();
    $form = $this->createForm(VilleTypeForm::class, $ville);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $entityManager->persist($ville);
      $entityManager->flush();

      $this->addFlash('success', 'La ville a été créée avec succès.');

      return $this->redirectToRoute('app_admin_villes');
    }

    return $this->render('admin/villes/form.html.twig', [
        'form' => $form->createView(),
        'ville' => $ville
    ]);
  }

  #[Route('/villes/{id}/modifier', name: 'app_admin_villes_edit')]
  public function editVille(
      Request $request,
      Ville $ville,
      EntityManagerInterface $entityManager
  ): Response
  {
    $form = $this->createForm(VilleTypeForm::class, $ville);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $entityManager->flush();

      $this->addFlash('success', 'La ville a été modifiée avec succès.');

      return $this->redirectToRoute('app_admin_villes');
    }

    return $this->render('admin/villes/form.html.twig', [
        'form' => $form->createView(),
        'ville' => $ville,
    ]);
  }

  #[Route('/villes/{id}/supprimer', name: 'app_admin_villes_delete', methods: ['POST'])]
  public function deleteVille(
      Request $request,
      Ville $ville,
      EntityManagerInterface $entityManager
  ): Response
  {
    if (!$this->isCsrfTokenValid('delete' . $ville->getId(), $request->request->get('_token'))) {
      $this->addFlash('error', 'Token de sécurité invalide.');
      return $this->redirectToRoute('app_admin_villes');
    }

    // Vérifier si la ville est utilisée
    $signalements = $ville->getSignalements();
    $utilisateurs = $ville->getUtilisateurs();

    if (count($signalements) > 0 || count($utilisateurs) > 0) {
      $message = 'Cette ville ne peut pas être supprimée car elle est utilisée par ';
      $details = [];

      if (count($signalements) > 0) {
        $details[] = count($signalements) . ' signalement(s)';
      }

      if (count($utilisateurs) > 0) {
        $details[] = count($utilisateurs) . ' utilisateur(s)';
      }

      $message .= implode(' et ', $details) . '.';

      $this->addFlash('error', $message);
      return $this->redirectToRoute('app_admin_villes');
    }

    try {
      $entityManager->remove($ville);
      $entityManager->flush();

      $this->addFlash('success', 'La ville a été supprimée avec succès.');

    } catch (\Exception $e) {
      $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la ville.');
    }

    return $this->redirectToRoute('app_admin_villes');
  }

  // ... [Gardez tous les autres méthodes existantes jusqu'à la méthode statistiques] ...

  // =====================
  // STATISTIQUES AMÉLIORÉES
  // =====================

  #[Route('/statistiques', name: 'app_admin_statistiques')]
  public function statistiques(
      SignalementRepository $signalementRepository,
      UtilisateurRepository $utilisateurRepository,
      CategorieRepository $categorieRepository,
      VilleRepository $villeRepository
  ): Response
  {
      // Statistiques générales
      $totalSignalements = $signalementRepository->count([]);
      $totalUtilisateurs = $utilisateurRepository->count([]);
      $totalCategories = $categorieRepository->count([]);
      $totalVilles = $villeRepository->count([]);

      // Données pour les graphiques
      $signalementsParMois = $this->getSignalementsParMois($signalementRepository);
      $signalementsParStatut = $this->getSignalementsParStatut($signalementRepository);
      $signalementsParVille = $this->getSignalementsParVille($signalementRepository);
      $signalementsParCategorie = $this->getSignalementsParCategorie($signalementRepository);

      // Activité récente
      $activiteRecente = $this->getActiviteRecente($signalementRepository, $utilisateurRepository);

      // Statistiques pour la validation des signalements
      $validationStats = [
          'en_attente' => $signalementRepository->count(['etatValidation' => 'en_attente']),
          'valide' => $signalementRepository->count(['etatValidation' => 'valide']),
          'rejete' => $signalementRepository->count(['etatValidation' => 'rejete']),
      ];

      $stats = [
          // Données principales pour les cartes
          'total_users' => $totalUtilisateurs,
          'total_signalements' => $totalSignalements,
          'total_villes' => $totalVilles,
          'total_categories' => $totalCategories,
          
          // Données pour les graphiques
          'signalements_par_mois' => $signalementsParMois,
          'signalements_par_statut' => array_values($signalementsParStatut),
          'signalements_par_ville' => $signalementsParVille,
          'signalements_par_categorie' => $signalementsParCategorie,
          
          // Statistiques détaillées
          'validation' => $validationStats,
      ];

      return $this->render('admin/statistiques.html.twig', [
          'stats' => $stats,
          'recent_activities' => $activiteRecente
      ]);
  }

  // =====================
  // MÉTHODES PRIVÉES POUR LES STATISTIQUES
  // =====================

  private function getSignalementsParMois(SignalementRepository $repository): array
  {
      $currentYear = date('Y');
      $data = [];
      
      for ($month = 1; $month <= 12; $month++) {
          $startDate = new \DateTime("$currentYear-$month-01");
          $endDate = clone $startDate;
          $endDate->modify('last day of this month')->setTime(23, 59, 59);
          
          $count = $repository->createQueryBuilder('s')
              ->select('COUNT(s.id)')
              ->where('s.dateSignalement BETWEEN :start AND :end')
              ->setParameter('start', $startDate)
              ->setParameter('end', $endDate)
              ->getQuery()
              ->getSingleScalarResult();
              
          $data[] = (int) $count;
      }
      
      return $data;
  }

  private function getSignalementsParStatut(SignalementRepository $repository): array
  {
      $data = [];
      
      foreach (StatutSignalement::cases() as $statut) {
          $count = $repository->count(['statut' => $statut]);
          $data[$statut->value] = $count;
      }
      
      return $data;
  }

  private function getSignalementsParVille(SignalementRepository $repository): array
  {
      $results = $repository->createQueryBuilder('s')
          ->select('v.nom as name, COUNT(s.id) as count')
          ->join('s.ville', 'v')
          ->groupBy('v.id')
          ->orderBy('count', 'DESC')
          ->setMaxResults(10)
          ->getQuery()
          ->getResult();

      return array_map(function($item) {
          return [
              'name' => $item['name'],
              'count' => (int) $item['count']
          ];
      }, $results);
  }

  private function getSignalementsParCategorie(SignalementRepository $repository): array
  {
      $results = $repository->createQueryBuilder('s')
          ->select('c.nom as name, COUNT(s.id) as count')
          ->join('s.categorie', 'c')
          ->groupBy('c.id')
          ->orderBy('count', 'DESC')
          ->setMaxResults(10)
          ->getQuery()
          ->getResult();

      return array_map(function($item) {
          return [
              'name' => $item['name'],
              'count' => (int) $item['count']
          ];
      }, $results);
  }

  private function getActiviteRecente(SignalementRepository $signalementRepository, UtilisateurRepository $utilisateurRepository): array
  {
      $activities = [];

      // Récupérer les 5 derniers signalements
      $derniersSignalements = $signalementRepository->findBy(
          [],
          ['dateSignalement' => 'DESC'],
          5
      );

      foreach ($derniersSignalements as $signalement) {
          $activities[] = [
              'type' => 'new-report',
              'icon' => 'fa-exclamation-triangle',
              'title' => 'Nouveau signalement',
              'description' => $signalement->getTitre(),
              'date' => $signalement->getDateSignalement()
          ];
      }

      // Récupérer les 3 derniers utilisateurs
      $derniersUtilisateurs = $utilisateurRepository->findBy(
          [],
          ['dateInscription' => 'DESC'],
          3
      );

      foreach ($derniersUtilisateurs as $utilisateur) {
          $activities[] = [
              'type' => 'new-user',
              'icon' => 'fa-user-plus',
              'title' => 'Nouvel utilisateur',
              'description' => $utilisateur->getPrenom() . ' ' . $utilisateur->getNom(),
              'date' => $utilisateur->getDateInscription()
          ];
      }

      // Trier par date
      usort($activities, function($a, $b) {
          return $b['date'] <=> $a['date'];
      });

      return array_slice($activities, 0, 8);
  }

  // ... [Gardez toutes les autres méthodes existantes] ...

  /**
   * Méthode privée pour obtenir les statistiques utilisées dans la sidebar
   */
  private function getAdminStats(
      SignalementRepository $signalementRepository,
      CategorieRepository $categorieRepository,
      UtilisateurRepository $utilisateurRepository = null,
      VilleRepository $villeRepository = null
  ): array
  {
    $stats = [
        'signalementsEnAttente' => $signalementRepository->count(['etatValidation' => 'en_attente']),
        'demandesSuppressions' => $signalementRepository->count(['demandeSuppressionStatut' => DemandeSuppressionStatut::DEMANDEE->value]),
        'totalCategories' => $categorieRepository->count([]),
    ];

    if ($utilisateurRepository) {
      $stats['utilisateursNonValides'] = $utilisateurRepository->count(['estValide' => false]);
    }

    if ($villeRepository) {
      $stats['totalVilles'] = $villeRepository->count([]);
    }

    return $stats;
  }
}