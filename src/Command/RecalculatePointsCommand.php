<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Game;
use App\Entity\GameStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:recalculate-points',
    description: 'Przelicza punkty dla wszystkich zakończonych meczy',
)]
class RecalculatePointsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $games = $this->em->getRepository(Game::class)
            ->findBy(['status' => GameStatus::FINISHED]);

        if (empty($games)) {
            $io->info('Brak zakończonych meczy do przeliczenia.');
            return Command::SUCCESS;
        }

        $io->title('Przeliczanie punktów');
        $io->progressStart(count($games));

        $count = 0;
        foreach ($games as $game) {
            $game->calculatePoints();
            $count++;
            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();

        $io->success("Przeliczono punkty dla {$count} meczy!");

        return Command::SUCCESS;
    }
}
