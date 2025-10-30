<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\TimestampTrait;
use App\Repository\FrameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FrameRepository::class)]
#[ORM\Table(name: 'frame')]
#[ORM\HasLifecycleCallbacks]
class Frame
{
    use TimestampTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'frames')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private int $frameNumber; // 1-10

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private int $laneNumber; // Numer toru

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private int $gameNumber; // 1 lub 2 (dwumecz - zmiana toru)

    // Drużyny (opcjonalne - tylko dla gier drużynowych)
    #[ORM\ManyToOne]
    private ?Team $teamA = null;

    #[ORM\ManyToOne]
    private ?Team $teamB = null;

    // Zawodnicy Team A (może być 1 gracz dla individual, 3 dla team)
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'frame_team_a_players')]
    #[ORM\JoinColumn(name: 'frame_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private Collection $teamAPlayers;

    // Zawodnicy Team B
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'frame_team_b_players')]
    #[ORM\JoinColumn(name: 'frame_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private Collection $teamBPlayers;

    // Wszystkie rzuty w tym framie
    #[ORM\OneToMany(targetEntity: Roll::class, mappedBy: 'frame', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['player' => 'ASC', 'rollNumber' => 'ASC'])]
    private Collection $rolls;

    public function __construct()
    {
        $this->teamAPlayers = new ArrayCollection();
        $this->teamBPlayers = new ArrayCollection();
        $this->rolls = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): self
    {
        $this->game = $game;
        return $this;
    }

    public function getFrameNumber(): int
    {
        return $this->frameNumber;
    }

    public function setFrameNumber(int $frameNumber): self
    {
        $this->frameNumber = $frameNumber;
        return $this;
    }

    public function getLaneNumber(): int
    {
        return $this->laneNumber;
    }

    public function setLaneNumber(int $laneNumber): self
    {
        $this->laneNumber = $laneNumber;
        return $this;
    }

    public function getGameNumber(): int
    {
        return $this->gameNumber;
    }

    public function setGameNumber(int $gameNumber): self
    {
        $this->gameNumber = $gameNumber;
        return $this;
    }

    public function getTeamA(): ?Team
    {
        return $this->teamA;
    }

    public function setTeamA(?Team $teamA): self
    {
        $this->teamA = $teamA;
        return $this;
    }

    public function getTeamB(): ?Team
    {
        return $this->teamB;
    }

    public function setTeamB(?Team $teamB): self
    {
        $this->teamB = $teamB;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getTeamAPlayers(): Collection
    {
        return $this->teamAPlayers;
    }

    public function addTeamAPlayer(User $player): self
    {
        if (!$this->teamAPlayers->contains($player)) {
            $this->teamAPlayers->add($player);
        }
        return $this;
    }

    public function removeTeamAPlayer(User $player): self
    {
        $this->teamAPlayers->removeElement($player);
        return $this;
    }

    public function clearTeamAPlayers(): self
    {
        $this->teamAPlayers->clear();
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getTeamBPlayers(): Collection
    {
        return $this->teamBPlayers;
    }

    public function addTeamBPlayer(User $player): self
    {
        if (!$this->teamBPlayers->contains($player)) {
            $this->teamBPlayers->add($player);
        }
        return $this;
    }

    public function removeTeamBPlayer(User $player): self
    {
        $this->teamBPlayers->removeElement($player);
        return $this;
    }

    public function clearTeamBPlayers(): self
    {
        $this->teamBPlayers->clear();
        return $this;
    }

    /**
     * @return Collection<int, Roll>
     */
    public function getRolls(): Collection
    {
        return $this->rolls;
    }

    public function addRoll(Roll $roll): self
    {
        if (!$this->rolls->contains($roll)) {
            $this->rolls->add($roll);
            $roll->setFrame($this);
        }
        return $this;
    }

    public function removeRoll(Roll $roll): self
    {
        if ($this->rolls->removeElement($roll)) {
            if ($roll->getFrame() === $this) {
                $roll->setFrame(null);
            }
        }
        return $this;
    }

    // === Helper methods ===

    /**
     * Pobiera rzuty konkretnego gracza
     */
    public function getPlayerRolls(User $player): Collection
    {
        return $this->rolls->filter(fn(Roll $roll) => $roll->getPlayer() === $player);
    }

    /**
     * Pobiera wszystkie rzuty Team A
     */
    public function getTeamARolls(): Collection
    {
        return $this->rolls->filter(function(Roll $roll) {
            return $this->teamAPlayers->contains($roll->getPlayer());
        });
    }

    /**
     * Pobiera wszystkie rzuty Team B
     */
    public function getTeamBRolls(): Collection
    {
        return $this->rolls->filter(function(Roll $roll) {
            return $this->teamBPlayers->contains($roll->getPlayer());
        });
    }

    /**
     * Pobiera konkretny rzut gracza
     */
    public function getPlayerRoll(User $player, int $rollNumber): ?Roll
    {
        return $this->rolls->filter(
            fn(Roll $r) => $r->getPlayer() === $player && $r->getRollNumber() === $rollNumber
        )->first() ?: null;
    }

    /**
     * Pobiera sumę kręgli gracza w tym framie
     */
    public function getPlayerTotalPins(User $player): int
    {
        return $this->getPlayerRolls($player)
            ->map(fn(Roll $r) => $r->getPinsKnocked())
            ->reduce(fn($carry, $pins) => $carry + $pins, 0);
    }

    /**
     * Pobiera tablicę [rollNumber => pinsKnocked] dla gracza
     */
    public function getPlayerPins(User $player): array
    {
        $pins = [];
        foreach ($this->getPlayerRolls($player) as $roll) {
            $pins[$roll->getRollNumber()] = $roll->getPinsKnocked();
        }
        ksort($pins);
        return $pins;
    }

    // === Scoring logic dla pojedynczego gracza ===

    public function isPlayerStrike(User $player): bool
    {
        $firstRoll = $this->getPlayerRoll($player, 1);
        return $firstRoll && $firstRoll->getPinsKnocked() === 10;
    }

    public function isPlayerSpare(User $player): bool
    {
        if ($this->isPlayerStrike($player)) {
            return false;
        }

        $roll1 = $this->getPlayerRoll($player, 1);
        $roll2 = $this->getPlayerRoll($player, 2);

        return $roll1 && $roll2
            && ($roll1->getPinsKnocked() + $roll2->getPinsKnocked()) === 10;
    }

    public function calculatePlayerScore(User $player, ?Frame $nextFrame = null, ?Frame $nextNextFrame = null): int
    {
        $score = $this->getPlayerTotalPins($player);

        if ($this->frameNumber < 10 && $nextFrame && $this->isPlayerStrike($player)) {
            $nextRoll1 = $nextFrame->getPlayerRoll($player, 1);
            $score += $nextRoll1?->getPinsKnocked() ?? 0;

            if ($nextNextFrame && $nextFrame->isPlayerStrike($player)) {
                $nextNextRoll1 = $nextNextFrame->getPlayerRoll($player, 1);
                $score += $nextNextRoll1?->getPinsKnocked() ?? 0;
            } else {
                $nextRoll2 = $nextFrame->getPlayerRoll($player, 2);
                $score += $nextRoll2?->getPinsKnocked() ?? 0;
            }
        }

        if ($this->frameNumber < 10 && $nextFrame && $this->isPlayerSpare($player)) {
            $nextRoll1 = $nextFrame->getPlayerRoll($player, 1);
            $score += $nextRoll1?->getPinsKnocked() ?? 0;
        }

        return $score;
    }

    public function getTeamAScore(?Frame $nextFrame = null, ?Frame $nextNextFrame = null): int
    {
        $total = 0;
        foreach ($this->teamAPlayers as $player) {
            $total += $this->calculatePlayerScore($player, $nextFrame, $nextNextFrame);
        }
        return $total;
    }

    public function getTeamBScore(?Frame $nextFrame = null, ?Frame $nextNextFrame = null): int
    {
        $total = 0;
        foreach ($this->teamBPlayers as $player) {
            $total += $this->calculatePlayerScore($player, $nextFrame, $nextNextFrame);
        }
        return $total;
    }

    public function isTeamAComplete(): bool
    {
        if ($this->teamAPlayers->isEmpty()) {
            return false;
        }

        return $this->checkIsTeamFinished($this->teamAPlayers);
    }

    public function isTeamBComplete(): bool
    {
        if ($this->teamBPlayers->isEmpty()) {
            return false;
        }

        return $this->checkIsTeamFinished($this->teamBPlayers);
    }

    public function isComplete(): bool
    {
        return $this->isTeamAComplete() && $this->isTeamBComplete();
    }

    public function getTeamAStrikes(): int
    {
        $strikes = 0;
        foreach ($this->teamAPlayers as $player) {
            if ($this->isPlayerStrike($player)) {
                $strikes++;
            }
        }
        return $strikes;
    }

    public function getTeamASpares(): int
    {
        $spares = 0;
        foreach ($this->teamAPlayers as $player) {
            if ($this->isPlayerSpare($player)) {
                $spares++;
            }
        }
        return $spares;
    }

    public function getTeamBStrikes(): int
    {
        $strikes = 0;
        foreach ($this->teamBPlayers as $player) {
            if ($this->isPlayerStrike($player)) {
                $strikes++;
            }
        }
        return $strikes;
    }

    public function getTeamBSpares(): int
    {
        $spares = 0;
        foreach ($this->teamBPlayers as $player) {
            if ($this->isPlayerSpare($player)) {
                $spares++;
            }
        }
        return $spares;
    }

    public function __toString(): string
    {
        return sprintf('Frame %d (Game %d, Lane %d)', $this->frameNumber, $this->gameNumber, $this->laneNumber);
    }


    private function checkIsTeamFinished(Collection $teamPlayers): bool
    {
        foreach ($teamPlayers as $player) {
            $rollsCount = $this->getPlayerRolls($player)->count();

            if ($this->frameNumber === 10) {
                if ($rollsCount < 2) {
                    return false;
                }
                if ($rollsCount < 3 && ($this->isPlayerStrike($player) || $this->isPlayerSpare($player))) {
                    return false;
                }
            } else {
                if ($rollsCount < 1 && $this->isPlayerStrike($player)) {
                    return false;
                }
                if ($rollsCount < 2 && !$this->isPlayerStrike($player)) {
                    return false;
                }
            }
        }
        return true;
    }
}
