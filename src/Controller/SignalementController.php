<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Enum\StatutSignalement;
use App\Form\CommentaireTypeForm;
use App\Form\SignalementTypeForm;
use App\Repository\SignalementRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/signalement')]
class SignalementController extends AbstractController
{
    #[Route('/', name: 'app_signalement_index')]
    public function index(SignalementRepository $signalementRepository): Response
    {
        return $this->render('signalement/index.html.twig', [
            'signalements' => $signalementRepository->findBy(
                ['etatValidation' => 'validé'],
                ['dateSignalement' => 'DESC']
            ),
        ]);
    }
    
    #[Route('/nouveau', name: 'app_signalement_nouveau')]
    #[IsGranted('ROLE_USER')]
    public function nouveau(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $signalement = new Signalement();
        $form = $this->createForm(SignalementType::class, $signalement);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $signalement->setUtilisateur($this->getUser());
            $signalement->setDateSignalement(new \DateTime());
            $signalement->setStatut(StatutSignalement::NOUVEAU);
            $signalement->setEtatValidation('en_attente');
            
            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('photoFile')->getData();
            
            if ($photoFile) {
                $photoFileName = $fileUploader->upload($photoFile);
                $signalement->setPhotoUrl($photoFileName);
            }
            
            $entityManager->persist($signalement);
            
            // Création de notification pour les modérateurs
            $moderateurs = $entityManager->getRepository('App:Utilisateur')->findByRoles('ROLE_MODERATOR');
            foreach ($moderateurs as $moderateur) {
                $notification = new Notification();
                $notification->setDestinataire($moderateur);
                $notification->setSignalement($signalement);
                $notification->setMessage('Nouveau signalement à valider : ' . $signalement->getTitre());
                $notification->setType('moderation');
                $entityManager->persist($notification);
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre signalement a bien été enregistré et sera traité rapidement.');
            return $this->redirectToRoute('app_mes_signalements');
        }
        
        return $this->render('signalement/nouveau.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}', name: 'app_signalement_show', methods: ['GET', 'POST'])]
    public function show(Signalement $signalement, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que le signalement est validé ou appartient à l'utilisateur courant
        if ($signalement->getEtatValidation() !== 'validé' && 
            ($this->getUser() !== $signalement->getUtilisateur() && !$this->isGranted('ROLE_MODERATOR'))) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce signalement.');
        }
        
        // Formulaire de commentaire
        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid() && $this->getUser()) {
            $commentaire->setUtilisateur($this->getUser());
            $commentaire->setSignalement($signalement);
            $commentaire->setEtatValidation('en_attente');
            
            $entityManager->persist($commentaire);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre commentaire a bien été ajouté et sera visible après modération.');
            return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
        }
        
        return $this->render('signalement/show.html.twig', [
            'signalement' => $signalement,
            'commentaire_form' => $form->createView(),
        ]);
    }
    
    #[Route('/mes-signalements', name: 'app_mes_signalements')]
    #[IsGranted('ROLE_USER')]
    public function mesSignalements(SignalementRepository $signalementRepository): Response
    {
        $signalements = $signalementRepository->findBy(
            ['utilisateur' => $this->getUser()],
            ['dateSignalement' => 'DESC']
        );
        
        return $this->render('signalement/mes_signalements.html.twig', [
            'signalements' => $signalements,
        ]);
    }
}