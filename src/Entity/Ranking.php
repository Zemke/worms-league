<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Repository\RankingRepository;
use App\Entity\Game;
use App\Entity\User;
use App\Thing\Decimal;

#[ORM\Entity(repositoryClass: RankingRepository::class)]
#[ORM\UniqueConstraint(name: 'owner_season_uidx', columns: ['owner_id', 'season_id'])]
class Ranking
{
    /** @var int num of days to look backwards in time to determine activity */
    private const ACTIVITY_LOOKBACK = 7;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $owner;

    #[ORM\Column(type: 'decimal', precision: Decimal::SCALE + 20, scale: Decimal::SCALE)]
    private $points;

    #[ORM\ManyToOne(targetEntity: Season::class, inversedBy: 'rankings')]
    #[ORM\JoinColumn(nullable: false)]
    private $season;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private $roundsPlayed;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => 0.00])]
    private $roundsPlayedRatio;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private $roundsWon;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => 0.00])]
    private $roundsWonRatio;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private $roundsLost;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private $gamesPlayed;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => 0.00])]
    private $gamesPlayedRatio;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private $gamesWon;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => 0.00])]
    private $gamesWonRatio;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private $gamesLost;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private $streak;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private $streakBest;

    /**
     * Games per day during the last seven days incl. today.
     */
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, options: ['default' => 0.00])]
    private $activity;

    public function __construct()
    {
        $this->reset();
    }

    public function reset(bool $resetPoints = false): self
    {
        $this->points = $resetPoints ? '0' : null;
        $this->roundsPlayed = 0;
        $this->roundsWon = 0;
        $this->roundsLost = 0;
        $this->roundsPlayedRatio = 0.00;
        $this->gamesPlayed = 0;
        $this->gamesWon = 0;
        $this->gamesLost = 0;
        $this->gamesPlayedRatio = 0.00;
        $this->streak = 0;
        $this->streakBest = 0;
        $this->activity = 0.00;
        return $this;
    }

    /**
     * Update ranking fields which rely on all games..
     *
     * @param Game[] $games Of this season relevant for this ranking.
     */
    public function updateByGames(array $games): self
    {

        usort(
            $games,
            fn($g1, $g2) => $g2->getCreated()->getTimestamp() - $g1->getCreated()->getTimestamp());
        $latest = \DateTimeImmutable::createFromMutable(
            $games[0]->getCreated())->modify('-' . self::ACTIVITY_LOOKBACK . ' days');
        $myGames = array_filter($games, fn($g) => $g->isHomeOrAway($this->owner));
        $totalRounds = array_reduce($games, function ($acc, $g) {
            return $acc + $g->getScoreHome() + $g->getScoreAway();
        }, 0);
        $myRounds = array_reduce($myGames, function ($acc, $g) {
            $c = 0;
            if ($this->ownedBy($g->getHome()) || $this->ownedBy($g->getAway())) {
                return $g->getScoreHome() + $g->getScoreAway() + $acc;
            }
            return $acc;
        }, 0);
        $this->roundsPlayedRatio = $myRounds / $totalRounds;
        $this->gamesPlayedRatio = count($myGames) / count($games);
        $recentGames = array_filter($myGames, fn($g) => $g->getCreated() >= $latest);
        $this->activity = count($recentGames) / Ranking::ACTIVITY_LOOKBACK;
        usort($myGames, fn($g1, $g2) => $g1->getCreated() > $g2->getCreated() ? 1 : -1);
        foreach ($myGames as $g) {
            if ($g->getSeason()->getId() !== $this->season->getId()) {
                throw new \RuntimeException(
                    "game {$g->getId()}'s season is {$g->getSeason()?->getId()} "
                    . "whereas ranking {$this->id} season is {$this->season->getId()}");
            }
            if ($this->ownedBy($g->getHome())) {
                $roundsWon = $g->getScoreHome();
                $roundsLost = $g->getScoreAway();
                $roundsDrawn = count($g->getReplays()) - ($roundsWon + $roundsLost);
            } else if ($this->ownedBy($g->getAway())) {
                $roundsWon = $g->getScoreAway();
                $roundsLost = $g->getScoreHome();
            } else {
                throw new \RuntimeException(
                    "neither {$g->getHome()->getId()} nor {$g->getAway()->getId()} "
                    . "own ranking {$this->id} owned by {$this->owner->getId()}");
            }
            $roundsDrawn = count($g->drawnRounds());
            $this->roundsPlayed += $roundsWon + $roundsLost + $roundsDrawn;
            $this->roundsWon += $roundsWon;
            $this->roundsWonRatio = $this->roundsWon / $this->roundsPlayed;
            $this->roundsLost += $roundsLost;
            $this->gamesPlayed += 1;
            $draw = $g->draw();
            $won = !$draw && $g->winner()->getId() === $this->owner->getId();
            $this->gamesWon += +($won);
            $this->gamesWonRatio = $this->gamesWon / $this->gamesPlayed;
            $this->gamesLost += +(!$draw && !$won);

            if (!$draw) {
                if ($won) {
                    $this->streak = $this->streak > 0 ? ($this->streak + 1) : 1;
                    if ($this->streakBest < $this->streak) {
                        $this->streakBest = $this->streak;
                    }
                } else {
                    $this->streak = $this->streak < 0 ? ($this->streak - 1) : -1;
                }
            } else {
                $this->streak = 0;
            }
        }
        return $this;
    }

    public function ownedBy(User $other): bool
    {
        return $this->owner->getId() === $other->getId();
    }

    /**
     * Get absolute ranking if points are not relativized yet.
     *
     * @return Decimal roundsWon if points are null, points otherwise.
     */
    public function ranking(): Decimal
    {
        return Decimal::of(is_null($this->points) ? (float) $this->roundsWon : $this->points);
    }

    public function getId(): ?int

    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function getPoints(): ?string
    {
        return $this->points;
    }

    public function setPoints(string $points): self
    {
        $this->points = $points;

        return $this;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): self
    {
        $this->season = $season;

        return $this;
    }

    public function getRoundsPlayed(): ?int
    {
        return $this->roundsPlayed;
    }

    public function setRoundsPlayed(int $roundsPlayed): self
    {
        $this->roundsPlayed = $roundsPlayed;

        return $this;
    }

    public function getRoundsPlayedRatio(): ?string
    {
        return $this->roundsPlayedRatio;
    }

    public function setRoundsPlayedRatio(string $roundsPlayedRatio): self
    {
        $this->roundsPlayedRatio = $roundsPlayedRatio;

        return $this;
    }

    public function getRoundsWon(): ?int
    {
        return $this->roundsWon;
    }

    public function setRoundsWon(int $roundsWon): self
    {
        $this->roundsWon = $roundsWon;

        return $this;
    }

    public function getRoundsWonRatio(): ?string
    {
        return $this->roundsWonRatio;
    }

    public function setRoundsWonRatio(string $roundsWonRatio): self
    {
        $this->roundsWonRatio = $roundsWonRatio;

        return $this;
    }

    public function getRoundsLost(): ?int
    {
        return $this->roundsLost;
    }

    public function setRoundsLost(int $roundsLost): self
    {
        $this->roundsLost = $roundsLost;

        return $this;
    }

    public function getGamesPlayed(): ?int
    {
        return $this->gamesPlayed;
    }

    public function setGamesPlayed(int $gamesPlayed): self
    {
        $this->gamesPlayed = $gamesPlayed;

        return $this;
    }

    public function getGamesPlayedRatio(): ?string
    {
        return $this->gamesPlayedRatio;
    }

    public function setGamesPlayedRatio(string $gamesPlayedRatio): self
    {
        $this->gamesPlayedRatio = $gamesPlayedRatio;

        return $this;
    }

    public function getGamesWon(): ?int
    {
        return $this->gamesWon;
    }

    public function setGamesWon(int $gamesWon): self
    {
        $this->gamesWon = $gamesWon;

        return $this;
    }

    public function getGamesWonRatio(): ?string
    {
        return $this->gamesWonRatio;
    }

    public function setGamesWonRatio(string $gamesWonRatio): self
    {
        $this->gamesWonRatio = $gamesWonRatio;

        return $this;
    }

    public function getGamesLost(): ?int
    {
        return $this->gamesLost;
    }

    public function setGamesLost(int $gamesLost): self
    {
        $this->gamesLost = $gamesLost;

        return $this;
    }

    public function getStreak(): ?int
    {
        return $this->streak;
    }

    public function setStreak(int $streak): self
    {
        $this->streak = $streak;

        return $this;
    }

    public function getStreakBest(): ?int
    {
        return $this->streakBest;
    }

    public function setStreakBest(int $streakBest): self
    {
        $this->streakBest = $streakBest;

        return $this;
    }

    public function getActivity(): ?string
    {
        return $this->activity;
    }

    public function setActivity(string $activity): self
    {
        $this->activity = $activity;

        return $this;
    }
}
