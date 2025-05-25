<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Enum\StatutSignalement;
use App\Form\SignalementTypeForm;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class SignalementController extends AbstractController
{
    #[Route('/signalements', name: 'app_signalements')]
    public function index(SignalementRepository $signalementRepository): Response
    {
        return $this->render('signalement/index.html.twig', [
            'signalements' => $signalementRepository->findBy(
                ['etatValidation' => 'validé'],
                ['dateSignalement' => 'DESC']
            )
        ]);
    }

    #[Route('/signalement/{id}', name: 'app_signalement_show')]
    public function show(int $id, SignalementRepository $signalementRepository): Response
    {
        $signalement = $signalementRepository->find($id);
        
        if (!$signalement) {
            throw $this->createNotFoundException('Signalement non trouvé');
        }
        
        return $this->render('signalement/show.html.twig', [
            'signalement' => $signalement,
        ]);
    }

    #[Route('/signalement/nouveau', name: 'app_signalement_nouveau')]
    public function nouveau(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $signalement = new Signalement();
        $form = $this->createForm(SignalementTypeForm::class, $signalement);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
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
                    // ... gérer l'exception
                }
            }
            
            $signalement->setUtilisateur($this->getUser());
            $signalement->setDateSignalement(new \DateTime());
            $signalement->setStatut(StatutSignalement::NOUVEAU);
            $signalement->setEtatValidation('en_attente');
            
            $entityManager->persist($signalement);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre signalement a été enregistré et sera validé prochainement.');
            
            return $this->redirectToRoute('app_signalements');
        }
        
        return $this->render('signalement/nouveau.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mes-signalements', name: 'app_mes_signalements')]
    public function mesSignalements(SignalementRepository $signalementRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        return $this->render('signalement/mes_signalements.html.twig', [
            'signalements' => $signalementRepository->findBy(
                ['utilisateur' => $this->getUser()],
                ['dateSignalement' => 'DESC']
            )
        ]);
    }
}