<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\RegistrationFormTypeForm as RegistrationFormType;
//use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            
            // Génération du token de confirmation
            $token = bin2hex(random_bytes(30));
            $user->setConfirmationToken($token);
            
            // Définition de la date d'expiration (24 heures)
            $expiry = new \DateTime();
            $expiry->modify('+24 hours');
            $user->setTokenExpiryDate($expiry);
            
            $entityManager->persist($user);
            $entityManager->flush();

            // Envoi de l'email de confirmation
            // Dans la méthode register() du RegistrationController.php
            $email = (new TemplatedEmail())
                ->from(new Address($this->getParameter('app.email_sender'), 'CityFlow'))  // Changez ceci par l'adresse Gmail désirée
                ->to($user->getEmail())
                ->subject('Confirmation de votre compte CityFlow')
                ->htmlTemplate('emails/confirmation.html.twig')
                ->context([
                    'user' => $user,
                    'token' => $token,
                    'expiry' => $expiry,
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Votre compte a été créé avec succès ! Un email de confirmation vous a été envoyé. Veuillez vérifier votre boîte de réception.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
    
    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verifyUserEmail(string $token, Request $request, EntityManagerInterface $entityManager): Response
    {
        $userRepository = $entityManager->getRepository(Utilisateur::class);
        $user = $userRepository->findOneBy(['confirmationToken' => $token]);
        
        if (!$user) {
            $this->addFlash('error', 'Le lien de vérification est invalide.');
            return $this->redirectToRoute('app_login');
        }
        
        if ($user->isTokenExpired()) {
            $this->addFlash('error', 'Le lien de vérification a expiré. Veuillez demander un nouveau lien.');
            return $this->redirectToRoute('app_request_verify_email');
        }
        
        // Validation du compte
        $user->setEstValide(true);
        $user->setConfirmationToken(null);
        $user->setTokenExpiryDate(null);
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès. Vous pouvez maintenant vous connecter.');
        
        return $this->redirectToRoute('app_login');
    }
    
    #[Route('/verify/resend', name: 'app_request_verify_email')]
    public function requestVerifyEmail(): Response
    {
        return $this->render('registration/request_verify_email.html.twig');
    }
    
    #[Route('/verify/resend/send', name: 'app_resend_verify_email', methods: ['POST'])]
    public function resendVerifyEmail(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $email = $request->request->get('email');
        
        if (!$email) {
            $this->addFlash('error', 'Veuillez fournir une adresse email.');
            return $this->redirectToRoute('app_request_verify_email');
        }
        
        $userRepository = $entityManager->getRepository(Utilisateur::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            // Ne pas révéler si l'email existe pour des raisons de sécurité
            $this->addFlash('success', 'Si votre adresse email existe dans notre système, un nouvel email de vérification vous a été envoyé.');
            return $this->redirectToRoute('app_login');
        }
        
        if ($user->isEstValide()) {
            $this->addFlash('info', 'Votre compte est déjà vérifié. Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }
        
        // Génération d'un nouveau token
        $token = bin2hex(random_bytes(30));
        $user->setConfirmationToken($token);
        
        // Définition de la date d'expiration (24 heures)
        $expiry = new \DateTime();
        $expiry->modify('+24 hours');
        $user->setTokenExpiryDate($expiry);
        
        $entityManager->flush();
        
        // Envoi de l'email de confirmation
        $email = (new TemplatedEmail())
            ->from(new Address($this->getParameter('app.email_sender'), 'CityFlow'))
            ->to($user->getEmail())
            ->subject('Confirmation de votre compte CityFlow')
            ->htmlTemplate('emails/confirmation.html.twig')
            ->context([
                'user' => $user,
                'token' => $token,
                'expiry' => $expiry,
            ]);

        $mailer->send($email);
        
        $this->addFlash('success', 'Un nouvel email de vérification vous a été envoyé. Veuillez vérifier votre boîte de réception.');
        
        return $this->redirectToRoute('app_login');
    }
}