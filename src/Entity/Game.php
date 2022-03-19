<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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
    #[ORM\ManyToOne(targetEntity: "User")]
    private $home;

    #[Assert\NotNull]
    #[ORM\JoinColumn]
    #[ORM\ManyToOne(targetEntity: "User")]
    private $away;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $scoreHome;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $scoreAway;

    #[ORM\Column(type: 'datetime')]
    private $created;

    #[ORM\Column(type: 'datetime')]
    private $modified;

    #[ORM\JoinColumn]
    #[ORM\ManyToOne(targetEntity: "User")]
    private $reporter;

    #[Assert\NotNull]
    #[ORM\JoinColumn]
    #[ORM\ManyToOne(targetEntity: "Season")]
    private $season;

    #[ORM\Column(type: 'boolean', options: ["default" => false])]
    private $voided;

    public function __construct()
    {
        $this->voided = false;
        $this->created = new \DateTime();
        $this->modified = $this->created;
    }


    #[@ORM\PrePersist]
    #[@ORM\PreUpdate]
    public function updateModified()
    {
        $this->modified = new \DateTime();
    }

    public function get(): ?int
    {
        return $this->id;
    }

    public function getHome(): ?int
    {
        return $this->home;
    }

    public function setHome(int $home): self
    {
        $this->home = $home;

        return $this;
    }

    public function getAway(): ?int
    {
        return $this->away;
    }

    public function setAway(int $away): self
    {
        $this->away = $away;

        return $this;
    }

    public function getScoreHome(): ?int
    {
        return $this->scoreHome;
    }

    public function setScoreHome(?int $scoreHome): self
    {
        $this->scoreHome = $scoreHome;

        return $this;
    }

    public function getScoreAway(): ?int
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

    public function getReporter(): ?int
    {
        return $this->reporter;
    }

    public function setReporter(int $reporter): self
    {
        $this->reporter = $reporter;

        return $this;
    }

    public function getSeason(): ?int
    {
        return $this->season;
    }

    public function setSeason(int $season): self
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
        return $this->home->getId() !== $this->away->getId();
    }
}
