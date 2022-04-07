<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Season;
use App\Entity\User;
use App\Repository\RankingRepository;
use App\Repository\GameRepository;
use App\Repository\SeasonRepository;
use Doctrine\ORM\EntityManagerInterface;

class RankingService
{

    public function __construct(private RankingRepository $rankingRepo,
                                private GameRepository $gameRepo,
                                private SeasonRepository $seasonRepo,
                                private EntityManagerInterface $em)
    {}

    /**
     * Trigger re-calc because there's a new game to include in the ranking calc.
     */
    public function calc(Game $game): void
    {
        if (!$game->played()) {
            throw new \RuntimeException("Game {$game->getId()} is not played");
        }
        if ($game->getRanked()) {
            throw new \RuntimeException(
                "Won't re-calc as game {$game->getId()} is already ranked");
        }
        $season = $this->seasonRepo->findActive();
        if ($game->getSeason()->getId() !== $season?->getId()) {
            throw new \RuntimeException(sprintf(
                "game season is %s but active season is %s",
                $game->getSeason()->getId(), $season?->getId()));
        }
        $this->reCalc($season);
        $game->setRanked(true);
    }

    /**
     * Take given games into account for a full recalc.
     *
     * @param Game[] games to take into account for re-calc
     */
    public function reCalc(Season $season): void
    {
        $games = $this->gameRepo->findBySeason($season);
        /* TODO sophisticated ranking calc
         * - Ranking formular with these factors
         *   - quality per opponent
         *   - reward activity but don't reward noob bashing
         *     - general activity
         *     - activity against specific opponent
         *   - entropy (older matches value less)
         */

        $rankings = $this->rankingRepo->findBySeason($season);
        foreach ($rankings as &$ranking) {
            $ranking->reset();
            $ranking->updateByAllGames($games);
        }
        $findOrCreate = function (User $user) use ($rankings) {
            $r = current(array_filter($rankings, fn($r) => $r->ownedBy($user)));
            return $r === false ? (new Ranking())->setOwner($user) : $r;
        };
        foreach ($games as $game) {
            if (!$game->played()) {
                continue;
            }
            $homeRanking = $findOrCreate($game->getHome())->updateByGame($game);
            $awayRanking = $findOrCreate($game->getAway())->updateByGame($game);
            $this->em->persist($homeRanking);
            $this->em->persist($awayRanking);
        }
        $this->em->flush();
    }
}

