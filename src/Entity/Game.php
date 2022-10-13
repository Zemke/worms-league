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
    #[Assert\PositiveOrZero(message: 'Home score should be positive.')]
    private $scoreHome;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(message: 'Away score should be positive.')]
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

    // TODO there's got to be a reportedAt because PO games are created before they're reported

    /**
     * Whether or not the game had already been included when last calculating the ranking.
     */
    #[ORM\Column(type: 'boolean', options: ["default" => false])]
    private $ranked;

    #[ORM\OneToMany(mappedBy: 'game', targetEntity: Comment::class, orphanRemoval: true)]
    private $comments;

    #[ORM\OneToOne(targetEntity: Playoff::class, cascade: ['persist', 'remove'])]
    private $playoff;

    public function __construct()
    {
        $this->voided = false;
        $this->ranked = false;
        $this->created = new \DateTime();
        $this->modified = $this->created;
        $this->replays = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function played(): bool
    {
        return !is_null($this->reporter) && !is_null($this->scoreHome) && !is_null($this->scoreAway);
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
        return $this->scoreHome > $this->scoreAway ? $this->home: $this->away;
    }

    public function loser(): ?User
    {
        $winner = $this->winner();
        if (is_null($winner)) {
            return null;
        }
        return $winner->getId() === $this->home->getId()
            ? $this->away : $this->home;
    }

    /**
     * Set the score based on replay data.
     */
    public function score(): self
    {
        $this->assertFullyProcessed();
        $scores = array_reduce($this->replays->getValues(), function ($acc, $r) {
            $winner = $r->winner();
            if (is_null($winner)) {
                return $acc;
            }
            $acc[+!($this->home->getId() === $winner->getId())]++;
            return $acc;
        }, [0, 0]);
        $this->setScoreHome($scores[0]);
        $this->setScoreAway($scores[1]);
        return $this;
    }

    /**
     * @throws \RuntimeException when the game has not fully processed.
     */
    private function assertFullyProcessed(): void
    {
        if (!$this->fullyProcessed()) {
            throw new \RuntimeException("Game {$this->getId()} is not fully processed");
        }
    }

    private function assertHomeOrAway(User $user): void
    {
        if (!$this->isHomeOrAway($user)) {
            throw new \RuntimeException(
                "User {$user->getId()} is neither "
                . "home {$this->home?->getId()} nor away {$this->away?->getId()}");
        }
    }

    /**
     * Convenience for getting every replay's data sorted by its startedAt ascendingly.
     *
     * @return ReplayData[]
     */
    public function replayData(): array
    {
        $this->assertFullyProcessed();
        $rr = array_reduce($this->getReplays()->getValues(), function($acc, $v) {
            $acc[] = $v->getReplayData();
            return $acc;
        }, []);
        usort($rr, fn($a, $b) => $a->startedAt() > $b->startedAt() ? 1 : -1);
        return $rr;
    }

    public function fullyProcessed(): bool
    {
        $replays = $this->getReplays()->getValues();
        return count($replays) === array_reduce($replays, function($acc, $replay) {
            return $acc + $replay->processed();
        }, 0);
    }

    public function isHome(User $user): bool
    {
        return $this->home?->getId() === $user->getId();
    }

    public function isAway(User $user): bool
    {
        return $this->away?->getId() === $user->getId();
    }

    public function isHomeOrAway(User $user): bool
    {
        return $this->isHome($user) || $this->isAway($user);
    }

    public function isPlayoff(): bool
    {
        return !is_null($this->getPlayoff());
    }

    public function opponent(User $user): User
    {
        $this->assertHomeOrAway($user);
        return $this->isHome($user) ? $this->away : $this->home;
    }

    public function scoreOf(User $user): int
    {
        $this->assertHomeOrAway($user);
        return $this->isHome($user) ? $this->scoreHome : $this->scoreAway;
    }

    /**
     * When the game was actually played.
     * That's the most recent replay's startedAt data property.
     */
    public function playedAt(): \DateTime
    {
        $this->assertFullyProcessed();
        $rd = $this->replayData();
        return end($rd)->startedAt();
    }

    /**
     * Get replays sorted by their replay data's startedAt value.
     *
     * @param $order 'asc'|'desc' sort order
     */
    public function sortedReplays(string $order = 'asc'): array
    {
        $replays = $this->replays->getValues();
        $ord = $order === 'desc' ? [-1, 1] : [1, -1];
        usort(
            $replays,
            fn($a, $b) =>
                $a?->getReplayData()?->startedAt() > $b?->getReplayData()?->startedAt()
                ? $ord[0] : $ord[1]
        );
        return $replays;
    }

    public function asText(): string
    {
        $s = $this->getHome()->getUsername();
        $s .= $this->played()
            ? " {$this->getScoreHome()}–{$this->getScoreAway()} "
            : ' – ';
        $s .= $this->getAway()->getUsername();
        return $s;
    }

    #[Assert\IsTrue(message: "One must not play against oneself.")]
    public function isOpponentDifferent(): bool
    {
        return $this->home?->getId() !== $this->away?->getId();
    }

    #[Assert\IsTrue(message: 'The season has ended.')]
    public function isReportWithinSeason(): bool
    {
        $now = new \DateTime('now');
        return $this->isPlayoff()
            || ($this->season->getStart() <= $now
            && $this->season->getEnding() >= $now);
    }

    #[Assert\IsTrue(message: 'The season is not active.')]
    public function isSeasonActive(): bool
    {
        return $this->season->getActive();
    }

    #[Assert\IsTrue(message: "There are not enough replays.")]
    public function isEnoughReplays(): bool
    {
        return ($this->scoreHome + $this->scoreAway) <= count($this->replays);
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

    public function getHome(): ?User
    {
        return $this->home;
    }

    public function setHome(User $home): self
    {
        $this->home = $home;

        return $this;
    }

    public function getAway(): ?User
    {
        return $this->away;
    }

    public function setAway(User $away): self
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

    public function getRanked(): ?bool
    {
        return $this->ranked;
    }

    public function setRanked(bool $ranked): self
    {
        $this->ranked = $ranked;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments[] = $comment;
            $comment->setGame($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getGame() === $this) {
                $comment->setGame(null);
            }
        }

        return $this;
    }

    public function getPlayoff(): ?Playoff
    {
        return $this->playoff;
    }

    public function setPlayoff(?Playoff $playoff): self
    {
        $this->playoff = $playoff;

        return $this;
    }
}
