<?php

namespace App\Service;

use App\Entity\Game;
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
        $games = $this->gameRepo->findBySeason($season);
        $this->reCalc($games);
        $game->setRanked(true);
    }

    /**
     * Take given games into account for a full recalc.
     *
     * @param Game[] games to take into account for re-calc
     */
    public function reCalc(array $games): void
    {
        /* TODO sophisticated ranking calc
         * - Ranking formular with these factors
         *   - quality per opponent
         *   - reward activity but don't reward noob bashing
         *     - general activity
         *     - activity against specific opponent
         *   - entropy (older matches value less)
         */

        // TODO it's full re-calc, reset ranking before

        foreach ($games as $game) {
            if (!$game->played()) {
                continue;
            }
            if ($game->draw()) {
                $home = $this->rankingRepo->findOneOrCreate($game->getHome(), $game->getSeason());
                $away = $this->rankingRepo->findOneOrCreate($game->getAway(), $game->getSeason());
                $home->plusPoints(1);
                $away->plusPoints(1);
                $this->em->persist($home);
                $this->em->persist($away);
            } else {
                $winner = $this->rankingRepo->findOneOrCreate($game->winner(), $game->getSeason());
                $loser = $this->rankingRepo->findOneOrCreate($game->loser(), $game->getSeason());
                $winner->plusPoints(3);
                $this->em->persist($winner);
            }
        }
        $this->em->flush();
    }

    public function report(Game $game): Game
    {
        $winner = $this->rankingRepo->findOneOrCreate($game->winner(), $game->getSeason());
        $loser = $this->rankingRepo->findOneOrCreate($game->loser(), $game->getSeason());
        if ($game->draw()) {
            $winner->plusPoints(1);
            $loser->plusPoints(1);
            $this->em->persist($winner);
            $this->em->persist($loser);
        } else {
            $winner->plusPoints(3);
            $this->em->persist($winner);
        }
        $this->em->persist($game);
        $this->em->flush();
        return $game;
    }
}

