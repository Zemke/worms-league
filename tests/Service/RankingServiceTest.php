<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Entity\Game;
use App\Entity\Ranking;
use App\Entity\Season;
use App\Entity\User;
use App\Repository\RankingRepository;
use App\Repository\GameRepository;
use App\Repository\SeasonRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\RelativizingService;
use App\Service\RankingService;
use App\Tests\Helper;
use App\Tests\GamesGenerator;
use App\Thing\Decimal as D;
use App\Thing\MinMaxNorm;

class RankingServiceTest extends TestCase
{
    public function testCalc(): void
    {
        [$g, $cut] = $this->mockForCalc();
        $g->method('played')->willReturn(true);
        $g->method('getRanked')->willReturn(false);
        $cut->expects($this->once())
            ->method('reCalc');
        $g->expects($this->once())
            ->method('setRanked')
            ->with($this->equalTo(true));
        $cut->calc($g);
    }

    public function testCalc_notPlayed(): void
    {
        [$g, $cut] = $this->mockForCalc();
        $g->method('played')->willReturn(false);
        $g->method('getRanked')->willReturn(false);
        $cut->expects($this->never())
            ->method('reCalc');
        $g->expects($this->never())
            ->method('setRanked');
        $this->expectException(\RuntimeException::class);
        $cut->calc($g);
    }

    public function testCalc_alreadyRanked(): void
    {
        [$g, $cut] = $this->mockForCalc();
        $g->method('played')->willReturn(true);
        $g->method('getRanked')->willReturn(true);
        $cut->expects($this->never())
            ->method('reCalc');
        $g->expects($this->never())
            ->method('setRanked');
        $this->expectException(\RuntimeException::class);
        $cut->calc($g);
    }

    public function testCalc_differentSeason(): void
    {
        [$g, $cut] = $this->mockForCalc(Helper::setId(new Season(), 2));
        $g->method('played')->willReturn(true);
        $g->method('getRanked')->willReturn(false);
        $cut->expects($this->never())
            ->method('reCalc');
        $g->expects($this->never())
            ->method('setRanked')
            ->with($this->equalTo(true));
        $this->expectException(\RuntimeException::class);
        $cut->calc($g);
    }

    private function mockForCalc(?Season $activeSeason = null): array
    {
        $season = Helper::setId(new Season(), 1);
        $seasonRepo = $this->getMockBuilder(SeasonRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $seasonRepo->method('findActive')
            ->willReturn(is_null($activeSeason) ? $season : $activeSeason);
        $cut = $this->getMockBuilder(RankingService::class)
            ->setConstructorArgs([
                $this->createMock(RankingRepository::class),
                $this->createMock(GameRepository::class),
                $seasonRepo,
                $this->createMock(RelativizingService::class),
                $this->createMock(EntityManagerInterface::class),
                2.6,
                5,
            ])
            ->setMethodsExcept(['calc'])
            ->getMock();
        $g = $this->createMock(Game::class);
        $g->method('getSeason')->willReturn($season);
        return [$g, $cut];
    }

    public function testReCalc(): void
    {
        $A = 10;
        $B = 1_000;
        $files = glob(dirname(__FILE__) . '/../../src/DataFixtures/csv/games_nnn*.csv');
        $numOfSeasons = count($files);
        $res = [];
        for ($relRel = 0.; $relRel <= 5; $relRel += .2) {
            dump('relRel ' . $relRel);
            for ($relSteps = 1; $relSteps <= 10; $relSteps++) {
                dump('relSteps ' . $relSteps);
                $ress = [
                    'std' => [],
                    'var' => [],
                    'mean' => [],
                    'avg' => [],
                    'maxDiffs' => [],
                ];
                foreach ($files as $file) {
                    $name = substr(basename($file), 6, -4);
                    $season = Helper::setId(
                        (new Season())->setActive(true)->setName(strtoupper($name)),
                        1);
                    dump($season->getName());

                    // games
                    $f = fopen($file, 'r');
                    $users = [];
                    $games = [];
                    GamesGenerator::fromCsv($season, $f, $users, false, function ($o) use (&$users, &$games) {
                        if ($o instanceof User) {
                            $users[] = Helper::setId($o, count($users));
                        } else if ($o instanceof Game) {
                            $games[] = Helper::setId($o, count($games));
                        } else {
                            throw new \RuntimeException('exhaustion ' . get_class($o));
                        }
                    });

                    /*
                    $splitStatFn = function ($s) {
                        $splt = explode('-', $s);
                        return [intval($splt[0]), intval(substr($splt[1], 1, -1))];
                    };
                    */

                    // rankings
                    $f = fopen(dirname($file) . '/ranking_' . $name . '.tsv', 'r');
                    $head = fgetcsv($f, 0, "\t");
                    $nnnRankings = [];
                    while (($row = fgetcsv($f, 0, "\t")) !== false) {
                        [
                          $rank, // 24
                          $nickname, // CzarnyKot
                          $points, // 0.091
                          $rounds, // 0-(2)
                          // $games, // 0-(1)
                          // $activity, // 0 very poor
                          // $streak, // 1 lost
                        ] = $row;
                        $owner = current(array_filter($users, fn($u) => $u->getUsername() === $nickname));
                        if ($owner === false) {
                            $owner = Helper::setId(
                                (new User())->setUsername($nickname),
                                count($users)
                            );
                            $users[] = $owner;
                        }
                        $nnnRankings[] = (new Ranking())
                            ->setSeason($season)
                            ->setOwner($owner)
                            ->setPoints(strval($points));
                    }

                    $gameRepo = $this->getMockBuilder(GameRepository::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['findBySeason'])
                        ->getMock();
                    $rankingRepo = $this->getMockBuilder(RankingRepository::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['findBySeason'])
                        ->getMock();
                    $gameRepo->method('findBySeason')->willReturn($games);
                    $rankingRepo->method('findBySeason')->willReturn([]);
                    $cut = $this->getMockBuilder(RankingService::class)
                        ->setConstructorArgs([
                            $rankingRepo,
                            $gameRepo,
                            $this->createMock(SeasonRepository::class),
                            new RelativizingService(),
                            $this->createMock(EntityManagerInterface::class),
                            $relRel,
                            $relSteps,
                        ])
                        ->setMethodsExcept(['reCalc', 'calc', 'rank'])
                        ->getMock();
                    $actual = array_filter($cut->reCalc($season), fn($r) => $r->getRoundsWon() >= 5);
                    $actualUserIds = array_map(fn($r) => $r->getOwner()->getId(), $actual);
                    $nnnRankings = array_filter($nnnRankings, fn($r) => in_array($r->getOwner()->getId(), $actualUserIds));

                    // TODO For some reason NNN ranking is missing users that have games played in that season.  Potentially the whole NNN ranking cannot be trusted.
                    $nnnUserIds = array_map(fn($r) => $r->getOwner()->getId(), $nnnRankings);
                    $actual = array_filter($actual, fn($r) => in_array($r->getOwner()->getId(), $nnnUserIds));

                    $nnnPoints = array_map(fn($r) => $r->getPoints(), $nnnRankings);
                    $actPoints = array_map(fn($r) => $r->getPoints(), $actual);
                    $allPoints = [...$nnnPoints, ...$actPoints];
                    $nnnNormer = new MinMaxNorm($nnnPoints, $A, $B);
                    foreach ($nnnRankings as &$r) {
                        $r->setPoints($nnnNormer->step($r->getPoints()));
                    }
                    $actualNormer = new MinMaxNorm($actPoints, $A, $B);
                    foreach ($actual as &$r) {
                        $r->setPoints($actualNormer->step($r->getPoints()));
                    }

                    // uncomment and run the following command to see the tables
                    // ./bin/phpunit --filter ::testReCalc tests/Service/RankingServiceTest.php | grep 'username\|points'
                    //usort($nnnRankings, fn($r1, $r2) => D::of($r2->getPoints())->comp($r1->getPoints()));
                    //usort($actual, fn($r1, $r2) => D::of($r2->getPoints())->comp($r1->getPoints()));
                    //dump($actual);
                    //dump('----- username -----');
                    //dump($nnnRankings);
                    //die();

                    $sum = D::zero();
                    $diffs = [];
                    $maxDiff = [];
                    foreach ($actual as $r) {
                        $o = current(array_filter($nnnRankings, fn($r1) => $r1->ownedBy($r->getOwner())));
                        $diff = D::of($r->getPoints())->sub($o->getPoints());
                        $absDiff = D::abs($diff);
                        $sum = $sum->add($absDiff);
                        if (empty($maxDiff) || $maxDiff[0] < $absDiff) {
                            $maxDiff = [$season->getName(), $r->getOwner()->getUsername(), $absDiff];
                        }
                        //dump($diff . ' ' . $r->getOwner()->getUsername());
                        $diffs[] = $diff;
                    }
                    dump('max diff ' . json_encode([$maxDiff[0], $maxDiff[1], strval($maxDiff[2])]));
                    $avg = $sum->div(count($actual));
                    dump('avg ' . $avg);
                    $count = count($diffs);
                    $variance = D::zero();
                    $mean = D::sum($diffs)->div($count);
                    dump('mean ' . $mean);
                    foreach ($diffs as $diff) {
                        $variance = $variance->add($diff->sub($mean)->pow(2));
                    }
                    dump('var ' . $variance->div($count));
                    $std = $variance->div($count)->sqrt();
                    dump('std ' . $std);
                    $ress['std'][] = $std;
                    $ress['var'][] = $variance;
                    $ress['mean'][] = $mean;
                    $ress['avg'][] = $avg;
                    $ress['maxDiffs'][] = implode(', ', $maxDiff);
                }
                $res[] = [
                    '_config' => ['relRel' => $relRel, 'relSteps' => $relSteps],
                    'std' => strval(D::sum($ress['std'])->div($numOfSeasons)),
                    'var' => strval(D::sum($ress['var'])->div($numOfSeasons)),
                    'mean'=> strval(D::sum($ress['mean'])->div($numOfSeasons)),
                    'avg' => strval(D::sum($ress['avg'])->div($numOfSeasons)),
                    'maxDiffs' => $ress['maxDiffs'],
                ];
            }
        }
        usort($res, fn($r1, $r2) => D::of($r2['std'])->comp($r1['std']));
        foreach ($res as $r) {
            dump($r); // prevent truncation by not dumping all at once
        }
    }
}

