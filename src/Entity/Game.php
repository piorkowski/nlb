<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\BlamestampTrait;
use App\Entity\Trait\TimestampTrait;
use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ORM\Table(name: 'game')]
#[ORM\HasLifecycleCallbacks]
class Game
{
    use TimestampTrait;
    use BlamestampTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $gameDate = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: true)]
    private ?League $league = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: GameStatus::class)]
    private GameStatus $status = GameStatus::DRAFT;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'gamesAsTeamA')]
    #[ORM\JoinColumn(name: 'team_a_id', referencedColumnName: 'id', nullable: true)]
    private ?Team $teamA = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'gamesAsTeamB')]
    #[ORM\JoinColumn(name: 'team_b_id', referencedColumnName: 'id', nullable: true)]
    private ?Team $teamB = null;

    #[ORM\OneToMany(targetEntity: Frame::class, mappedBy: 'game', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['laneNumber' => 'ASC', 'gameNumber' => 'ASC', 'frameNumber' => 'ASC'])]
    private Collection $frames;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $teamAPoints = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $teamBPoints = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->frames = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGameDate(): ?\DateTimeImmutable
    {
        return $this->gameDate;
    }

    public function setGameDate(\DateTimeImmutable $gameDate): self
    {
        $this->gameDate = $gameDate;
        return $this;
    }

    public function getLeague(): ?League
    {
        return $this->league;
    }

    public function setLeague(?League $league): self
    {
        $this->league = $league;
        return $this;
    }

    public function getStatus(): GameStatus
    {
        return $this->status;
    }

    public function setStatus(GameStatus $status): self
    {
        $this->status = $status;
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
     * @return Collection<int, Frame>
     */
    public function getFrames(): Collection
    {
        return $this->frames;
    }

    public function addFrame(Frame $frame): self
    {
        if (!$this->frames->contains($frame)) {
            $this->frames->add($frame);
            $frame->setGame($this);
        }
        return $this;
    }

    public function removeFrame(Frame $frame): self
    {
        if ($this->frames->removeElement($frame)) {
            if ($frame->getGame() === $this) {
                $frame->setGame(null);
            }
        }
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    // === Helper methods ===

    public function isTeamGame(): bool
    {
        return $this->teamA !== null && $this->teamB !== null;
    }

    public function isIndividualGame(): bool
    {
        return !$this->isTeamGame();
    }

    /**
     * Pobiera wszystkich unikalnych graczy w grze
     */
    public function getAllPlayers(): array
    {
        $players = [];
        foreach ($this->frames as $frame) {
            foreach ($frame->getTeamAPlayers() as $player) {
                $players[$player->getId()] = $player;
            }
            foreach ($frame->getTeamBPlayers() as $player) {
                $players[$player->getId()] = $player;
            }
        }
        return array_values($players);
    }

    /**
     * Pobiera wynik konkretnego gracza we wszystkich framach tego meczu
     */
    public function getPlayerTotalScore(User $player): int
    {
        $framesByGameAndNumber = [];

        foreach ($this->frames as $frame) {
            $gameNumber = $frame->getGameNumber();
            $frameNumber = $frame->getFrameNumber();

            if (!isset($framesByGameAndNumber[$gameNumber])) {
                $framesByGameAndNumber[$gameNumber] = [];
            }

            $framesByGameAndNumber[$gameNumber][$frameNumber] = $frame;
        }

        $totalScore = 0;

        foreach ($framesByGameAndNumber as $gameNumber => $framesInGame) {
            ksort($framesInGame);
            $framesArray = array_values($framesInGame);

            foreach ($framesArray as $index => $frame) {
                $nextFrame = $framesArray[$index + 1] ?? null;
                $nextNextFrame = $framesArray[$index + 2] ?? null;
                $totalScore += $frame->calculatePlayerScore($player, $nextFrame, $nextNextFrame);
            }
        }

        return $totalScore;
    }

    /**
     * Suma punktów Team A we wszystkich framach
     */
    public function getTeamAScore(): int
    {
        if (!$this->isTeamGame()) {
            return 0;
        }

        $total = 0;

        // Pobierz wszystkich graczy Team A z pierwszego framu
        $firstFrame = $this->frames->first();
        if (!$firstFrame) {
            return 0;
        }

        foreach ($firstFrame->getTeamAPlayers() as $player) {
            $total += $this->getPlayerTotalScore($player);
        }

        return $total;
    }

    /**
     * Suma punktów Team B we wszystkich framach
     */
    public function getTeamBScore(): int
    {
        if (!$this->isTeamGame()) {
            return 0;
        }

        $total = 0;

        $firstFrame = $this->frames->first();
        if (!$firstFrame) {
            return 0;
        }

        foreach ($firstFrame->getTeamBPlayers() as $player) {
            $total += $this->getPlayerTotalScore($player);
        }

        return $total;
    }

    /**
     * Pobiera zwycięzcę meczu
     */
    public function getWinner(): ?string
    {
        if ($this->status !== GameStatus::FINISHED) {
            return null;
        }

        if ($this->isTeamGame()) {
            $scoreA = $this->getTeamAScore();
            $scoreB = $this->getTeamBScore();

            if ($scoreA > $scoreB) return 'Team A';
            if ($scoreB > $scoreA) return 'Team B';
            return 'DRAW';
        }

        // Individual game
        $players = $this->getAllPlayers();
        if (count($players) !== 2) {
            return null;
        }

        $score1 = $this->getPlayerTotalScore($players[0]);
        $score2 = $this->getPlayerTotalScore($players[1]);

        if ($score1 > $score2) return $players[0]->getFullName();
        if ($score2 > $score1) return $players[1]->getFullName();
        return 'DRAW';
    }

    /**
     * Sprawdza czy gra jest kompletna (wszystkie framy wypełnione)
     */
    public function isComplete(): bool
    {
        if ($this->frames->isEmpty()) {
            return false;
        }

        $players = $this->getAllPlayers();

        foreach ($this->frames as $frame) {
            foreach ($players as $player) {
                $rolls = $frame->getPlayerRolls($player);

                if ($rolls->isEmpty()) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getTeamAPoints(): ?int
    {
        return $this->teamAPoints;
    }

    public function setTeamAPoints(?int $teamAPoints): self
    {
        $this->teamAPoints = $teamAPoints;
        return $this;
    }

    public function getTeamBPoints(): ?int
    {
        return $this->teamBPoints;
    }

    public function setTeamBPoints(?int $teamBPoints): self
    {
        $this->teamBPoints = $teamBPoints;
        return $this;
    }

    /**
     * Oblicza punkty za dwumecz według zasady:
     * - Wygrana na torze = 2 punkty
     * - Remis na torze = 1 punkt dla każdej drużyny/gracza
     * - Przegrana = 0 punktów
     *
     * Dwumecz = 2 gry, więc max 4 punkty (2+2)
     */
    public function calculatePoints(): void
    {
        if ($this->status !== GameStatus::FINISHED) {
            return;
        }

        if (!$this->isTeamGame()) {
            $this->calculateIndividualPoints();
        } else {
            $this->calculateTeamPoints();
        }
    }

    private function calculateTeamPoints(): void
    {
        $teamAPointsTotal = 0;
        $teamBPointsTotal = 0;

        $gamesByNumber = [];
        foreach ($this->frames as $frame) {
            $gamesByNumber[$frame->getGameNumber()][] = $frame;
        }

        foreach ($gamesByNumber as $gameNumber => $framesInGame) {
            $teamAScore = 0;
            $teamBScore = 0;

            foreach ($framesInGame as $frame) {
                foreach ($frame->getTeamAPlayers() as $player) {
                    $teamAScore += $this->getPlayerTotalScore($player);
                }
                foreach ($frame->getTeamBPlayers() as $player) {
                    $teamBScore += $this->getPlayerTotalScore($player);
                }
                break;
            }

            if ($teamAScore > $teamBScore) {
                $teamAPointsTotal += 2;
            } elseif ($teamBScore > $teamAScore) {
                $teamBPointsTotal += 2;
            } else {
                $teamAPointsTotal += 1;
                $teamBPointsTotal += 1;
            }
        }

        $this->teamAPoints = $teamAPointsTotal;
        $this->teamBPoints = $teamBPointsTotal;
    }

    private function calculateIndividualPoints(): void
    {
        $players = $this->getAllPlayers();
        if (count($players) !== 2) {
            return;
        }

        [$playerA, $playerB] = $players;
        $pointsA = 0;
        $pointsB = 0;

        $gamesByNumber = [];
        foreach ($this->frames as $frame) {
            $gamesByNumber[$frame->getGameNumber()][] = $frame;
        }

        foreach ($gamesByNumber as $gameNumber => $framesInGame) {
            $scoreA = 0;
            $scoreB = 0;

            foreach ($framesInGame as $frame) {
                $scoreA += $frame->calculatePlayerScore($playerA);
                $scoreB += $frame->calculatePlayerScore($playerB);
            }

            if ($scoreA > $scoreB) {
                $pointsA += 2;
            } elseif ($scoreB > $scoreA) {
                $pointsB += 2;
            } else {
                $pointsA += 1;
                $pointsB += 1;
            }
        }

        $this->teamAPoints = $pointsA;
        $this->teamBPoints = $pointsB;
    }

    public function getPlayerPoints(User $player): int
    {
        if ($this->isTeamGame()) {
            return 0;
        }

        $players = $this->getAllPlayers();
        if (count($players) !== 2) {
            return 0;
        }

        if ($players[0]->getId() === $player->getId()) {
            return $this->teamAPoints ?? 0;
        }

        if ($players[1]->getId() === $player->getId()) {
            return $this->teamBPoints ?? 0;
        }

        return 0;
    }

    public function __toString(): string
    {
        if ($this->isTeamGame()) {
            return sprintf(
                '%s vs %s (%s)',
                $this->teamA?->getName() ?? 'Team A',
                $this->teamB?->getName() ?? 'Team B',
                $this->gameDate?->format('Y-m-d') ?? 'TBD'
            );
        }

        $players = $this->getAllPlayers();
        if (count($players) === 2) {
            return sprintf(
                '%s vs %s (%s)',
                $players[0]->getFullName(),
                $players[1]->getFullName(),
                $this->gameDate?->format('Y-m-d') ?? 'TBD'
            );
        }

        return sprintf('Game (%s)', $this->gameDate?->format('Y-m-d') ?? 'TBD');
    }
}
