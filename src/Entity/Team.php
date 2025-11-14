<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\BlamestampTrait;
use App\Entity\Trait\TimestampTrait;
use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Team
{
    use TimestampTrait;
    use BlamestampTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'teams')]
    private Collection $players;

    /**
     * @var Collection<int, League>
     */
    #[ORM\ManyToMany(targetEntity: League::class, mappedBy: 'teams')]
    private Collection $leagues;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'teamA')]
    private Collection $gamesAsTeamA;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'teamB')]
    private Collection $gamesAsTeamB;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->leagues = new ArrayCollection();
        $this->gamesAsTeamA = new ArrayCollection();
        $this->gamesAsTeamB = new ArrayCollection();
    }

    public function __toString(): string
    {
     return $this->name;
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

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
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

    public function getMembers(): Collection
    {
        return $this->getPlayers();
    }

    public function removePlayer(User $player): static
    {
        $this->players->removeElement($player);

        return $this;
    }

    /**
     * @return Collection<int, League>
     */
    public function getLeagues(): Collection
    {
        return $this->leagues;
    }

    public function addLeague(League $league): static
    {
        if (!$this->leagues->contains($league)) {
            $this->leagues->add($league);
            $league->addTeam($this);
        }

        return $this;
    }

    public function removeLeague(League $league): static
    {
        if ($this->leagues->removeElement($league)) {
            $league->removeTeam($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGamesAsTeamA(): Collection
    {
        return $this->gamesAsTeamA;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGamesAsTeamB(): Collection
    {
        return $this->gamesAsTeamB;
    }

    public function addGame(Game $game): static
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
            $game->setTeam1($this);
        }

        return $this;
    }

    public function removeGame(Game $game): static
    {
        if ($this->games->removeElement($game)) {
            // set the owning side to null (unless already changed)
            if ($game->getTeam1() === $this) {
                $game->setTeam1(null);
            }
        }

        return $this;
    }
}
