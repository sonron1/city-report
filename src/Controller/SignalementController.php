<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Entity\Utilisateur;
use App\Enum\StatutSignalement;
use App\Form\SignalementTypeForm;
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

// Dans src/Controller/SignalementController.php
use App\Enum\DemandeSuppressionStatut;
use App\Entity\JournalValidation;

class SignalementController extends AbstractController
{
    #[Route('/signalements', name: 'app_signalements')]
    #[IsGranted('ROLE_USER')]
    public function index(SignalementRepository $signalementRepository): Response
    {
        $signalements = $signalementRepository->findBy(['etatValidation' => 'valide'], ['dateSignalement' => 'DESC']);

        return $this->render('signalement/index.html.twig', [
            'signalements' => $signalements,
        ]);
    }

    // Suppression de la méthode carte() car elle est déjà gérée par CarteController

    #[Route('/signalement/{id}', name: 'app_signalement_show', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function show(int $id, SignalementRepository $signalementRepository): Response
    {
        $signalement = $signalementRepository->find($id);

        if (!$signalement) {
            throw $this->createNotFoundException('Signalement non trouvé');
        }

        // Vérifier que l'utilisateur a le droit de voir ce signalement
        if ($signalement->getEtatValidation() !== 'valide' &&
            $signalement->getUtilisateur() !== $this->getUser() &&
            !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de voir ce signalement.');
        }

        return $this->render('signalement/show.html.twig', [
            'signalement' => $signalement,
        ]);
    }

    #[Route('/signalement/nouveau', name: 'app_signalement_nouveau')]
    #[IsGranted('ROLE_USER')]
    public function nouveau(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
      $signalement = new Signalement();
      $signalement->setUtilisateur($this->getUser());
      $signalement->setStatut(StatutSignalement::NOUVEAU);
  
      $form = $this->createForm(SignalementTypeForm::class, $signalement);
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
          }
        }
  
        $entityManager->persist($signalement);
        $entityManager->flush();
  
        $this->addFlash('success', 'Votre signalement a été créé avec succès!');
        return $this->redirectToRoute('app_mes_signalements');
      }
  
      return $this->render('signalement/nouveau.html.twig', [
          'form' => $form->createView(),
      ]);
    }

    #[Route('/mes-signalements', name: 'app_mes_signalements')]
    #[IsGranted('ROLE_USER')]
    public function mesSignalements(SignalementRepository $signalementRepository): Response
    {
      /** @var Utilisateur $utilisateur */
      $utilisateur = $this->getUser();
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
      // Vérifier que le signalement n'a pas déjà une demande de suppression
      if ($signalement->getDemandeSuppressionStatut() !== null) {
        $this->addFlash('warning', 'Une demande de suppression est déjà en cours pour ce signalement.');
        return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
      }
  
      // Enregistrer la demande de suppression
      $signalement->setDemandeSuppressionStatut(DemandeSuppressionStatut::DEMANDEE->value);
  
      // Créer une entrée dans le journal de validation
      $journal = new JournalValidation();
      $journal->setSignalement($signalement);
      $journal->setUtilisateur($this->getUser());
      $journal->setDateAction(new \DateTime());
      $journal->setAction('Demande de suppression');
      $journal->setCommentaire('L\'utilisateur a demandé la suppression de ce signalement');
  
      $entityManager->persist($journal);
      $entityManager->flush();
  
      $this->addFlash('success', 'Votre demande de suppression a été enregistrée et sera traitée prochainement.');
  
      return $this->redirectToRoute('app_mes_signalements');
    }
}