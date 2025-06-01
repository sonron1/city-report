<?php

namespace App\Controller;

use App\Entity\Reparation;
use App\Entity\Signalement;
use App\Form\ReparationTypeForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReparationController extends AbstractController
{
    #[Route('/reparation/new/{signalement_id}', name: 'app_reparation_new')]
    #[IsGranted('ROLE_ADMIN', 'ROLE_MODERATOR')]
    public function new(Request $request, EntityManagerInterface $entityManager, Signalement $signalement, int $signalement_id): Response
    {
        // Vérifier si le signalement a déjà une réparation
        if ($signalement->getReparation()) {
            $this->addFlash('warning', 'Ce signalement a déjà une réparation planifiée.');
            return $this->redirectToRoute('app_signalement_show', ['id' => $signalement_id]);
        }
        
        $reparation = new Reparation();
        $reparation->setSignalement($signalement);
        $reparation->setDateCreation(new \DateTime());
        
        $form = $this->createForm(ReparationTypeForm::class, $reparation);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reparation);
            $entityManager->flush();
            
            $this->addFlash('success', 'La réparation a été planifiée avec succès.');
            return $this->redirectToRoute('app_signalement_show', ['id' => $signalement_id]);
        }
        
        return $this->render('reparation/new.html.twig', [
            'form' => $form->createView(),
            'signalement' => $signalement
        ]);
    }
    
    #[Route('/reparation/edit/{id}', name: 'app_reparation_edit')]
    #[IsGranted('ROLE_ADMIN', 'ROLE_MODERATOR')]
    public function edit(Request $request, EntityManagerInterface $entityManager, Reparation $reparation): Response
    {
        $form = $this->createForm(ReparationTypeForm::class, $reparation);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'La réparation a été mise à jour avec succès.');
            return $this->redirectToRoute('app_signalement_show', ['id' => $reparation->getSignalement()->getId()]);
        }
        
        return $this->render('reparation/edit.html.twig', [
            'form' => $form->createView(),
            'reparation' => $reparation,
            'signalement' => $reparation->getSignalement()
        ]);
    }
}