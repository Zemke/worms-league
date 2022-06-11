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
        $this->assertEquals($relRel, 15.2);
        $this->assertEquals($relSteps, 20);
        dump('relRel ' . $relRel);
        dump('relSteps ' . $relSteps);
        $data = $this->gen();
        $diffs = [];
        foreach ($data as $d) {
            array_push($diffs, ...$this->forSeason($d, $relRel, $relSteps));
        }
        $this->assertEquals(
            dump($this->rmse($diffs))->comp('939.95542529391305995353654552622559955813065389536453'),
            0);
    }

    /*
    // Use this test to find the best relRel and relStep values by trying them all out
    // and sorting by standard deviation from NNN ranking
    */
    public function testReCalc_mass(): void
    {
        $data = $this->gen();
        $res = [];
        for ($relRel = D::of(9.2); $relRel->comp(19.1) === -1; $relRel = $relRel->add(.2)) {
            $effRelRel = floatval(strval($relRel));
            dump('relRel ' . $effRelRel);
            for ($relSteps = 10; $relSteps <= 22; $relSteps++) {
                dump('relSteps ' . $relSteps);
                $diffs = [];
                foreach ($data as $d) {
                    array_push($diffs, ...$this->forSeason($d, $effRelRel, $relSteps));
                }
                $res[] = [
                    '_config' => ['relRel' => $effRelRel, 'relSteps' => $relSteps],
                    'rmse' => dump(strval($this->rmse($diffs))),
                ];
            }
        }
        usort($res, fn($r1, $r2) => D::of($r2['rmse'])->comp($r1['rmse']));
        foreach ($res as $r) {
            dump(strval($r['rmse']) . json_encode($r['_config']));
        }
    }

    private function rmse(array $xx) {
        $mse = D::sum(array_map(fn($x) => $x->pow(2), $xx));
        return $mse->sqrt();
    }

    private function gen(): array
    {
        $files = array_filter(
            glob(dirname(__FILE__) . '/../../src/DataFixtures/csv/games_nnn*.csv'),
            fn($f) => !str_ends_with($f, '_nnn38.csv')); // season 38 is corrupt
        return array_map(function ($f) {
            $season = (new Season())
                ->setActive(true)
                ->setName(strtoupper(substr(basename($f), 6, -4)));
            return [
                'season' => $season,
                'gr' => $this->genFromCsv($season),
            ];
        }, $files);
    }

    private function genFromCsv($season): array
    {
        $path = dirname(__FILE__) . '/../../src/DataFixtures/csv';

        // games
        $f = fopen("{$path}/games_{$season->getName()}.csv", 'r');
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
        $rankings = [];
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
            $rankings[] = (new Ranking())
                ->setSeason($season)
                ->setOwner($owner)
                ->setPoints(strval($points));
        }
        return [$games, $rankings,];
    }

    private function forSeason(array $data, float $relRel, int $relSteps): array
    {
        $season = $data['season'];
        [$games, $nnnRankings] = $data['gr'];

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

        // only users with minimum of won rounds
        $cutRankings = array_filter($cut->reCalc($season), fn($r) => $r->getRoundsWon() >= 5);
        $actualRankingOwnerIds = array_map(fn($r) => $r->getOwner()->getId(), $cutRankings);
        $nnnRankings = array_filter(
            $nnnRankings,
            fn($r) => in_array($r->getOwner()->getId(), $actualRankingOwnerIds));

        $this->normPoints($cutRankings);
        $this->normPoints($nnnRankings);

        $diffs = [];
        foreach ($cutRankings as $r) {
            $o = current(array_filter($nnnRankings, fn($r1) => $r1->ownedBy($r->getOwner())));
            $diffs[] = D::of($r->getPoints())->sub($o->getPoints());
        }
        return $diffs;
    }

    private function normPoints(array &$rankings): void
    {
        $norm = new MinMaxNorm(array_map(fn($r) => $r->getPoints(), $rankings), 10, 1_000);
        foreach ($rankings as &$r) {
            $r->setPoints($norm->step($r->getPoints()));
        }
    }
}

