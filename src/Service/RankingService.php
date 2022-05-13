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
        $rankings = array_reduce($games, function (array $acc, Game $g) use ($season, $games) {
            foreach ([$g->getHome(), $g->getAway()] as $u) {
                if (empty(array_filter($acc, fn($r) => $r->ownedBy($u)))) {
                    $newr = (new Ranking())->setOwner($u)->setSeason($season);
                    $newr->reset();
                    $newr->updateByGames($games);
                    $acc[] = $newr;
                }
            }
            return $acc;
        }, $rankings);
        $this->rank($rankings, $games);
        foreach ($rankings as &$r) {
            $this->em->persist($r);
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
        $DP = [];
        for ($i = 0; $i < 3; $i++) {
            foreach ($rankings as &$ranking) {
                $s = microtime(true);
                $user = $ranking->getOwner();
                $rels = [
                    $this->relativizingService->byQuality($user, $rankings, $games, $DP),
                    $this->relativizingService->byFarming($user, $rankings, $games, $DP),
                    $this->relativizingService->byEffort ($user, $rankings, $games, $DP),
                ];
                if (in_array($user->getUsername(), ['chuvash', 'Kayz', 'Master', 'KinslayeR', 'Rafka'])
                    && $i === 0) {
                    dump($user->getUsername(), $rels);
                }
                //dump(microtime(true) - $s);
                $ranking->setPoints($ranking->ranking() * (array_sum($rels) / count($rels)));
            }
            dump('------------------------------------');
        }
        return;
    }
}

