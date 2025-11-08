<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Frame;
use App\Entity\Game;
use App\Entity\GameStatus;
use App\Entity\League;
use App\Entity\Roll;
use App\Entity\Team;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class GameTest extends TestCase
{
    private Game $game;
    private User $player1;
    private User $player2;

    protected function setUp(): void
    {
        $this->game = new Game();
        $this->game->setStatus(GameStatus::DRAFT);
        $this->game->setGameDate(new \DateTimeImmutable());

        $league = new League();
        $this->game->setLeague($league);

        $this->player1 = new User();
        $this->player2 = new User();
    }

    public function testIsTeamGame(): void
    {
        $this->assertFalse($this->game->isTeamGame());

        $teamA = new Team();
        $teamB = new Team();
        $this->game->setTeamA($teamA);
        $this->game->setTeamB($teamB);

        $this->assertTrue($this->game->isTeamGame());
    }

    public function testIsIndividualGame(): void
    {
        $this->assertTrue($this->game->isIndividualGame());

        $teamA = new Team();
        $teamB = new Team();
        $this->game->setTeamA($teamA);
        $this->game->setTeamB($teamB);

        $this->assertFalse($this->game->isIndividualGame());
    }

    public function testGetAllPlayers(): void
    {
        $frame = new Frame();
        $frame->setGame($this->game);
        $frame->setFrameNumber(1);
        $frame->setLaneNumber(1);
        $frame->setGameNumber(1);
        $frame->addTeamAPlayer($this->player1);
        $frame->addTeamBPlayer($this->player2);

        $this->game->addFrame($frame);

        $players = $this->game->getAllPlayers();
        $this->assertCount(2, $players);
    }

    public function testCalculatePointsIndividualGameWin(): void
    {
        $this->createCompleteIndividualGame(150, 120);

        $this->game->setStatus(GameStatus::FINISHED);
        $this->game->calculatePoints();

        $this->assertEquals(4, $this->game->getTeamAPoints());
        $this->assertEquals(0, $this->game->getTeamBPoints());
    }

    public function testCalculatePointsIndividualGameDraw(): void
    {
        $this->createCompleteIndividualGame(150, 150);

        $this->game->setStatus(GameStatus::FINISHED);
        $this->game->calculatePoints();

        $this->assertEquals(2, $this->game->getTeamAPoints());
        $this->assertEquals(2, $this->game->getTeamBPoints());
    }

    public function testCalculatePointsIndividualGameSplit(): void
    {
        for ($gameNumber = 1; $gameNumber <= 2; $gameNumber++) {
            $pinsPlayer1 = $gameNumber === 1 ? 8 : 6;
            $pinsPlayer2 = $gameNumber === 1 ? 6 : 8;

            for ($frameNumber = 1; $frameNumber <= 10; $frameNumber++) {
                $frame = new Frame();
                $frame->setGame($this->game);
                $frame->setFrameNumber($frameNumber);
                $frame->setLaneNumber($gameNumber);
                $frame->setGameNumber($gameNumber);
                $frame->addTeamAPlayer($this->player1);
                $frame->addTeamBPlayer($this->player2);

                $roll1 = new Roll();
                $roll1->setFrame($frame);
                $roll1->setPlayer($this->player1);
                $roll1->setRollNumber(1);
                $roll1->setPinsKnocked($pinsPlayer1);
                $frame->addRoll($roll1);

                if ($pinsPlayer1 < 10) {
                    $roll1b = new Roll();
                    $roll1b->setFrame($frame);
                    $roll1b->setPlayer($this->player1);
                    $roll1b->setRollNumber(2);
                    $roll1b->setPinsKnocked(0);
                    $frame->addRoll($roll1b);
                }

                $roll2 = new Roll();
                $roll2->setFrame($frame);
                $roll2->setPlayer($this->player2);
                $roll2->setRollNumber(1);
                $roll2->setPinsKnocked($pinsPlayer2);
                $frame->addRoll($roll2);

                if ($pinsPlayer2 < 10) {
                    $roll2b = new Roll();
                    $roll2b->setFrame($frame);
                    $roll2b->setPlayer($this->player2);
                    $roll2b->setRollNumber(2);
                    $roll2b->setPinsKnocked(0);
                    $frame->addRoll($roll2b);
                }

                $this->game->addFrame($frame);
            }
        }

        $this->game->setStatus(GameStatus::FINISHED);
        $this->game->calculatePoints();

        $this->assertEquals(2, $this->game->getTeamAPoints());
        $this->assertEquals(2, $this->game->getTeamBPoints());
    }

    public function testIsComplete(): void
    {
        $this->assertFalse($this->game->isComplete());

        $frame = new Frame();
        $frame->setGame($this->game);
        $frame->setFrameNumber(1);
        $frame->setLaneNumber(1);
        $frame->setGameNumber(1);
        $frame->addTeamAPlayer($this->player1);
        $frame->addTeamBPlayer($this->player2);

        $roll1 = new Roll();
        $roll1->setFrame($frame);
        $roll1->setPlayer($this->player1);
        $roll1->setRollNumber(1);
        $roll1->setPinsKnocked(10);
        $frame->addRoll($roll1);

        $roll2 = new Roll();
        $roll2->setFrame($frame);
        $roll2->setPlayer($this->player2);
        $roll2->setRollNumber(1);
        $roll2->setPinsKnocked(10);
        $frame->addRoll($roll2);

        $this->game->addFrame($frame);

        $this->assertTrue($this->game->isComplete());
    }

    private function createCompleteIndividualGame(int $score1, int $score2): void
    {
        for ($gameNumber = 1; $gameNumber <= 2; $gameNumber++) {
            for ($frameNumber = 1; $frameNumber <= 10; $frameNumber++) {
                $frame = new Frame();
                $frame->setGame($this->game);
                $frame->setFrameNumber($frameNumber);
                $frame->setLaneNumber($gameNumber);
                $frame->setGameNumber($gameNumber);
                $frame->addTeamAPlayer($this->player1);
                $frame->addTeamBPlayer($this->player2);

                $pins1 = (int)($score1 / 10);
                $pins2 = (int)($score2 / 10);

                $roll1 = new Roll();
                $roll1->setFrame($frame);
                $roll1->setPlayer($this->player1);
                $roll1->setRollNumber(1);
                $roll1->setPinsKnocked($pins1);
                $frame->addRoll($roll1);

                if ($pins1 < 10) {
                    $roll1b = new Roll();
                    $roll1b->setFrame($frame);
                    $roll1b->setPlayer($this->player1);
                    $roll1b->setRollNumber(2);
                    $roll1b->setPinsKnocked(0);
                    $frame->addRoll($roll1b);
                }

                $roll2 = new Roll();
                $roll2->setFrame($frame);
                $roll2->setPlayer($this->player2);
                $roll2->setRollNumber(1);
                $roll2->setPinsKnocked($pins2);
                $frame->addRoll($roll2);

                if ($pins2 < 10) {
                    $roll2b = new Roll();
                    $roll2b->setFrame($frame);
                    $roll2b->setPlayer($this->player2);
                    $roll2b->setRollNumber(2);
                    $roll2b->setPinsKnocked(0);
                    $frame->addRoll($roll2b);
                }

                $this->game->addFrame($frame);
            }
        }
    }
}
