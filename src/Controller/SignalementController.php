<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Entity\Utilisateur;
use App\Enum\StatutSignalement;
use App\Form\SignalementTypeForm;
use App\EventListener\AccessDeniedListener;
use App\Repository\SignalementRepository;
use App\Repository\CategorieRepository;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Enum\DemandeSuppressionStatut;
use App\Entity\JournalValidation;
use App\Entity\Commentaire;
use App\Form\CommentaireTypeForm;

class SignalementController extends AbstractController
{
    #[Route('/signalements', name: 'app_signalements')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(
        Request $request,
        SignalementRepository $signalementRepository,
        VilleRepository $villeRepository,
        CategorieRepository $categorieRepository
    ): Response {
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est validé (sauf pour les modérateurs et admins)
        if (!$user->isEstValide() && !$this->isGranted('ROLE_MODERATOR')) {
            $this->addFlash('error', 'Votre compte doit être validé pour accéder aux signalements.');
            return $this->redirectToRoute('app_home');
        }
        
        $page = max(1, $request->query->getInt('page', 1));
        $itemsPerPage = 9; // 9 signalements par page (3x3)
        $currentTab = $request->query->get('tab', 'my'); // 'my' (par défaut) ou 'all'
        
        // Préparation des filtres
        $filters = [
            'statut' => $request->query->get('statut'),
            'categorie' => $request->query->has('categorie') ? $request->query->get('categorie') : null,
            'ville' => $request->query->has('ville') ? $request->query->get('ville') : null,
            'date' => $request->query->get('date'),
        ];
        
        // Filtrer les valeurs vides
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
        
        // Pour les modérateurs et admins, afficher différemment
        if ($this->isGranted('ROLE_MODERATOR')) {
            // Les modérateurs voient tous les signalements par défaut
            $allSignalements = $signalementRepository->findAllSignalementsPaginated(
                $page,
                $itemsPerPage,
                $filters,
                $this->isGranted('ROLE_MODERATOR') // Inclure les non-validés pour les modérateurs
            );
            
            // Signalements de l'utilisateur (pour l'onglet "Mes signalements")
            $userSignalements = $signalementRepository->findUserSignalementsPaginated(
                $user,
                $currentTab === 'my' ? $page : 1,
                $itemsPerPage,
                $filters
            );
            
            // Statistiques pour les modérateurs
            $userSignalementsEnCours = $signalementRepository->countUserSignalementsByStatus($user, 'en_cours');
            $userSignalementsResolus = $signalementRepository->countUserSignalementsByStatus($user, 'resolu');
            $userSignalementsRejetes = $signalementRepository->countUserSignalementsByStatus($user, 'rejete');
        } else {
            // Utilisateurs normaux
            $userSignalements = $signalementRepository->findUserSignalementsPaginated(
                $user,
                $page,
                $itemsPerPage,
                $filters
            );
            
            $userSignalementsEnCours = $signalementRepository->countUserSignalementsByStatus($user, 'en_cours');
            $userSignalementsResolus = $signalementRepository->countUserSignalementsByStatus($user, 'resolu');
            $userSignalementsRejetes = $signalementRepository->countUserSignalementsByStatus($user, 'rejete');
            
            // Signalements publics (validés)
            $allSignalementsPage = $currentTab === 'all' ? $page : 1;
            $allSignalements = $signalementRepository->findAllPublicSignalementsPaginated(
                $allSignalementsPage,
                $itemsPerPage,
                $filters
            );
        }
        
        // Récupérer les villes et catégories pour les filtres
        $villes = $villeRepository->findAll();
        $categories = $categorieRepository->findAll();
        
        return $this->render('signalement/index.html.twig', [
            'user_signalements_paginator' => $userSignalements,
            'user_signalements_en_cours' => $userSignalementsEnCours,
            'user_signalements_resolus' => $userSignalementsResolus,
            'user_signalements_rejetes' => $userSignalementsRejetes,
            'all_signalements_paginator' => $allSignalements,
            'villes' => $villes,
            'categories' => $categories,
            'is_moderator' => $this->isGranted('ROLE_MODERATOR'),
        ]);
    }

    #[Route('/signalement/{id}', name: 'app_signalement_show', requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(Signalement $signalement): Response
    {
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est validé (sauf pour les modérateurs et admins)
        if (!$user->isEstValide() && !$this->isGranted('ROLE_MODERATOR')) {
            $this->addFlash('error', 'Votre compte doit être validé pour accéder aux signalements.');
            return $this->redirectToRoute('app_home');
        }

        // Créer un nouvel objet Commentaire
        $commentaire = new Commentaire();
        $commentaire->setSignalement($signalement);

        // Créer le formulaire
        $commentForm = $this->createForm(CommentaireTypeForm::class, $commentaire, [
            'action' => $this->generateUrl('app_commentaire_add', ['signalement_id' => $signalement->getId()])
        ]);

        // Rendre la vue avec le formulaire
        return $this->render('signalement/show.html.twig', [
            'signalement' => $signalement,
            'commentForm' => $commentForm->createView()
        ]);
    }

    #[Route('/signalement/nouveau', name: 'app_signalement_nouveau')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function nouveau(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est validé
        if (!$user->isEstValide()) {
            $this->addFlash('error', 'Votre compte doit être validé pour créer des signalements.');
            return $this->redirectToRoute('app_home');
        }

        $signalement = new Signalement();
        $signalement->setUtilisateur($user);
        $signalement->setDateSignalement(new \DateTime());
        $signalement->setStatut(StatutSignalement::NOUVEAU);

        $form = $this->createForm(SignalementTypeForm::class, $signalement, [
            'arrondissement_url' => $this->generateUrl('app_arrondissement_api_by_ville', ['id' => '_villeId_'])
        ]);

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload de photo
            $photoFile = $form->get('photo')->getData();
            
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid('', true).'.'.$photoFile->guessExtension();
                
                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    $signalement->setPhotoUrl($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image.');
                    return $this->redirectToRoute('app_signalement_nouveau');
                }
            }
            
            $entityManager->persist($signalement);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre signalement a été créé avec succès!');
            return $this->redirectToRoute('app_signalements');
        }
        
        return $this->render('signalement/nouveau.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mes-signalements', name: 'app_mes_signalements')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function mesSignalements(SignalementRepository $signalementRepository): Response
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $this->getUser();
        
        // Vérifier que l'utilisateur est validé
        if (!$utilisateur->isEstValide()) {
            $this->addFlash('error', 'Votre compte doit être validé pour accéder à vos signalements.');
            return $this->redirectToRoute('app_home');
        }
        
        $signalements = $signalementRepository->findBy(
            ['utilisateur' => $utilisateur],
            ['dateSignalement' => 'DESC']
        );

        return $this->render('signalement/mes_signalements.html.twig', [
            'signalements' => $signalements,
        ]);
    }

    #[Route('/signalement/{id}/demande-suppression', name: 'app_signalement_demande_suppression')]
    #[IsGranted('request_delete', subject: 'signalement')]
    public function demanderSuppression(Signalement $signalement, EntityManagerInterface $entityManager): Response
    {
        if ($signalement->getDemandeSuppressionStatut() !== null) {
            $this->addFlash('warning', 'Une demande de suppression est déjà en cours pour ce signalement.');
            return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
        }

        $signalement->setDemandeSuppressionStatut(DemandeSuppressionStatut::DEMANDEE->value);

        // ✅ CORRIGER ICI :
        $journal = new JournalValidation();
        $journal->setSignalement($signalement);
        $journal->setModerateur($this->getUser()); // ✅ Correct (même si c'est un utilisateur normal)
        $journal->setDateValidation(new \DateTime()); // ✅ Correct
        $journal->setAction('Demande de suppression');
        $journal->setCommentaire('L\'utilisateur a demandé la suppression de ce signalement');

        $entityManager->persist($journal);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande de suppression a été enregistrée et sera traitée prochainement.');

        return $this->redirectToRoute('app_mes_signalements');
    }

    #[Route('/signalement/{id}/delete', name: 'app_signalement_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Signalement $signalement,
        EntityManagerInterface $entityManager
    ): Response
    {
        try {
            // Créer une entrée dans le journal (si besoin de garder une trace)
            $journal = new JournalValidation();
            $journal->setSignalement($signalement);
            $journal->setModerateur($this->getUser());
            $journal->setDateValidation(new \DateTime());
            $journal->setAction('Suppression définitive');
            $journal->setCommentaire('Signalement "' . $signalement->getTitre() . '" supprimé par ' . $this->getUser()->getPrenom() . ' ' . $this->getUser()->getNom());

            // Persister le journal avant la suppression
            $entityManager->persist($journal);
            $entityManager->flush();

            // Supprimer le signalement
            $entityManager->remove($signalement);
            $entityManager->flush();

            $this->addFlash('success', 'Le signalement a été supprimé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression du signalement : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_signalements');
    }

    #[Route('/signalement/{id}/modifier', name: 'app_signalement_modifier')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function modifier(
        Signalement $signalement,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est propriétaire du signalement
        if ($signalement->getUtilisateur() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres signalements.');
        }

        // Vérifier que le signalement est rejeté
        if ($signalement->getEtatValidation() !== 'rejete') {
            $this->addFlash('error', 'Seuls les signalements rejetés peuvent être modifiés.');
            return $this->redirectToRoute('app_mes_signalements');
        }

        $form = $this->createForm(SignalementTypeForm::class, $signalement, [
            'arrondissement_url' => $this->generateUrl('app_arrondissement_api_by_ville', ['id' => '_villeId_'])
        ]);

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload de nouvelle photo (optionnel)
            $photoFile = $form->get('photo')->getData();
            
            if ($photoFile) {
                // Supprimer l'ancienne photo si elle existe
                if ($signalement->getPhotoUrl()) {
                    $anciennePhoto = $this->getParameter('photos_directory') . '/' . $signalement->getPhotoUrl();
                    if (file_exists($anciennePhoto)) {
                        unlink($anciennePhoto);
                    }
                }

                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid('', true).'.'.$photoFile->guessExtension();
                
                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    $signalement->setPhotoUrl($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image.');
                    return $this->redirectToRoute('app_signalement_modifier', ['id' => $signalement->getId()]);
                }
            }
            
            // Remettre en attente de validation
            $signalement->setEtatValidation('en_attente');
            
            // Créer une entrée dans le journal
            $journal = new JournalValidation();
            $journal->setSignalement($signalement);
            $journal->setModerateur($user);
            $journal->setDateValidation(new \DateTime());
            $journal->setAction('Modification après rejet');
            $journal->setCommentaire('L\'utilisateur a modifié son signalement suite au rejet');

            $entityManager->persist($journal);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre signalement a été modifié et soumis à nouveau pour validation !');
            return $this->redirectToRoute('app_mes_signalements');
        }
        
        return $this->render('signalement/modifier.html.twig', [
            'form' => $form->createView(),
            'signalement' => $signalement,
        ]);
    }
}