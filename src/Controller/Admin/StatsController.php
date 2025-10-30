<?php

namespace App\Controller\Admin;

use App\Entity\Frame;
use App\Entity\Game;
use App\Entity\GameStatus;
use App\Entity\User;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/stats')]
class StatsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private GameRepository $gameRepository
    ) {}

    #[Route('/ranking', name: 'admin_stats_ranking')]
    public function ranking(): Response
    {
        $players = $this->userRepository->findAll();
        $stats = [];

        foreach ($players as $player) {
            $finishedGames = $this->em->getRepository(Game::class)
                ->createQueryBuilder('g')
                ->join('g.frames', 'f')
                ->leftJoin('f.teamAPlayers', 'tap')
                ->leftJoin('f.teamBPlayers', 'tbp')
                ->where('g.status = :status')
                ->andWhere('tap.id = :playerId OR tbp.id = :playerId')
                ->setParameter('status', GameStatus::FINISHED)
                ->setParameter('playerId', $player->getId())
                ->getQuery()
                ->getResult();

            $totalScore = 0;
            $gamesCount = 0;
            $strikes = 0;
            $spares = 0;

            foreach ($finishedGames as $game) {
                $score = $game->getPlayerTotalScore($player);
                $totalScore += $score;
                $gamesCount++;

                foreach ($game->getFrames() as $frame) {
                    if ($frame->getPlayerRolls($player)->count() > 0) {
                        if ($frame->isPlayerStrike($player)) {
                            $strikes++;
                        }
                        if ($frame->isPlayerSpare($player)) {
                            $spares++;
                        }
                    }
                }
            }

            $avgScore = $gamesCount > 0 ? round($totalScore / $gamesCount, 2) : 0;

            if ($gamesCount > 0) {
                $stats[] = [
                    'player' => $player,
                    'gamesCount' => $gamesCount,
                    'totalScore' => $totalScore,
                    'avgScore' => $avgScore,
                    'strikes' => $strikes,
                    'spares' => $spares,
                ];
            }
        }

        usort($stats, fn($a, $b) => $b['avgScore'] <=> $a['avgScore']);

        return $this->render('admin/stats/ranking.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/players', name: 'admin_stats_players')]
    public function players(): Response
    {
        $players = $this->userRepository->findAll();
        $detailedStats = [];

        foreach ($players as $player) {
            $finishedGames = $this->em->getRepository(Game::class)
                ->createQueryBuilder('g')
                ->join('g.frames', 'f')
                ->leftJoin('f.teamAPlayers', 'tap')
                ->leftJoin('f.teamBPlayers', 'tbp')
                ->where('g.status = :status')
                ->andWhere('tap.id = :playerId OR tbp.id = :playerId')
                ->setParameter('status', GameStatus::FINISHED)
                ->setParameter('playerId', $player->getId())
                ->getQuery()
                ->getResult();

            $scores = [];
            $strikes = 0;
            $spares = 0;
            $totalFrames = 0;

            foreach ($finishedGames as $game) {
                $scores[] = $game->getPlayerTotalScore($player);

                foreach ($game->getFrames() as $frame) {
                    if ($frame->getPlayerRolls($player)->count() > 0) {
                        $totalFrames++;
                        if ($frame->isPlayerStrike($player)) {
                            $strikes++;
                        }
                        if ($frame->isPlayerSpare($player)) {
                            $spares++;
                        }
                    }
                }
            }

            $gamesCount = count($scores);

            if ($gamesCount > 0) {
                $totalScore = array_sum($scores);
                $avgScore = round($totalScore / $gamesCount, 2);
                $maxScore = max($scores);
                $minScore = min($scores);
                $strikeRate = $totalFrames > 0 ? round(($strikes / $totalFrames) * 100, 1) : 0;
                $spareRate = $totalFrames > 0 ? round(($spares / $totalFrames) * 100, 1) : 0;

                $detailedStats[] = [
                    'player' => $player,
                    'gamesCount' => $gamesCount,
                    'totalScore' => $totalScore,
                    'avgScore' => $avgScore,
                    'maxScore' => $maxScore,
                    'minScore' => $minScore,
                    'strikes' => $strikes,
                    'spares' => $spares,
                    'strikeRate' => $strikeRate,
                    'spareRate' => $spareRate,
                    'totalFrames' => $totalFrames,
                ];
            }
        }

        usort($detailedStats, fn($a, $b) => $b['avgScore'] <=> $a['avgScore']);

        return $this->render('admin/stats/players.html.twig', [
            'stats' => $detailedStats,
        ]);
    }
}
