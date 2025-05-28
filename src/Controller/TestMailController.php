<?php
// src/Controller/MailTestController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TestMailController extends AbstractController
{
  /**
   */
  #[Route('/test-mail', name: 'test_mail')]
  public function test(MailerInterface $mailer): Response
  {
    try {
        $email = (new Email())
            ->from($this->getParameter('app.email_sender'))
            ->to('you@example.com')
            ->subject('Test Mail')
            ->text('Ceci est un test de MailHog avec Symfony.');

        $mailer->send($email);

        $this->addFlash('success', 'L\'email a été envoyé avec succès.');
        return new Response('Email envoyé avec succès.');
    } catch (TransportExceptionInterface $e) {
        $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
        return new Response('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage(), 500);
    }
}
}