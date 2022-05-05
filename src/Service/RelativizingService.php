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
        $oppRanks = array_reduce($games, function ($acc, $g) use ($user, $rankings) {
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
        $P = 0;
        $roundsWon = array_unique(array_map(
            fn($r) => is_null($r->getPoints()) ? $r->getRoundsWon() : $r->getPoints(),
            $rankings));
        sort($roundsWon);
        $userRanking = current(array_filter($rankings, fn($r) => $r->ownedBy($user)));
        assert(array_sum(array_column($oppRanks, 'won')) === $userRanking->getRoundsWon());
        $X = count($rankings);
        foreach ($oppRanks as $r) {
            $weight = (array_search($r['opp']->getRoundsWon(), $roundsWon) + 1) / $X;
            $P += ($weight) * ($r['won'] / $userRanking->getRoundsWon());
        }
        return $P;
    }
}


