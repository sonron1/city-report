<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Signalement;
use App\Form\CommentaireTypeForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommentaireController extends AbstractController
{
    #[Route('/commentaire/add/{signalement_id}', name: 'app_commentaire_add')]
    #[IsGranted('ROLE_USER')]
    public function add(Request $request, EntityManagerInterface $entityManager, Signalement $signalement, int $signalement_id): Response
    {
        $commentaire = new Commentaire();
        $commentaire->setSignalement($signalement);
        $commentaire->setUtilisateur($this->getUser());
        $commentaire->setDateCreation(new \DateTime());
        
        $form = $this->createForm(CommentaireTypeForm::class, $commentaire);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commentaire);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès.');
        } else if ($form->isSubmitted()) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout du commentaire.');
        }
        
        return $this->redirectToRoute('app_signalement_show', ['id' => $signalement_id]);
    }
}