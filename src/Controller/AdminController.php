<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\AdminUserCreateTypeForm;
use App\Form\AdminUserEditTypeForm;
use App\Repository\UtilisateurRepository;
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
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
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
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');
        }
        
        return $this->redirectToRoute('app_admin_users');
    }
}