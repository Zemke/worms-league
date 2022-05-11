<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\Ranking;
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
                                private RelativizingService $relativizingService,
                                private EntityManagerInterface $em,)
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
     * @param Season $season The season to run re-calc for.
     */
    public function reCalc(Season $season): void
    {
        $games = $this->gameRepo->findBySeason($season);
        usort($games, fn($a, $b) => $a->playedAt()->diff($b->playedAt())->f);

        $rankings = $this->rankingRepo->findBySeason($season);
        foreach ($rankings as &$ranking) {
            $ranking->reset();
            $ranking->updateByGames($games);
        }
        $findOrCreate = function (User $user) use ($rankings, $season) {
            $r = current(array_filter($rankings, fn($r) => $r->ownedBy($user)));
            return $r === false ? (new Ranking())->setOwner($user)->setSeason($season) : $r;
        };
        foreach ($games as $game) {
            if (!$game->played() || !$game->fullyProcessed()) {
                continue;
            }
            $homeRanking = $findOrCreate($game->getHome())->updateByGame($game);
            $awayRanking = $findOrCreate($game->getAway())->updateByGame($game);
            $this->em->persist($homeRanking);
            $this->em->persist($awayRanking);
        }
        $this->em->flush();
    }

    /**
     * Points pattern.
     *
     * @param Ranking[] $rankings
     * @param Game[] $games
     */
    public function rank(array &$rankings, array $games): void
    {
        /* TODO
        There's an absolute ranking which is simply won rounds.
        This is then relativized through several weighted factors.
        Each relativizing step is based on each previous step.
        The first one is based on the absolute ranking.

        These are the relativizing factors applied
        on the won rounds (relativizing won rounds):
        - How good is the opponent?
        - Entropy values older rounds less.
        - Opponent variety. Winning rounds against different opponents is
          worth more than beating the same over and over.
        - De-value activity by relativizing amount rounds.

        The more relativizing steps are run, the more the absolute ranking
        is relativized and the less worth is allocated to activity.
        */
        usort($rankings, fn($a, $b) => $a->getPoints() - $b->getPoints());
        $X = count($rankings);
        foreach ($rankings as &$ranking) {
            $user = $ranking->getOwner();
            $this->relativizingService->byOpponentQuality($user, $rankings, $games);
        }
        return;
    }
}

