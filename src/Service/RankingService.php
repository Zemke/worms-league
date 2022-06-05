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
    /** @var float moderate relativizing effect */
    private const REL_REL = 2.6;

    public function __construct(private RankingRepository $rankingRepo,
                                private GameRepository $gameRepo,
                                private SeasonRepository $seasonRepo,
                                private RelativizingService $relativizingService,
                                private EntityManagerInterface $em,)
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
    private function rank(array &$rankings, array $games): void
    {
        usort($rankings, fn($a, $b) => $a->getPoints() - $b->getPoints());
        $X = count($rankings);
        $DP = [];
        for ($i = 0; $i < 5; $i++) {
            foreach ($rankings as &$ranking) {
                $user = $ranking->getOwner();
                $rels = [
                    self::REL_REL,
                    $this->relativizingService->byQuality($user, $rankings, $games, $DP),
                    $this->relativizingService->byFarming($user, $rankings, $games, $DP),
                    $this->relativizingService->byEffort ($user, $rankings, $games, $DP),
                ];
                $ranking->setPoints(
                    strval(D::of($ranking->ranking())->mul(D::sum($rels)->div(count($rels)))));
            }
        }
        return;
    }
}

