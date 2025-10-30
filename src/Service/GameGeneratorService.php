<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Entity\Frame;
use App\Entity\League;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class GameGeneratorService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Tworzy grę indywidualną 1v1
     */
    public function createIndividualGame(
        League $league,
        User $player1,
        User $player2,
        \DateTimeImmutable $date,
        int $startLane = 1
    ): Game {
        $game = new Game();
        $game->setLeague($league);
        $game->setGameDate($date);

        // Generuj framy
        $this->generateIndividualFrames($game, $player1, $player2, $startLane);

        $this->em->persist($game);
        $this->em->flush();

        return $game;
    }

    /**
     * Tworzy grę drużynową 3v3
     */
    public function createTeamGame(
        League $league,
        Team $team1,
        Team $team2,
        array $team1Players, // 3 zawodników
        array $team2Players, // 3 zawodników
        \DateTimeImmutable $date,
        int $startLane = 1
    ): Game {
        if (count($team1Players) !== 3 || count($team2Players) !== 3) {
            throw new \InvalidArgumentException('Each team must have exactly 3 players');
        }

        $game = new Game();
        $game->setLeague($league);
        $game->setTeamA($team1);
        $game->setTeamB($team2);
        $game->setGameDate($date);

        // Generuj framy
        $this->generateTeamFrames($game, $team1, $team2, $team1Players, $team2Players, $startLane);

        $this->em->persist($game);
        $this->em->flush();

        return $game;
    }

    /**
     * Generuje framy dla istniejącego meczu indywidualnego
     */
    public function generateIndividualFrames(
        Game $game,
        User $player1,
        User $player2,
        int $startLane = 1
    ): void {
        // Sprawdź czy mecz nie ma już framów
        if ($game->getFrames()->count() > 0) {
            throw new \RuntimeException('Game already has frames generated');
        }

        // Dwumecz - 2 gry × 10 framów = 20 framów
        for ($gameNum = 1; $gameNum <= 2; $gameNum++) {
            $lane = $startLane + ($gameNum - 1);

            for ($frameNum = 1; $frameNum <= 10; $frameNum++) {
                $frame = new Frame();
                $frame->setGame($game);
                $frame->setFrameNumber($frameNum);
                $frame->setLaneNumber($lane);
                $frame->setGameNumber($gameNum);

                // Dodaj graczy (bez teamów dla gry indywidualnej)
                $frame->addTeamAPlayer($player1);
                $frame->addTeamBPlayer($player2);

                $game->addFrame($frame);
                $this->em->persist($frame);
            }
        }
    }

    /**
     * Generuje framy dla istniejącego meczu drużynowego
     */
    public function generateTeamFrames(
        Game $game,
        Team $team1,
        Team $team2,
        array $team1Players,
        array $team2Players,
        int $startLane = 1
    ): void {
        // Sprawdź czy mecz nie ma już framów
        if ($game->getFrames()->count() > 0) {
            throw new \RuntimeException('Game already has frames generated');
        }

        if (count($team1Players) !== 3 || count($team2Players) !== 3) {
            throw new \InvalidArgumentException('Each team must have exactly 3 players');
        }

        // 3 pary zawodników
        for ($pairIndex = 0; $pairIndex < 3; $pairIndex++) {
            $player1 = $team1Players[$pairIndex];
            $player2 = $team2Players[$pairIndex];
            $lane = $startLane + $pairIndex;

            // Dwumecz dla każdej pary (2 gry z zamianą torów)
            for ($gameNum = 1; $gameNum <= 2; $gameNum++) {
                for ($frameNum = 1; $frameNum <= 10; $frameNum++) {
                    $frame = new Frame();
                    $frame->setGame($game);
                    $frame->setFrameNumber($frameNum);
                    $frame->setLaneNumber($lane);
                    $frame->setGameNumber($gameNum);
                    $frame->setTeamA($team1);
                    $frame->setTeamB($team2);

                    // Dodaj graczy tej pary
                    $frame->addTeamAPlayer($player1);
                    $frame->addTeamBPlayer($player2);

                    $game->addFrame($frame);
                    $this->em->persist($frame);
                }
            }
        }
    }

    /**
     * Generuje framy na podstawie danych z requesta (dla użycia w kontrolerze)
     */
    public function generateFramesFromRequest(Game $game, array $data): void
    {
        $gameType = $data['gameType'] ?? null;

        if ($gameType === 'individual') {
            $this->generateIndividualFromRequestData($game, $data['individual'] ?? []);
        } elseif ($gameType === 'team') {
            $this->generateTeamFromRequestData($game, $data['team'] ?? []);
        } else {
            throw new \InvalidArgumentException('Invalid game type');
        }

        $this->em->flush();
    }

    /**
     * Generuje mecz indywidualny z danych z requesta
     */
    private function generateIndividualFromRequestData(Game $game, array $individualData): void
    {
        $playerAId = $individualData['playerA'] ?? null;
        $playerBId = $individualData['playerB'] ?? null;
        $startLane = (int) ($individualData['startLane'] ?? 1);

        if (!$playerAId || !$playerBId) {
            throw new \InvalidArgumentException('Musisz wybrać obu graczy');
        }

        $playerA = $this->em->getRepository(User::class)->find($playerAId);
        $playerB = $this->em->getRepository(User::class)->find($playerBId);

        if (!$playerA || !$playerB) {
            throw new \InvalidArgumentException('Nie znaleziono wybranych graczy');
        }

        if ($playerA === $playerB) {
            throw new \InvalidArgumentException('Gracze muszą być różni');
        }

        $this->generateIndividualFrames($game, $playerA, $playerB, $startLane);
    }

    /**
     * Generuje mecz drużynowy z danych z requesta
     */
    private function generateTeamFromRequestData(Game $game, array $teamData): void
    {
        if (!$game->getTeamA() || !$game->getTeamB()) {
            throw new \InvalidArgumentException('Mecz musi mieć przypisane drużyny');
        }

        $startLane = (int) ($teamData['startLane'] ?? 1);

        // Pobierz graczy drużyny A
        $teamAPlayers = [];
        for ($i = 1; $i <= 3; $i++) {
            $playerId = $teamData['teamA']['player' . $i] ?? null;
            if (!$playerId) {
                throw new \InvalidArgumentException('Musisz wybrać wszystkich zawodników drużyny A');
            }
            $player = $this->em->getRepository(User::class)->find($playerId);
            if (!$player) {
                throw new \InvalidArgumentException('Nie znaleziono zawodnika drużyny A');
            }
            $teamAPlayers[] = $player;
        }

        // Pobierz graczy drużyny B
        $teamBPlayers = [];
        for ($i = 1; $i <= 3; $i++) {
            $playerId = $teamData['teamB']['player' . $i] ?? null;
            if (!$playerId) {
                throw new \InvalidArgumentException('Musisz wybrać wszystkich zawodników drużyny B');
            }
            $player = $this->em->getRepository(User::class)->find($playerId);
            if (!$player) {
                throw new \InvalidArgumentException('Nie znaleziono zawodnika drużyny B');
            }
            $teamBPlayers[] = $player;
        }

        $this->generateTeamFrames(
            $game,
            $game->getTeamA(),
            $game->getTeamB(),
            $teamAPlayers,
            $teamBPlayers,
            $startLane
        );
    }

    /**
     * Usuwa wszystkie framy z meczu (np. do regeneracji)
     */
    public function clearGameFrames(Game $game): void
    {
        foreach ($game->getFrames() as $frame) {
            $this->em->remove($frame);
        }

        $this->em->flush();
    }

    /**
     * Sprawdza czy mecz jest gotowy do rozpoczęcia (ma framy i graczy)
     */
    public function isGameReady(Game $game): bool
    {
        if ($game->getFrames()->isEmpty()) {
            return false;
        }

        // Sprawdź czy każdy fram ma graczy
        foreach ($game->getFrames() as $frame) {
            if ($frame->getTeamAPlayers()->isEmpty() || $frame->getTeamBPlayers()->isEmpty()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Pobiera informacje o strukturze meczu
     */
    public function getGameStructureInfo(Game $game): array
    {
        $frames = $game->getFrames();

        if ($frames->isEmpty()) {
            return [
                'hasFrames' => false,
                'framesCount' => 0,
                'gamesCount' => 0,
                'lanesCount' => 0,
                'playersCount' => 0,
            ];
        }

        $gameNumbers = [];
        $laneNumbers = [];
        $players = [];

        foreach ($frames as $frame) {
            $gameNumbers[$frame->getGameNumber()] = true;
            $laneNumbers[$frame->getLaneNumber()] = true;

            foreach ($frame->getTeamAPlayers() as $player) {
                $players[$player->getId()] = $player;
            }
            foreach ($frame->getTeamBPlayers() as $player) {
                $players[$player->getId()] = $player;
            }
        }

        return [
            'hasFrames' => true,
            'framesCount' => $frames->count(),
            'gamesCount' => count($gameNumbers),
            'lanesCount' => count($laneNumbers),
            'playersCount' => count($players),
            'players' => array_values($players),
            'lanes' => array_keys($laneNumbers),
            'isTeamGame' => $game->isTeamGame(),
        ];
    }

    /**
     * Waliduje dane do generowania meczu
     */
    public function validateGameData(array $data): array
    {
        $errors = [];

        if (!isset($data['gameType'])) {
            $errors[] = 'Nie wybrano typu gry';
            return $errors;
        }

        if ($data['gameType'] === 'individual') {
            if (empty($data['individual']['playerA'])) {
                $errors[] = 'Nie wybrano gracza A';
            }
            if (empty($data['individual']['playerB'])) {
                $errors[] = 'Nie wybrano gracza B';
            }
            if ($data['individual']['playerA'] === $data['individual']['playerB']) {
                $errors[] = 'Gracze muszą być różni';
            }
        } elseif ($data['gameType'] === 'team') {
            for ($i = 1; $i <= 3; $i++) {
                if (empty($data['team']['teamA']['player' . $i])) {
                    $errors[] = "Nie wybrano zawodnika $i drużyny A";
                }
                if (empty($data['team']['teamB']['player' . $i])) {
                    $errors[] = "Nie wybrano zawodnika $i drużyny B";
                }
            }

            // Sprawdź czy gracze się nie powtarzają w drużynie
            $teamAPlayers = [
                $data['team']['teamA']['player1'] ?? null,
                $data['team']['teamA']['player2'] ?? null,
                $data['team']['teamA']['player3'] ?? null,
            ];
            $teamBPlayers = [
                $data['team']['teamB']['player1'] ?? null,
                $data['team']['teamB']['player2'] ?? null,
                $data['team']['teamB']['player3'] ?? null,
            ];

            if (count($teamAPlayers) !== count(array_unique($teamAPlayers))) {
                $errors[] = 'Gracze w drużynie A się powtarzają';
            }
            if (count($teamBPlayers) !== count(array_unique($teamBPlayers))) {
                $errors[] = 'Gracze w drużynie B się powtarzają';
            }
        }

        return $errors;
    }
}
