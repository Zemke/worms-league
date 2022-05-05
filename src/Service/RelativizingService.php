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
    public function byOpponentQuality(User $user, array $rankings, array $games): float
    {
        $oppRanks = $this->reduceOppRanks($user, $rankings, $games);
        $P = 0;
        $roundsWon = array_unique(array_map(
            fn($r) => is_null($r->getPoints()) ? $r->getRoundsWon() : $r->getPoints(),
            $rankings));
        sort($roundsWon);
        $userRanking = $this->userRanking($user, $rankings);
        assert(array_sum(array_column($oppRanks, 'won')) === $userRanking->getRoundsWon());
        $X = count($rankings);
        foreach ($oppRanks as $r) {
            $weight = (array_search($r['opp']->getRoundsWon(), $roundsWon) + 1) / $X;
            $P += ($weight) * ($r['won'] / $userRanking->getRoundsWon());
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
    public function byOpponentBashing(User $user, array $rankings, array $games): float
    {
        // a Is the max rounds won of a single player against another one
        //   so the value -- if it were x as well -- that would get us .01.
        //   In other words a is the max in a set of x.
        // -(99/(100ln(a)))ln(x)+1
        $a = array_reduce($rankings, function ($acc, $r) use ($rankings, $games) {
            $oppRanks = $this->reduceOppRanks($r->getOwner(), $rankings, $games);
            return max($acc, max(array_column($oppRanks, 'won')));
        }, 0);
        dump('a' . $a);
        $userRanking = $this->userRanking($user, $rankings);
        $oppRanks = $this->reduceOppRanks($user, $rankings, $games);
        $P = 0;
        foreach ($oppRanks as ['won' => $x]) {
            $y = -(99/(100*log($a)))*log($x)+1;
            $P += $y * ($x / $userRanking->getRoundsWon());
        }
        return $P;
    }

    private function reduceOppRanks(User $user, array $rankings, array $games): array
    {
        return array_reduce($games, function ($acc, $g) use ($user, $rankings) {
            if (!$g->fullyProcessed() || !$g->isHomeOrAway($user)) {
                return $acc;
            }
            $opp = $g->opponent($user);
            $oppRanking = current(
                array_filter($rankings, fn($r) => $r->getOwner()->getId() === $opp->getId()));
            $accKey = key(array_filter($acc, fn($x) => $x['opp']->getOwner()->getId() === $opp->getId()));
            if (is_null($accKey)) {
                $acc[] = ['opp' => $oppRanking, 'won' => $g->scoreOf($user)];
            } else {
                $acc[$accKey]['won'] += $g->scoreOf($user);
            }
            return $acc;
        }, []);
    }

    private function userRanking(User $user, array $rankings): Ranking
    {
        return current(array_filter($rankings, fn($r) => $r->ownedBy($user)));
    }
}

