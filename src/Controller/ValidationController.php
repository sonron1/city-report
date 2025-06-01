<?php

namespace App\Controller;

use App\Entity\Signalement;
use App\Entity\JournalValidation;
use App\Enum\EtatValidation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ValidationController extends AbstractController
{
    #[Route('/validation/valider/{id}', name: 'app_validation_valider')]
    #[IsGranted('ROLE_ADMIN', 'ROLE_MODERATOR')]
    public function valider(Signalement $signalement, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que le signalement est en attente
        if ($signalement->getEtatValidation() !== EtatValidation::EN_ATTENTE->value) {
            $this->addFlash('warning', 'Ce signalement a déjà été traité.');
            return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
        }
        
        // Mettre à jour l'état de validation
        $signalement->setEtatValidation(EtatValidation::VALIDE->value);
        
        // Créer une entrée dans le journal de validation
        $journal = new JournalValidation();
        $journal->setSignalement($signalement);
        $journal->setUtilisateur($this->getUser());
        $journal->setDateAction(new \DateTime());
        $journal->setAction('Validation');
        $journal->setCommentaire('Le signalement a été validé');
        
        $entityManager->persist($journal);
        $entityManager->flush();
        
        $this->addFlash('success', 'Le signalement a été validé avec succès.');
        
        return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
    }
    
    #[Route('/validation/rejeter/{id}', name: 'app_validation_rejeter')]
    #[IsGranted('ROLE_ADMIN', 'ROLE_MODERATOR')]
    public function rejeter(Signalement $signalement, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que le signalement est en attente
        if ($signalement->getEtatValidation() !== EtatValidation::EN_ATTENTE->value) {
            $this->addFlash('warning', 'Ce signalement a déjà été traité.');
            return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
        }
        
        // Mettre à jour l'état de validation
        $signalement->setEtatValidation(EtatValidation::REJETE->value);
        
        // Créer une entrée dans le journal de validation
        $journal = new JournalValidation();
        $journal->setSignalement($signalement);
        $journal->setUtilisateur($this->getUser());
        $journal->setDateAction(new \DateTime());
        $journal->setAction('Rejet');
        $journal->setCommentaire('Le signalement a été rejeté');
        
        $entityManager->persist($journal);
        $entityManager->flush();
        
        $this->addFlash('success', 'Le signalement a été rejeté.');
        
        return $this->redirectToRoute('app_signalement_show', ['id' => $signalement->getId()]);
    }
}