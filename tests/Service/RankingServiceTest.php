<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Yaml\Yaml;
use App\Entity\Game;
use App\Entity\Ranking;
use App\Entity\Season;
use App\Entity\User;
use App\Repository\RankingRepository;
use App\Repository\GameRepository;
use App\Repository\SeasonRepository;
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
        ['$relRel' => $relRel, '$relSteps' => $relSteps] =
            Yaml::parseFile(dirname(__FILE__) . '/../../config/services.yaml')
                ['services']['App\Service\RankingService']['arguments'];
        // making sure this is like when the test was written
        $this->assertEquals($relRel, 3.4);
        $this->assertEquals($relSteps, 6);
        $seasons = $this->genNnnSeasons();
        $res = [];
        foreach ($seasons as $season) {
            $res[] = dump($this->forSeason($season, $relRel, $relSteps));
        }
        $actual = dump($this->averageSeason($res));
        $avgs = array_column($res, 'avg');
        dump(D::sum($avgs)->div(count($avgs)));
        $this->assertEquals($actual['std']->comp('133.3'), -1);
        $this->assertEquals($actual['var']->comp('299724.2'), -1);
        $this->assertEquals(D::abs($actual['mean'])->comp('21.'), -1);
        $this->assertEquals($actual['avg']->comp('105.5'), -1);
        $this->assertEquals(
            D::max(array_map(fn($d) => D::abs($d), array_column($actual['maxDiffs'], 2)))
                ->comp('394.'),
            -1);
    }

    // Use this test to find the best relRel and relStep values by trying them all out
    // and sorting by standard deviation from NNN ranking
    /*
    public function testReCalc_mass(): void
    {
        $seasons = $this->genNnnSeasons();
        $res = [];
        for ($relRel = D::of(0.); $relRel->comp(10.1) === -1; $relRel = $relRel->add(.2)) {
            $effRelRel = floatval(strval($relRel));
            dump('relRel ' . $effRelRel);
            for ($relSteps = 1; $relSteps <= 10; $relSteps++) {
                dump('relSteps ' . $relSteps);
                $ress = [];
                foreach ($seasons as $season) {
                    $ress[] = $this->forSeason($season, $effRelRel, $relSteps);
                }
                $res[] = dump([
                    '_config' => ['relRel' => $relRel, 'relSteps' => $relSteps],
                    ...$this->averageSeason($ress),
                ]);
            }
        }
        usort($res, fn($r1, $r2) => D::of($r2['avg'])->comp($r1['avg']));
        foreach ($res as $r) {
            dump($r); // prevent truncation by not dumping all at once
        }
    }
    */

    private function averageSeason(array $ress): array
    {
        $numOfSeasons = count($ress);
        return [
            'std' => D::sum(array_column($ress, 'std'))->div($numOfSeasons),
            'var' => D::sum(array_column($ress, 'var'))->div($numOfSeasons),
            'mean'=> D::sum(array_column($ress, 'mean'))->div($numOfSeasons),
            'avg' => D::sum(array_column($ress, 'avg'))->div($numOfSeasons),
            //'maxDiffs' => array_map(fn($diff) => implode(', ', $diff), array_column($ress, 'maxDiff')),
            'maxDiffs' => array_column($ress, 'maxDiff'),
        ];
    }

    private function genNnnSeasons(): array
    {
        $files = glob(dirname(__FILE__) . '/../../src/DataFixtures/csv/games_nnn*.csv');
        $res = array_map(function ($f) {
            $name = substr(basename($f), 6, -4);
            return (new Season())->setActive(true)->setName(strtoupper($name));
        }, $files);
        return array_values(array_filter($res, fn($s) => $s->getName() !== 'NNN38'));
    }

    private function forSeason(Season $season, float $relRel, int $relSteps): array
    {
        $path = dirname(__FILE__) . '/../../src/DataFixtures/csv';

        // games
        $f = fopen("${path}/games_{$season->getName()}.csv", 'r');
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

        // rankings
        $f = fopen("${path}/ranking_{$season->getName()}.tsv", 'r');
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

        $this->normPoints($nnnRankings);
        $this->normPoints($actual);

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
            if (empty($maxDiff) || $maxDiff[2]->comp($absDiff) < 0) {
                $maxDiff = [$r->getOwner()->getUsername(), $diff, $absDiff];
            }
            $diffs[] = $diff;
        }
        return [
            ...$this->std($diffs),
            'avg' => $sum->div(count($actual)),
            'maxDiff' => [$season->getName(), ...array_slice($maxDiff, 0, 2)],
        ];
    }

    private function std(array $xx): array
    {
        $count = count($xx);
        $var = D::zero();
        $mean = D::sum($xx)->div($count);
        foreach ($xx as $x) {
            $var = $var->add($x->sub($mean)->pow(2));
        }
        $std = $var->div($count)->sqrt();
        return [
            'std' => $std,
            'var' => $var,
            'mean' => $mean,
        ];
    }

    private function normPoints(array &$rankings): void
    {
        $norm = new MinMaxNorm(array_map(fn($r) => $r->getPoints(), $rankings), 10, 1_000);
        foreach ($rankings as &$r) {
            $r->setPoints($norm->step($r->getPoints()));
        }
    }
}

