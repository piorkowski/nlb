<?php

namespace App\Entity;

use App\Entity\Trait\TimestampTrait;
use App\Repository\RollRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RollRepository::class)]
#[ORM\Table(name: 'roll')]
#[ORM\HasLifecycleCallbacks]
class Roll
{
    use TimestampTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'playerARolls')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Frame $frame = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $player = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $rollNumber; // 1, 2, lub 3 (dla 10 framu)

    #[ORM\Column(type: Types::SMALLINT)]
    private int $pinsKnocked = 0; // 0-10

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isStrike = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isSpare = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFrame(): ?Frame
    {
        return $this->frame;
    }

    public function setFrame(?Frame $frame): self
    {
        $this->frame = $frame;
        return $this;
    }

    public function getPlayer(): ?User
    {
        return $this->player;
    }

    public function setPlayer(?User $player): self
    {
        $this->player = $player;
        return $this;
    }

    public function getRollNumber(): int
    {
        return $this->rollNumber;
    }

    public function setRollNumber(int $rollNumber): self
    {
        if ($rollNumber < 1 || $rollNumber > 3) {
            throw new \InvalidArgumentException('Roll number must be between 1 and 3');
        }
        $this->rollNumber = $rollNumber;
        return $this;
    }

    public function getPinsKnocked(): int
    {
        return $this->pinsKnocked;
    }

    public function setPinsKnocked(int $pinsKnocked): self
    {
        if ($pinsKnocked < 0 || $pinsKnocked > 10) {
            throw new \InvalidArgumentException('Pins knocked must be between 0 and 10');
        }
        $this->pinsKnocked = $pinsKnocked;
        $this->updateFlags();
        return $this;
    }

    public function isStrike(): bool
    {
        return $this->isStrike;
    }

    public function isSpare(): bool
    {
        return $this->isSpare;
    }

    private function updateFlags(): void
    {
        if ($this->rollNumber === 1 && $this->pinsKnocked === 10) {
            $this->isStrike = true;
            $this->isSpare = false;
            return;
        }

        $this->isStrike = false;

        if ($this->rollNumber === 2 && $this->frame) {
            $previousRoll = $this->frame->getPlayerARoll(1) ?? $this->frame->getPlayerBRoll(1);
            if ($previousRoll && ($previousRoll->getPinsKnocked() + $this->pinsKnocked) === 10) {
                $this->isSpare = true;
                return;
            }
        }

        $this->isSpare = false;
    }

    public function __toString(): string
    {
        $result = (string)$this->pinsKnocked;

        if ($this->isStrike) {
            $result = 'X';
        } elseif ($this->isSpare) {
            $result = '/';
        } elseif ($this->pinsKnocked === 0) {
            $result = '-';
        }

        return sprintf('Roll #%d: %s', $this->rollNumber, $result);
    }
}
