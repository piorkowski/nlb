<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Entity\GameStatus;
use App\Entity\League;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class StatsService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Ranking graczy indywidualnych - oparty o punkty za mecze
     */
    public function getIndividualRanking(?League $league = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('g')
            ->from(Game::class, 'g')
            ->where('g.status = :status')
            ->andWhere('g.teamA IS NULL')
            ->andWhere('g.teamB IS NULL')
            ->setParameter('status', GameStatus::FINISHED);

        if ($league) {
            $qb->andWhere('g.league = :league')
                ->setParameter('league', $league);
        }

        $games = $qb->getQuery()->getResult();

        $playerStats = [];

        foreach ($games as $game) {
            $players = $game->getAllPlayers();

            foreach ($players as $player) {
                $playerId = $player->getId();

                if (!isset($playerStats[$playerId])) {
                    $playerStats[$playerId] = [
                        'player' => $player,
                        'points' => 0,
                        'gamesPlayed' => 0,
                        'wins' => 0,
                        'draws' => 0,
                        'losses' => 0,
                        'totalScore' => 0,
                    ];
                }

                $playerPoints = $game->getPlayerPoints($player);
                $playerStats[$playerId]['points'] += $playerPoints;
                $playerStats[$playerId]['gamesPlayed']++;
                $playerStats[$playerId]['totalScore'] += $game->getPlayerTotalScore($player);

                // Zlicz wygrane/remisy/przegrane
                if ($playerPoints == 4) {
                    $playerStats[$playerId]['wins']++;
                } elseif ($playerPoints == 2) {
                    $playerStats[$playerId]['draws']++;
                } elseif ($playerPoints == 0) {
                    $playerStats[$playerId]['losses']++;
                } else {
                    // 1 lub 3 punkty = mieszany wynik (1 wygrana + 1 remis lub podobne)
                    // Policzymy to jako częściowy sukces
                }
            }
        }

        // Sortuj po punktach (malejąco), potem po średniej (malejąco)
        usort($playerStats, function($a, $b) {
            if ($a['points'] === $b['points']) {
                $avgA = $a['gamesPlayed'] > 0 ? $a['totalScore'] / $a['gamesPlayed'] : 0;
                $avgB = $b['gamesPlayed'] > 0 ? $b['totalScore'] / $b['gamesPlayed'] : 0;
                return $avgB <=> $avgA;
            }
            return $b['points'] <=> $a['points'];
        });

        return $playerStats;
    }

    /**
     * Ranking drużynowy - oparty o punkty za mecze
     */
    public function getTeamRanking(?League $league = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('g')
            ->from(Game::class, 'g')
            ->where('g.status = :status')
            ->andWhere('g.teamA IS NOT NULL')
            ->andWhere('g.teamB IS NOT NULL')
            ->setParameter('status', GameStatus::FINISHED);

        if ($league) {
            $qb->andWhere('g.league = :league')
                ->setParameter('league', $league);
        }

        $games = $qb->getQuery()->getResult();

        $teamStats = [];

        foreach ($games as $game) {
            $teamA = $game->getTeamA();
            $teamB = $game->getTeamB();

            // Team A
            if (!isset($teamStats[$teamA->getId()])) {
                $teamStats[$teamA->getId()] = [
                    'team' => $teamA,
                    'points' => 0,
                    'gamesPlayed' => 0,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'totalScore' => 0,
                    'totalScoreAgainst' => 0,
                ];
            }

            $teamAPoints = $game->getTeamAPoints() ?? 0;
            $teamBPoints = $game->getTeamBPoints() ?? 0;

            $teamStats[$teamA->getId()]['points'] += $teamAPoints;
            $teamStats[$teamA->getId()]['gamesPlayed']++;
            $teamStats[$teamA->getId()]['totalScore'] += $game->getTeamAScore();
            $teamStats[$teamA->getId()]['totalScoreAgainst'] += $game->getTeamBScore();

            if ($teamAPoints == 4) {
                $teamStats[$teamA->getId()]['wins']++;
            } elseif ($teamAPoints == 2 && $teamBPoints == 2) {
                $teamStats[$teamA->getId()]['draws']++;
            } elseif ($teamAPoints == 0) {
                $teamStats[$teamA->getId()]['losses']++;
            }

            // Team B
            if (!isset($teamStats[$teamB->getId()])) {
                $teamStats[$teamB->getId()] = [
                    'team' => $teamB,
                    'points' => 0,
                    'gamesPlayed' => 0,
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'totalScore' => 0,
                    'totalScoreAgainst' => 0,
                ];
            }

            $teamStats[$teamB->getId()]['points'] += $teamBPoints;
            $teamStats[$teamB->getId()]['gamesPlayed']++;
            $teamStats[$teamB->getId()]['totalScore'] += $game->getTeamBScore();
            $teamStats[$teamB->getId()]['totalScoreAgainst'] += $game->getTeamAScore();

            if ($teamBPoints == 4) {
                $teamStats[$teamB->getId()]['wins']++;
            } elseif ($teamBPoints == 2 && $teamAPoints == 2) {
                $teamStats[$teamB->getId()]['draws']++;
            } elseif ($teamBPoints == 0) {
                $teamStats[$teamB->getId()]['losses']++;
            }
        }

        // Sortuj po punktach, potem po bilansie bramek
        usort($teamStats, function($a, $b) {
            if ($a['points'] === $b['points']) {
                $diffA = $a['totalScore'] - $a['totalScoreAgainst'];
                $diffB = $b['totalScore'] - $b['totalScoreAgainst'];
                return $diffB <=> $diffA;
            }
            return $b['points'] <=> $a['points'];
        });

        return $teamStats;
    }

    /**
     * Statystyki graczy - oparte o rzuty (strikes, spares, średnia)
     */
    public function getPlayerStats(?League $league = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('g')
            ->from(Game::class, 'g')
            ->where('g.status = :status')
            ->setParameter('status', GameStatus::FINISHED);

        if ($league) {
            $qb->andWhere('g.league = :league')
                ->setParameter('league', $league);
        }

        $games = $qb->getQuery()->getResult();

        $playerStats = [];

        foreach ($games as $game) {
            $players = $game->getAllPlayers();

            foreach ($players as $player) {
                $playerId = $player->getId();

                if (!isset($playerStats[$playerId])) {
                    $playerStats[$playerId] = [
                        'player' => $player,
                        'gamesPlayed' => 0,
                        'totalScore' => 0,
                        'strikes' => 0,
                        'spares' => 0,
                        'maxScore' => 0,
                        'minScore' => PHP_INT_MAX,
                        'totalFrames' => 0,
                    ];
                }

                $score = $game->getPlayerTotalScore($player);
                $playerStats[$playerId]['gamesPlayed']++;
                $playerStats[$playerId]['totalScore'] += $score;
                $playerStats[$playerId]['maxScore'] = max($playerStats[$playerId]['maxScore'], $score);
                $playerStats[$playerId]['minScore'] = min($playerStats[$playerId]['minScore'], $score);

                // Policz strikes i spares
                foreach ($game->getFrames() as $frame) {
                    if ($frame->getPlayerRolls($player)->count() > 0) {
                        $playerStats[$playerId]['totalFrames']++;

                        if ($frame->isPlayerStrike($player)) {
                            $playerStats[$playerId]['strikes']++;
                        }
                        if ($frame->isPlayerSpare($player)) {
                            $playerStats[$playerId]['spares']++;
                        }
                    }
                }
            }
        }

        // Oblicz średnie i sortuj
        foreach ($playerStats as &$stats) {
            $stats['avgScore'] = $stats['gamesPlayed'] > 0
                ? round($stats['totalScore'] / $stats['gamesPlayed'], 2)
                : 0;
            $stats['strikeRate'] = $stats['totalFrames'] > 0
                ? round(($stats['strikes'] / $stats['totalFrames']) * 100, 1)
                : 0;
            $stats['spareRate'] = $stats['totalFrames'] > 0
                ? round(($stats['spares'] / $stats['totalFrames']) * 100, 1)
                : 0;

            if ($stats['minScore'] === PHP_INT_MAX) {
                $stats['minScore'] = 0;
            }
        }

        usort($playerStats, fn($a, $b) => $b['avgScore'] <=> $a['avgScore']);

        return $playerStats;
    }

    /**
     * Pobierz wszystkie ligi do filtrowania
     */
    public function getAllLeagues(): array
    {
        return $this->em->getRepository(League::class)->findBy([], ['name' => 'ASC']);
    }
}
