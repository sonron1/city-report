<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class TestMailController extends AbstractController
{
    #[Route('/test-mail', name: 'app_test_mail')]
    public function index(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('test@example.com')
            ->to('lolathread@gmail.com')
            ->subject('Test email from Symfony')
            ->text('This is a test email sent from Symfony.')
            ->html('<p>This is a test email sent from Symfony.</p>');

        try {
            $mailer->send($email);
            return new Response('Email envoyé avec succès! Vérifiez votre serveur SMTP.');
        } catch (\Exception $e) {
            return new Response('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage(), 500);
        }
    }
}