<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\User;
use App\Entity\Season;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Assert\NotNull]
    #[ORM\JoinColumn]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $home;

    #[Assert\NotNull]
    #[ORM\JoinColumn]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $away;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $scoreHome;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $scoreAway;

    #[ORM\Column(type: 'datetime')]
    private $created;

    #[ORM\Column(type: 'datetime')]
    private $modified;

    #[Assert\NotNull]
    #[ORM\JoinColumn]
    #[ORM\ManyToOne(targetEntity: User::class)]
    private $reporter;

    #[Assert\NotNull]
    #[ORM\JoinColumn]
    #[ORM\ManyToOne(targetEntity: "Season")]
    private $season;

    #[ORM\Column(type: 'boolean', options: ["default" => false])]
    private $voided;

    #[ORM\OneToMany(
        mappedBy: 'game',
        targetEntity: Replay::class,
        orphanRemoval: true,
        cascade: ['persist', 'remove'])]
    private $replays;

    public function __construct()
    {
        $this->voided = false;
        $this->created = new \DateTime();
        $this->modified = $this->created;
        $this->replays = new ArrayCollection();
    }

    public function played(): bool
    {
        return $this->reporter != null && $this->scoreHome != null && $this->scoreAway != null;
    }

    public function draw(): bool
    {
        return $this->played() && $this->scoreHome === $this->scoreAway;
    }

    public function winner(): ?User
    {
        if (!$this->played() || $this->draw()) {
            return null;
        }
        return $this->scoreHome > $this->scoreAway ? $this->homeUser : $this->awayUser;
    }

    public function loser(): ?User
    {
        $winner = $this->winner();
        if (is_null($winner)) {
            return null;
        }
        return $winner->getId() === $this->homeUser->getId()
            ? $this->awayUser : $this->homeUser;
    }

    public function plusPoints(int $points): Game
    {
        $this->points += $points;
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateModified()
    {
        $this->modified = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHome(): ?int
    {
        return $this->home;
    }

    public function setHome(User $home): self
    {
        $this->home = $home;

        return $this;
    }

    public function getAway(): ?int
    {
        return $this->away;
    }

    public function setAway(User $away): self
    {
        $this->away = $away;

        return $this;
    }

    public function getScoreHome(): ?User
    {
        return $this->scoreHome;
    }

    public function setScoreHome(?int $scoreHome): self
    {
        $this->scoreHome = $scoreHome;

        return $this;
    }

    public function getScoreAway(): ?User
    {
        return $this->scoreAway;
    }

    public function setScoreAway(?int $scoreAway): self
    {
        $this->scoreAway = $scoreAway;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getModified(): ?\DateTimeInterface
    {
        return $this->modified;
    }

    public function setModified(\DateTimeInterface $modified): self
    {
        $this->modified = $modified;

        return $this;
    }

    public function getReporter(): ?User
    {
        return $this->reporter;
    }

    public function setReporter(User $reporter): self
    {
        $this->reporter = $reporter;

        return $this;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(Season $season): self
    {
        $this->season = $season;

        return $this;
    }

    public function getVoided(): ?bool
    {
        return $this->voided;
    }

    public function setVoided(bool $voided): self
    {
        $this->voided = $voided;

        return $this;
    }

    #[Assert\IsTrue(message: "One must not play against oneself.")]
    public function isOpponentDifferent()
    {
        return $this->home?->getId() !== $this->away?->getId();
    }

    /**
     * @return Collection<int, Replay>
     */
    public function getReplays(): Collection
    {
        return $this->replays;
    }

    public function addReplay(Replay $replay): self
    {
        if (!$this->replays->contains($replay)) {
            $this->replays[] = $replay;
            $replay->setGame($this);
        }

        return $this;
    }

    public function removeReplay(Replay $replay): self
    {
        if ($this->replays->removeElement($replay)) {
            // set the owning side to null (unless already changed)
            if ($replay->getGame() === $this) {
                $replay->setGame(null);
            }
        }

        return $this;
    }
}
