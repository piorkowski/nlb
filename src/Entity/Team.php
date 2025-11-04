<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\Table(name: 'teams')]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'teams')]
    #[ORM\JoinTable(name: 'team_players')]
    private Collection $players;

    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'teamA')]
    private Collection $gamesAsTeamA;

    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'teamB')]
    private Collection $gamesAsTeamB;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->gamesAsTeamA = new ArrayCollection();
        $this->gamesAsTeamB = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(User $player): static
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
        }
        return $this;
    }

    public function removePlayer(User $player): static
    {
        $this->players->removeElement($player);
        return $this;
    }

    public function getGamesAsTeamA(): Collection
    {
        return $this->gamesAsTeamA;
    }

    public function getGamesAsTeamB(): Collection
    {
        return $this->gamesAsTeamB;
    }

    public function getAllGames(): Collection
    {
        $allGames = new ArrayCollection();

        foreach ($this->gamesAsTeamA as $game) {
            $allGames->add($game);
        }

        foreach ($this->gamesAsTeamB as $game) {
            if (!$allGames->contains($game)) {
                $allGames->add($game);
            }
        }

        return $allGames;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
