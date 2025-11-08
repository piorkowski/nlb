<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Frame;
use App\Entity\Game;
use App\Entity\Roll;
use App\Entity\User;
use App\Service\RollService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class RollServiceTest extends TestCase
{
    private RollService $rollService;
    private EntityManagerInterface $em;
    private Frame $frame;
    private User $player;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->rollService = new RollService($this->em);

        $this->player = new User();
        $this->frame = new Frame();
        $this->frame->setFrameNumber(1);
        $this->frame->setLaneNumber(1);
        $this->frame->setGameNumber(1);
        $this->frame->addTeamAPlayer($this->player);
        
        $game = new Game();
        $this->frame->setGame($game);
    }

    public function testAddRollStrike(): void
    {
        $this->em->expects($this->once())
            ->method('persist');

        $roll = $this->rollService->addRoll($this->frame, $this->player, 1, 10);

        $this->assertEquals(10, $roll->getPinsKnocked());
        $this->assertEquals(1, $roll->getRollNumber());
        $this->assertTrue($roll->isStrike());
        $this->assertFalse($roll->isSpare());
    }

    public function testAddRollSpare(): void
    {
        $this->em->expects($this->exactly(2))
            ->method('persist');

        $roll1 = $this->rollService->addRoll($this->frame, $this->player, 1, 7);
        $roll2 = $this->rollService->addRoll($this->frame, $this->player, 2, 3);

        $this->assertFalse($roll1->isStrike());
        $this->assertFalse($roll1->isSpare());
        $this->assertTrue($roll2->isSpare());
    }

    public function testAddRollInvalidPins(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->rollService->addRoll($this->frame, $this->player, 1, 11);
    }

    public function testAddRollInvalidRollNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->rollService->addRoll($this->frame, $this->player, 4, 5);
    }

    public function testAddRollExceedsTenPins(): void
    {
        $roll1 = $this->rollService->addRoll($this->frame, $this->player, 1, 8);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->rollService->addRoll($this->frame, $this->player, 2, 5);
    }

    public function testIsPlayerFrameCompleteStrike(): void
    {
        $roll = new Roll();
        $roll->setFrame($this->frame);
        $roll->setPlayer($this->player);
        $roll->setRollNumber(1);
        $roll->setPinsKnocked(10);
        $this->frame->addRoll($roll);

        $this->assertTrue($this->rollService->isPlayerFrameComplete($this->frame, $this->player));
    }

    public function testIsPlayerFrameCompleteTwoRolls(): void
    {
        $roll1 = new Roll();
        $roll1->setFrame($this->frame);
        $roll1->setPlayer($this->player);
        $roll1->setRollNumber(1);
        $roll1->setPinsKnocked(5);
        $this->frame->addRoll($roll1);

        $this->assertFalse($this->rollService->isPlayerFrameComplete($this->frame, $this->player));

        $roll2 = new Roll();
        $roll2->setFrame($this->frame);
        $roll2->setPlayer($this->player);
        $roll2->setRollNumber(2);
        $roll2->setPinsKnocked(3);
        $this->frame->addRoll($roll2);

        $this->assertTrue($this->rollService->isPlayerFrameComplete($this->frame, $this->player));
    }

    public function testIsPlayerFrameCompleteTenthFrame(): void
    {
        $this->frame->setFrameNumber(10);

        $roll1 = new Roll();
        $roll1->setFrame($this->frame);
        $roll1->setPlayer($this->player);
        $roll1->setRollNumber(1);
        $roll1->setPinsKnocked(10);
        $this->frame->addRoll($roll1);

        $this->assertFalse($this->rollService->isPlayerFrameComplete($this->frame, $this->player));

        $roll2 = new Roll();
        $roll2->setFrame($this->frame);
        $roll2->setPlayer($this->player);
        $roll2->setRollNumber(2);
        $roll2->setPinsKnocked(10);
        $this->frame->addRoll($roll2);

        $this->assertFalse($this->rollService->isPlayerFrameComplete($this->frame, $this->player));

        $roll3 = new Roll();
        $roll3->setFrame($this->frame);
        $roll3->setPlayer($this->player);
        $roll3->setRollNumber(3);
        $roll3->setPinsKnocked(10);
        $this->frame->addRoll($roll3);

        $this->assertTrue($this->rollService->isPlayerFrameComplete($this->frame, $this->player));
    }

    public function testGetPlayerRollsArray(): void
    {
        $roll1 = new Roll();
        $roll1->setFrame($this->frame);
        $roll1->setPlayer($this->player);
        $roll1->setRollNumber(1);
        $roll1->setPinsKnocked(7);
        $this->frame->addRoll($roll1);

        $roll2 = new Roll();
        $roll2->setFrame($this->frame);
        $roll2->setPlayer($this->player);
        $roll2->setRollNumber(2);
        $roll2->setPinsKnocked(2);
        $this->frame->addRoll($roll2);

        $rolls = $this->rollService->getPlayerRollsArray($this->frame, $this->player);

        $this->assertCount(2, $rolls);
        $this->assertEquals(7, $rolls[1]);
        $this->assertEquals(2, $rolls[2]);
    }
}
