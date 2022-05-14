<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Ranking;
use App\Entity\User;
use App\Thing\Decimal;

class RelativizingService
{
    /** @var float just so that there are no zero value points */
    private const JUMP_MIN = .00001;

    public function __construct()
    {}

    /**
     * Relativize by ranking of opponents.
     *
     * @param User $user The user whose won rounds are to be relativized.
     * @param Ranking[] $rankings Quality of opponents based on these rankings.
     * @param Game[] $games Games to find the opponents of the given user.
     * @return Decimal The weight of the won rounds according to opponent quality.
     */
    public function byQuality(User $user, array $rankings, array $games, array &$DP = []): Decimal
    {
        $oppRanks = OppRank::reduce($user, $rankings, $games, $DP);
        $P = new Decimal(0);
        $ranking = array_unique(array_map(fn($r) => $r->ranking(), $rankings));
        sort($ranking);
        $roundsWon = $this->userRanking($user, $rankings)->getRoundsWon();
        assert(array_sum(array_map(fn($or) => $or->getWon(), $oppRanks)) === $roundsWon);
        $X = count($ranking);
        foreach ($oppRanks as $or) {
            $y = (new Decimal(array_search($or->getOpp()->ranking(), $ranking)))
                ->add(1)
                ->div($X)
                ->pow(3);
            $P = $P->add($y->mul((new Decimal($or->getWon()))->div($roundsWon)));
        }
        return $P;
    }

    // this is an alternative using min max rather than rank exponentially
    public function byQualityMinMax(User $user, array $rankings, array $games, array &$DP = []): float
    {
        $oppRanks = OppRank::reduce($user, $rankings, $games, $DP);
        $roundsWon = $this->userRanking($user, $rankings)->getRoundsWon();
        assert(array_sum(array_map(fn($or) => $or->getWon(), $oppRanks)) === $roundsWon);
        $allRankings = array_map(fn($r) => $r->ranking(), $rankings);
        $mn = min($allRankings) - self::JUMP_MIN;
        $mx = max($allRankings);
        $P = 0;
        foreach ($oppRanks as $r) {
            $weight = ($r->getOpp()->ranking() - $mn) / ($mx - $mn);
            $P += ($weight) * ($r->getWon() / $roundsWon);
        }
        return $P;
    }

    /**
     * Relativize by total rounds won against the same opponent.
     * The more rounds you've won against the same opponent the less it values
     * in the bigger picture.
     *
     * @param User $user The user whose won rounds are to be relativized.
     * @param Ranking[] $rankings All rankings.
     * @param Game[] $games Games to find the opponents of the given user.
     * @return float The weight of the won rounds according to opponent quality.
     */
    public function byFarming(User $user, array $rankings, array $games, array &$DP = []): float
    {
        // a Is the max rounds won of a single player against another one
        //   so the value -- if it were x as well -- that would get us .01.
        //   In other words a is the max in a set of x.
        // -(99/(100ln(a)))ln(x)+1
        $a = array_reduce($rankings, function ($acc, $r) use ($rankings, $games, &$DP) {
            $oppRanks = OppRank::reduce($r->getOwner(), $rankings, $games, $DP);
            if (empty($oppRanks)) {
                return $acc;
            }
            return max($acc, max(array_map(fn($or) => $or->getWon(), $oppRanks)));
        }, 0);
        $roundsWon = $this->userRanking($user, $rankings)->getRoundsWon();
        if ($roundsWon === 0) {
            return 0;
        }
        $oppRanks = OppRank::reduce($user, $rankings, $games, $DP);
        $P = 0;
        foreach ($oppRanks as $or) {
            // Sum[-(99/(100*log(a)))*log(x)+1),{x,1,z}]/z
            $y = array_sum(array_map(fn($x) =>
                -(99/(100*log($a)))*log($x)+1, range(1, $or->getWon()))) / $or->getWon();
            $P += $y * ($or->getWon() / $roundsWon);
        }
        return $P;
    }

    /**
     * How many rounds it took the user to attain the rounds won.
     * All rounds played devalue the rounds won.
     * The more total rounds played, the less value for a round won.
     *
     * @param User $user The user whose won rounds are to be relativized.
     * @param Ranking[] Feature scaling rounds played across all rankings.
     * @return float The weight of the won rounds according to opponent quality.
     */
    // TODO Maybe do this on a per OppRank basis
    public function byEffort(User $user, array $rankings): float
    {
        $allRoundsPlayed = array_map(fn($r) => $r->getRoundsPlayed(), $rankings);
        $mx = min($allRoundsPlayed);
        //$mn = max($allRoundsPlayed) + self::JUMP_MIN;
        $mn = max($allRoundsPlayed);
        $norm = ($this->userRanking($user, $rankings)->getRoundsPlayed() - $mn) / ($mx - $mn);
        // scale values from .35 to 1. Could also return $norm directly for bigger effect.
        //return .35 + .65 * $norm;
        return $norm;
    }

    /**
     * Value of rounds won decay over time. The older a won round, the less value.
     */
    private function byEntropy(User $user, array $rankings, array $games, array &$DP = []): float
    {
        return 1.; // TODO byEntropy
    }

    private function userRanking(User $user, array $rankings): Ranking
    {
        return current(array_filter($rankings, fn($r) => $r->ownedBy($user)));
    }
}

class OppRank
{
    /**
     * @param Ranking $opp The opponent's ranking.
     * @param int $won Number of won rounds against opponent.
     */
    public function __construct(private Ranking $opp, private int $won)
    {
    }

    public function plusWon(int $n): self
    {
        if ($n < 0) {
            throw new RuntimeException($n . ' is negative');
        }
        $this->won += $n;
        return $this;
    }

    public function getOpp(): Ranking
    {
        return $this->opp;
    }

    public function getWon(): int
    {
        return $this->won;
    }

    /**
     * Reduce games into round totals against each opponent of the given user.
     *
     * @param User $user That user's opponents
     * @param Ranking[] $rankings All the rankings
     * @param Game[] $games All games that lead to the rankings.
     * @param array $DP User ID to OppRank[] mapping to apply memoization (Dynamic Programming).
     * @return The OppRank array for the given user.
     */
    public static function reduce(User $user, array $rankings, array $games, array &$DP = []): array
    {
        if (array_key_exists($user->getId(), $DP)) {
            return $DP[$user->getId()];
        }
        $DP[$user->getId()] = array_reduce($games, function ($acc, $g) use ($user, $rankings) {
            if (!$g->fullyProcessed() || !$g->isHomeOrAway($user) || ($userScore = $g->scoreOf($user)) === 0) {
                return $acc;
            }
            $opp = $g->opponent($user);
            $oppRanking = current(
                array_filter($rankings, fn($r) => $r->getOwner()->getId() === $opp->getId()));
            $accKey = key(array_filter($acc, fn($x) => $x->getOpp()->getOwner()->getId() === $opp->getId()));
            if (is_null($accKey)) {
                $acc[] = new OppRank($oppRanking, $userScore);
            } else {
                $acc[$accKey]->plusWon($userScore);
            }
            return $acc;
        }, []);
        return $DP[$user->getId()];
    }
}

