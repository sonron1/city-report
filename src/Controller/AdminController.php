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
// Dans AdminController.php
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

    #[Route('/utilisateurs', name: 'app_admin_users')]
    public function listUsers(UtilisateurRepository $utilisateurRepository): Response
    {
      $users = $utilisateurRepository->findAll();

      return $this->render('admin/users/index.html.twig', [
          'users' => $users,
      ]);
  }

  #[Route('/utilisateurs/nouveau', name: 'app_admin_users_new')]
  public function newUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
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
  public function editUser(Request $request, Utilisateur $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
  {
    $form = $this->createForm(AdminUserEditTypeForm::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      if ($plainPassword = $form->get('plainPassword')->getData()) {
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
      }

      $entityManager->flush();

      $this->addFlash('success', 'L\'utilisateur a été modifié avec succès.');

      return $this->redirectToRoute('app_admin_users');
    }

    return $this->render('admin/users/edit.html.twig', [
        'form' => $form->createView(),
        'user' => $user,
    ]);
  }

  #[Route('/utilisateurs/{id}/supprimer', name: 'app_admin_users_delete', methods: ['POST'])]
  public function deleteUser(Request $request, Utilisateur $user, EntityManagerInterface $entityManager): Response
  {
    if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
      $entityManager->remove($user);
      $entityManager->flush();

      $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');
    }

    return $this->redirectToRoute('app_admin_users');
  }


  #[Route('/demandes-suppression', name: 'app_admin_demandes_suppression')]
  #[IsGranted('ROLE_ADMIN')]
  public function demandesSuppressions(SignalementRepository $signalementRepository): Response
  {
    $demandes = $signalementRepository->findBy(['demandeSuppressionStatut' => DemandeSuppressionStatut::DEMANDEE->value]);

    return $this->render('admin/demandes_suppression.html.twig', [
        'demandes' => $demandes
    ]);
  }

  #[Route('/demande-suppression/{id}/approve', name: 'app_admin_approve_suppression')]
  #[IsGranted('ROLE_ADMIN')]
  public function approuveSuppression(Signalement $signalement, EntityManagerInterface $entityManager): Response
  {
    // Vérifier que la demande est en attente
    if ($signalement->getDemandeSuppressionStatut() !== DemandeSuppressionStatut::DEMANDEE->value) {
      $this->addFlash('error', 'Cette demande ne peut pas être approuvée.');
      return $this->redirectToRoute('app_admin_demandes_suppression');
    }

    // Créer une entrée dans le journal
    $journal = new JournalValidation();
    $journal->setSignalement($signalement);
    $journal->setModerateur($this->getUser());
    $journal->setAction('suppression_approuvee');

    $entityManager->persist($journal);

    // Supprimer le signalement
    $entityManager->remove($signalement);
    $entityManager->flush();

    $this->addFlash('success', 'Le signalement a été supprimé avec succès.');

    return $this->redirectToRoute('app_admin_demandes_suppression');
  }

  #[Route('/demande-suppression/{id}/reject', name: 'app_admin_reject_suppression')]
  #[IsGranted('ROLE_ADMIN')]
  public function rejetSuppression(Signalement $signalement, Request $request, EntityManagerInterface $entityManager): Response
  {
    // Vérifier que la demande est en attente
    if ($signalement->getDemandeSuppressionStatut() !== DemandeSuppressionStatut::DEMANDEE->value) {
      $this->addFlash('error', 'Cette demande ne peut pas être rejetée.');
      return $this->redirectToRoute('app_admin_demandes_suppression');
    }

    $commentaire = $request->request->get('commentaire');

    // Mettre à jour le statut
    $signalement->setDemandeSuppressionStatut(DemandeSuppressionStatut::REJETEE->value);

    // Créer une entrée dans le journal
    $journal = new JournalValidation();
    $journal->setSignalement($signalement);
    $journal->setModerateur($this->getUser());
    $journal->setAction('suppression_rejetee');
    $journal->setCommentaire($commentaire);

    $entityManager->persist($journal);
    $entityManager->flush();

    $this->addFlash('success', 'La demande de suppression a été rejetée.');

    return $this->redirectToRoute('app_admin_demandes_suppression');
  }
  
  
  #Signalements
  
  #[Route('/signalements', name: 'app_admin_signalements')]
  public function listSignalements(
      SignalementRepository $signalementRepository,
      CategorieRepository $categorieRepository
  ): Response
  {
      $signalements = $signalementRepository->findBy([], ['dateSignalement' => 'DESC']);
      $categories = $categorieRepository->findAll();
      
      // Récupérer les statistiques pour le menu latéral
      $stats = $this->getAdminStats($signalementRepository, $categorieRepository);

      return $this->render('admin/signalements/index.html.twig', [
          'signalements' => $signalements,
          'categories' => $categories,
          'stats' => $stats,
      ]);
  }

  #[Route('/signalements/{id}/valider', name: 'app_admin_signalements_valider')]
  public function validerSignalement(
      Signalement $signalement,
      EntityManagerInterface $entityManager,
      Request $request
  ): Response
  {
      if ($signalement->getEtatValidation() === 'valide') {
          $this->addFlash('info', 'Ce signalement est déjà validé.');
          return $this->redirectToRoute('app_admin_signalements');
      }

      $commentaire = $request->request->get('commentaire', '');

      // Mettre à jour le statut
      $signalement->setEtatValidation('valide');

      // Créer une entrée dans le journal
      $journal = new JournalValidation();
      $journal->setSignalement($signalement);
      $journal->setUtilisateur($this->getUser());
      $journal->setDateAction(new \DateTime());
      $journal->setAction('Validation');
      $journal->setCommentaire($commentaire);

      $entityManager->persist($journal);
      $entityManager->flush();

      $this->addFlash('success', 'Le signalement a été validé avec succès.');

      return $this->redirectToRoute('app_admin_signalements');
  }

  #[Route('/signalements/{id}/rejeter', name: 'app_admin_signalements_rejeter')]
  public function rejeterSignalement(
      Signalement $signalement,
      EntityManagerInterface $entityManager,
      Request $request
  ): Response
  {
      if ($signalement->getEtatValidation() === 'rejete') {
          $this->addFlash('info', 'Ce signalement est déjà rejeté.');
          return $this->redirectToRoute('app_admin_signalements');
      }

      $commentaire = $request->request->get('commentaire', '');

      // Mettre à jour le statut
      $signalement->setEtatValidation('rejete');

      // Créer une entrée dans le journal
      $journal = new JournalValidation();
      $journal->setSignalement($signalement);
      $journal->setUtilisateur($this->getUser());
      $journal->setDateAction(new \DateTime());
      $journal->setAction('Rejet');
      $journal->setCommentaire($commentaire);

      $entityManager->persist($journal);
      $entityManager->flush();

      $this->addFlash('success', 'Le signalement a été rejeté avec succès.');

      return $this->redirectToRoute('app_admin_signalements');
  }

  #[Route('/signalements/{id}/modifier-statut', name: 'app_admin_signalements_modifier_statut')]
  public function modifierStatutSignalement(
      Signalement $signalement,
      EntityManagerInterface $entityManager,
      Request $request
  ): Response
  {
      $nouveauStatut = $request->request->get('statut');
      $commentaire = $request->request->get('commentaire', '');

      // Vérifier que le statut est valide
      if (!in_array($nouveauStatut, ['NOUVEAU', 'EN_COURS', 'RESOLU', 'FERME'])) {
          $this->addFlash('error', 'Le statut fourni n\'est pas valide.');
          return $this->redirectToRoute('app_admin_signalements');
      }

      // Mettre à jour le statut
      $signalement->setStatut(StatutSignalement::from($nouveauStatut));

      // Créer une entrée dans le journal
      $journal = new JournalValidation();
      $journal->setSignalement($signalement);
      $journal->setUtilisateur($this->getUser());
      $journal->setDateAction(new \DateTime());
      $journal->setAction('Modification statut');
      $journal->setCommentaire("Statut modifié en $nouveauStatut. $commentaire");

      $entityManager->persist($journal);
      $entityManager->flush();

      $this->addFlash('success', 'Le statut du signalement a été modifié avec succès.');

      return $this->redirectToRoute('app_admin_signalements');
    } 
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


  // Pour les catégories
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
  public function editCategorie(Request $request, Categorie $categorie, EntityManagerInterface $entityManager): Response
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
  public function deleteCategorie(Request $request, Categorie $categorie, EntityManagerInterface $entityManager): Response
  {
      if ($this->isCsrfTokenValid('delete' . $categorie->getId(), $request->request->get('_token'))) {
          // Vérifier si la catégorie est utilisée
          $signalements = $categorie->getSignalements();
          
          if (count($signalements) > 0) {
              $this->addFlash('error', 'Cette catégorie ne peut pas être supprimée car elle est utilisée par des signalements.');
              return $this->redirectToRoute('app_admin_categories');
          }
          
          $entityManager->remove($categorie);
          $entityManager->flush();
  
          $this->addFlash('success', 'La catégorie a été supprimée avec succès.');
      }
  
      return $this->redirectToRoute('app_admin_categories');
    }
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
        $villesPaginator = $villeRepository->findPaginated($page, $limit);
        
        // Récupérer les statistiques pour le menu latéral
        $stats = $this->getAdminStats($signalementRepository, $categorieRepository);

        return $this->render('admin/villes/index.html.twig', [
            'villes' => $villesPaginator->getItems(),
            'paginator' => $villesPaginator,
            'stats' => $stats,
        ]);
    }


  // Pour les villes
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
  public function editVille(Request $request, Ville $ville, EntityManagerInterface $entityManager): Response
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
  public function deleteVille(Request $request, Ville $ville, EntityManagerInterface $entityManager): Response
  {
    if ($this->isCsrfTokenValid('delete' . $ville->getId(), $request->request->get('_token'))) {
      // Vérifier si la ville est utilisée
      $signalements = $ville->getSignalements();

      if (count($signalements) > 0) {
        $this->addFlash('error', 'Cette ville ne peut pas être supprimée car elle est utilisée par des signalements.');
        return $this->redirectToRoute('app_admin_villes');
      }

      $entityManager->remove($ville);
      $entityManager->flush();

      $this->addFlash('success', 'La ville a été supprimée avec succès.');
    }

    return $this->redirectToRoute('app_admin_villes');

  }
  
  // Méthode privée pour obtenir les statistiques utilisées dans la sidebar
  private function getAdminStats(
      SignalementRepository $signalementRepository,
      CategorieRepository $categorieRepository, 
      UtilisateurRepository $utilisateurRepository = null,
      VilleRepository $villeRepository = null
  ): array
  {
      $stats = [
          'signalementsEnAttente' => $signalementRepository->count(['etatValidation' => 'en_attente']),
          'demandesSuppressions' => $signalementRepository->count(['demandeSuppressionStatut' => 'demandee']),
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