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
     * @param User user
     * @param Ranking[] rankings
     * @param Game[] games
     */
    public function byOpponentQuality(User $user, array &$rankings, array $games): void
    {
        usort($rankings, fn($a, $b) => $a->getPoints() - $b->getPoints());
        $oppRanks = array_reduce($games, function ($acc, $g) use ($user) {
            if (!$g->fullyProcessed() || !$g->isHomeOrAway($user)) {
                return $acc;
            }
            $opp = $g->opponent($user);
            $oppRanking = current(
                array_filter($rankings, fn($r) => $r->getOwner()->getId() === $opp->getId()));
            if (!count(array_filter($acc, fn($x) => $x['opp']->getOwner()->getId() === $opp->getId()))) {
                $acc[] = ['opp' => $oppRanking, 'won' => $g->scoreOf($user)];
            } else {
                $oppRanking['won'] += $g->scoreOf($user);
            }
            return $acc;
        }, []);
        $X = count($rankings);
        foreach ($oppRanks as $r) {
            $weight = (array_search($r['opp'], $rankings) + 1) / $X;
            $r['won'] * $weight;
        }
        return;
    }
}


