<?php

namespace App\command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;


#[AsCommand(
    name: 'app:test-email',
    description: 'Envoie un email de test via MailHog'
)]
class TestEmailCommand extends Command
{


   // protected static $defaultName = 'app:test-email';
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('recipient', InputArgument::REQUIRED, 'Email recipient');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $recipient = $input->getArgument('recipient');
        
        $email = (new Email())
            ->from('test@example.com')
            ->to($recipient)
            ->subject('Test depuis la console')
            ->text('Ceci est un test depuis la console Symfony.')
            ->html('<p>Ceci est un test depuis la console Symfony.</p>');

        try {
            $this->mailer->send($email);
            $output->writeln('Email envoyé avec succès!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}