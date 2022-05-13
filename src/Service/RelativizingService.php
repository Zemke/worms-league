<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Ranking;
use App\Entity\User;

class RelativizingService
{

    public function __construct()
    {}

    /**
     * Relativize by how well the user's opponents are ranked.
     *
     * @param User $user The user whose won rounds are to be relativized.
     * @param Ranking[] $rankings Quality of opponents based on these rankings.
     * @param Game[] $games Games to find the opponents of the given user.
     * @return float The weight of the won rounds according to opponent quality.
     */
    public function byQuality(User $user, array $rankings, array $games, array &$DP = []): float
    {
        $oppRanks = $this->reduceOppRanks($user, $rankings, $games, $DP);
        $P = 0;
        $ranking = array_unique(array_map(fn($r) => $r->ranking(), $rankings));
        sort($ranking);
        $userRanking = $this->userRanking($user, $rankings);
        assert(array_sum(array_map(fn($or) => $or->getWon(), $oppRanks)) === $userRanking->getRoundsWon());
        $X = count($ranking);
        foreach ($oppRanks as $or) {
            $weight = pow((array_search($or->getOpp()->ranking(), $ranking) + 1) / $X, 3);
            $P += ($weight) * ($or->getWon() / $userRanking->getRoundsWon());
        }
        return $P;
    }

    // this is an alternative using min max rather than rank exponentially
    public function byQualityMinMax(User $user, array $rankings, array $games, array &$DP = []): float
    {
        $oppRanks = $this->reduceOppRanks($user, $rankings, $games, $DP);
        $userRanking = $this->userRanking($user, $rankings);
        assert(array_sum(array_map(fn($or) => $or->getWon(), $oppRanks)) === $userRanking->getRoundsWon());
        $allRankings = array_map(fn($r) => $r->ranking(), $rankings);
        $mn = min($allRankings) - PHP_FLOAT_MIN;
        $mx = max($allRankings);
        $P = 0;
        foreach ($oppRanks as $r) {
            $weight = ($r->getOpp()->ranking() - $mn) / ($mx - $mn);
            $P += ($weight) * ($r->getWon() / $userRanking->getRoundsWon());
        }
        return $P;
    }

    /**
     * Relativize by total rounds won against the same opponent.
     * The more rounds you've won against the same opponent the less it values
     * in the bigger picture.
     *
     * @param User $user The user whose won rounds are to be relativized.
     * @param Ranking[] $rankings Quality of opponents based on these rankings.
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
            $oppRanks = $this->reduceOppRanks($r->getOwner(), $rankings, $games, $DP);
            if (empty($oppRanks)) {
                return $acc;
            }
            return max($acc, max(array_map(fn($or) => $or->getWon(), $oppRanks)));
        }, 0);
        $userRanking = $this->userRanking($user, $rankings);
        if ($userRanking->getRoundsWon() === 0) {
            return 0;
        }
        $oppRanks = $this->reduceOppRanks($user, $rankings, $games, $DP);
        $P = 0;
        foreach ($oppRanks as $or) {
            // Sum[-(99/(100*log(a)))*log(x)+1),{x,1,z}]/z
            $y = array_sum(array_map(fn($x) =>
                -(99/(100*log($a)))*log($x)+1, range(1, $or->getWon()))) / $or->getWon();
            $P += $y * ($or->getWon() / $userRanking->getRoundsWon());
        }
        return $P;
    }

    /**
     * How many rounds it took the user to attain the rounds won.
     * All rounds played devalue the rounds won.
     * The more total rounds played, the less value for a round won.
     */
    private function byEffort(User $user, array $rankings, array $games, array &$DP = []): float
    {
        return 1.; // TODO byEffort
    }

    /**
     * Value of rounds won decay over time. The older a won round, the less value.
     */
    private function byEntropy(User $user, array $rankings, array $games, array &$DP = []): float
    {
        return 1.; // TODO byEntropy
    }

    private function reduceOppRanks(User $user, array $rankings, array $games, array &$DP = []): array
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
}

