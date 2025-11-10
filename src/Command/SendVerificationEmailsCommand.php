<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:send-verification-emails',
    description: 'Wyślij emaile weryfikacyjne do wszystkich niezweryfikowanych użytkowników',
)]
class SendVerificationEmailsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private EmailVerifier $emailVerifier
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->em->getRepository(User::class)->findBy(['isVerified' => false]);

        if (empty($users)) {
            $io->success('Brak niezweryfikowanych użytkowników.');
            return Command::SUCCESS;
        }

        $io->title('Wysyłanie emaili weryfikacyjnych');
        $io->progressStart(count($users));

        $successCount = 0;
        $errorCount = 0;

        foreach ($users as $user) {
            try {
                $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                    (new TemplatedEmail())
                        ->from(new Address('no-reply@nyskaligabowlingowa.pl', 'Nyska Liga Bowlingowa'))
                        ->to($user->getEmail())
                        ->subject('Potwierdź swój adres email')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                );

                $successCount++;
                $io->writeln(' ✓ ' . $user->getEmail());
            } catch (\Exception $e) {
                $errorCount++;
                $io->writeln(' ✗ ' . $user->getEmail() . ' - Błąd: ' . $e->getMessage());
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        $io->success([
            "Wysłano emaile weryfikacyjne!",
            "Sukces: {$successCount}",
            "Błędy: {$errorCount}",
        ]);

        return Command::SUCCESS;
    }
}
