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
use App\Tests\Data;
use App\Thing\Decimal as D;

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
            ])
            ->setMethodsExcept(['calc'])
            ->getMock();
        $g = $this->createMock(Game::class);
        $g->method('getSeason')->willReturn($season);
        return [$g, $cut];
    }

    public function testReCalc(): void
    {
        $season = Helper::setId(
            (new Season())->setActive(true)->setName('NNN41CURRENT'),
            1);

        // games
        $f = fopen(
            dirname(__FILE__)
                . '/../../src/DataFixtures/csv/games_nnn41current'
                . '.csv',
            'r');
        $users = [];
        $games = [];
        Data::gamesFromCsv($season, $f, $users, function ($o) use (&$users, &$games) {
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
        $f = fopen(
            dirname(__FILE__)
                . '/../../src/DataFixtures/csv/ranking_nnn41current'
                . '.tsv',
            'r');
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
            ])
            ->setMethodsExcept(['reCalc', 'calc', 'rank'])
            ->getMock();
        $actual = array_filter($cut->reCalc($season), fn($r) => $r->getRoundsWon() >= 5);
        $actualUserIds = array_map(fn($r) => $r->getOwner()->getId(), $actual);
        $nnnRankings = array_filter($nnnRankings, fn($r) => in_array($r->getOwner()->getId(), $actualUserIds));
        $nnnPoints = array_map(fn($r) => $r->getPoints(), $nnnRankings);
        $actPoints = array_map(fn($r) => $r->getPoints(), $actual);
        $a = D::min($actPoints);
        $b = D::max($actPoints);
        $mn = D::min($nnnPoints);
        $mx = D::max($nnnPoints);
        foreach ($nnnRankings as &$r) {
            $r->setPoints(D::of($a)
                ->add(
                    D::of($r->getPoints())->sub($mn)
                        ->mul($b->sub($a))
                        ->div($mx->sub($mn))
                ));
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
        foreach ($actual as $r) {
            $o = current(array_filter($nnnRankings, fn($r1) => $r1->ownedBy($r->getOwner())));
            $diff = D::of($r->getPoints())->sub($o->getPoints());
            $sum = $sum->add(D::abs($diff));
            $diffs[] = $diff;
        }
        echo "\n";
        foreach ($diffs as $diff) {
            echo strval($diff) . ",";
        }
        //$diffs = array_map(fn($n) => D::of($n), [10, 12, 23, 23, 16, 23, 21, 16]);
        dump('diffs ' . json_encode(array_map(fn($d) => strval($d), $diffs)));
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
    }
}

