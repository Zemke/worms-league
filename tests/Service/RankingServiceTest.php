<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Entity\Game;
use App\Entity\Season;
use App\Repository\RankingRepository;
use App\Repository\GameRepository;
use App\Repository\SeasonRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\RelativizingService;
use App\Service\RankingService;
use App\Tests\Helper;

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
        $season = (new Season())->setActive(true);
        $this->assertTrue(true);
    }
}
