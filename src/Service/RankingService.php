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
use App\Thing\Decimal as D;

class RankingService
{
    public function __construct(private RankingRepository $rankingRepo,
                                private GameRepository $gameRepo,
                                private SeasonRepository $seasonRepo,
                                private RelativizingService $relativizingService,
                                private EntityManagerInterface $em,
                                private float $relRel,
                                private int $relSteps,)
    {}

    /**
     * Trigger re-calc because there's a new game to include in the ranking calc.
     */
    public function calc(Game &$game): void
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
        $this->em->flush();
    }

    /**
     * Take given games into account for a full recalc.
     *
     * @param Season $season The season to run re-calc for.
     * @return Ranking[] Rankings of the given season.
     */
    public function reCalc(Season $season): array
    {
        $games = array_filter(
            $this->gameRepo->findBySeason($season),
            fn($g) => $g->fullyProcessed());
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
        foreach ($games as &$g) {
            $g->setRanked(true);
        }
        $this->em->flush();
        return $rankings;
    }

    /**
     * Points pattern.
     *
     * @param Ranking[] $rankings
     * @param Game[] $games
     */
    private function rank(array &$rankings, array $games): void
    {
        $n = array_reduce(
            $rankings,
            fn($acc, $r) => max($acc, $r->getRoundsPlayed()),
            0);
        $relSteps = (int) round(9 + log($n, 10) * 4.8);
        $X = count($rankings);
        $DP = [];
        for ($i = 0; $i <= $relSteps; $i++) {
            foreach ($rankings as &$ranking) {
                $user = $ranking->getOwner();
                $rels = [
                    $this->relRel,
                    $this->relativizingService->byQuality($user, $rankings, $games, $DP),
                    $this->relativizingService->byFarming($user, $rankings, $games, $DP),
                    $this->relativizingService->byEffort($user, $rankings, $games, $DP),
                ];
                $ranking->setPoints(
                    strval(D::of($ranking->ranking())->mul(D::sum($rels)->div(count($rels)))));
            }
        }
        return;
    }
}

