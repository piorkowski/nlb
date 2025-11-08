<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Frame;
use App\Entity\Game;
use App\Entity\User;
use App\Entity\Roll;
use PHPUnit\Framework\TestCase;

class FrameTest extends TestCase
{
    private Frame $frame;
    private User $player1;
    private User $player2;

    protected function setUp(): void
    {
        $this->frame = new Frame();
        $this->player1 = new User();
        $this->player2 = new User();
        
        $game = new Game();
        $this->frame->setGame($game);
        $this->frame->setFrameNumber(1);
        $this->frame->setLaneNumber(1);
        $this->frame->setGameNumber(1);
        
        $this->frame->addTeamAPlayer($this->player1);
        $this->frame->addTeamBPlayer($this->player2);
    }

    public function testStrikeDetection(): void
    {
        $roll = new Roll();
        $roll->setFrame($this->frame);
        $roll->setPlayer($this->player1);
        $roll->setRollNumber(1);
        $roll->setPinsKnocked(10);
        $this->frame->addRoll($roll);

        $this->assertTrue($this->frame->isPlayerStrike($this->player1));
        $this->assertFalse($this->frame->isPlayerSpare($this->player1));
    }

    public function testSpareDetection(): void
    {
        $roll1 = new Roll();
        $roll1->setFrame($this->frame);
        $roll1->setPlayer($this->player1);
        $roll1->setRollNumber(1);
        $roll1->setPinsKnocked(7);
        $this->frame->addRoll($roll1);

        $roll2 = new Roll();
        $roll2->setFrame($this->frame);
        $roll2->setPlayer($this->player1);
        $roll2->setRollNumber(2);
        $roll2->setPinsKnocked(3);
        $this->frame->addRoll($roll2);

        $this->assertFalse($this->frame->isPlayerStrike($this->player1));
        $this->assertTrue($this->frame->isPlayerSpare($this->player1));
    }

    public function testCalculatePlayerScoreSimple(): void
    {
        $roll1 = new Roll();
        $roll1->setFrame($this->frame);
        $roll1->setPlayer($this->player1);
        $roll1->setRollNumber(1);
        $roll1->setPinsKnocked(5);
        $this->frame->addRoll($roll1);

        $roll2 = new Roll();
        $roll2->setFrame($this->frame);
        $roll2->setPlayer($this->player1);
        $roll2->setRollNumber(2);
        $roll2->setPinsKnocked(3);
        $this->frame->addRoll($roll2);

        $score = $this->frame->calculatePlayerScore($this->player1, null, null);
        $this->assertEquals(8, $score);
    }

    public function testCalculatePlayerScoreWithStrikeBonus(): void
    {
        $roll1 = new Roll();
        $roll1->setFrame($this->frame);
        $roll1->setPlayer($this->player1);
        $roll1->setRollNumber(1);
        $roll1->setPinsKnocked(10);
        $this->frame->addRoll($roll1);

        $nextFrame = new Frame();
        $nextFrame->setFrameNumber(2);
        $nextRoll1 = new Roll();
        $nextRoll1->setFrame($nextFrame);
        $nextRoll1->setPlayer($this->player1);
        $nextRoll1->setRollNumber(1);
        $nextRoll1->setPinsKnocked(5);
        $nextFrame->addRoll($nextRoll1);

        $nextRoll2 = new Roll();
        $nextRoll2->setFrame($nextFrame);
        $nextRoll2->setPlayer($this->player1);
        $nextRoll2->setRollNumber(2);
        $nextRoll2->setPinsKnocked(3);
        $nextFrame->addRoll($nextRoll2);

        $score = $this->frame->calculatePlayerScore($this->player1, $nextFrame, null);
        $this->assertEquals(18, $score);
    }

    public function testCalculatePlayerScoreWithSpareBonus(): void
    {
        $roll1 = new Roll();
        $roll1->setFrame($this->frame);
        $roll1->setPlayer($this->player1);
        $roll1->setRollNumber(1);
        $roll1->setPinsKnocked(7);
        $this->frame->addRoll($roll1);

        $roll2 = new Roll();
        $roll2->setFrame($this->frame);
        $roll2->setPlayer($this->player1);
        $roll2->setRollNumber(2);
        $roll2->setPinsKnocked(3);
        $this->frame->addRoll($roll2);

        $nextFrame = new Frame();
        $nextFrame->setFrameNumber(2);
        $nextRoll1 = new Roll();
        $nextRoll1->setFrame($nextFrame);
        $nextRoll1->setPlayer($this->player1);
        $nextRoll1->setRollNumber(1);
        $nextRoll1->setPinsKnocked(5);
        $nextFrame->addRoll($nextRoll1);

        $score = $this->frame->calculatePlayerScore($this->player1, $nextFrame, null);
        $this->assertEquals(15, $score);
    }

    public function testCalculatePlayerScoreTenthFrame(): void
    {
        $this->frame->setFrameNumber(10);
        
        $roll1 = new Roll();
        $roll1->setFrame($this->frame);
        $roll1->setPlayer($this->player1);
        $roll1->setRollNumber(1);
        $roll1->setPinsKnocked(10);
        $this->frame->addRoll($roll1);

        $roll2 = new Roll();
        $roll2->setFrame($this->frame);
        $roll2->setPlayer($this->player1);
        $roll2->setRollNumber(2);
        $roll2->setPinsKnocked(10);
        $this->frame->addRoll($roll2);

        $roll3 = new Roll();
        $roll3->setFrame($this->frame);
        $roll3->setPlayer($this->player1);
        $roll3->setRollNumber(3);
        $roll3->setPinsKnocked(10);
        $this->frame->addRoll($roll3);

        $score = $this->frame->calculatePlayerScore($this->player1, null, null);
        $this->assertEquals(30, $score);
    }

    public function testGetPlayerTotalPins(): void
    {
        $roll1 = new Roll();
        $roll1->setFrame($this->frame);
        $roll1->setPlayer($this->player1);
        $roll1->setRollNumber(1);
        $roll1->setPinsKnocked(7);
        $this->frame->addRoll($roll1);

        $roll2 = new Roll();
        $roll2->setFrame($this->frame);
        $roll2->setPlayer($this->player1);
        $roll2->setRollNumber(2);
        $roll2->setPinsKnocked(2);
        $this->frame->addRoll($roll2);

        $this->assertEquals(9, $this->frame->getPlayerTotalPins($this->player1));
    }
}
