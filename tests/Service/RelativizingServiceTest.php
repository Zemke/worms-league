<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Tests\Helper;
use App\Entity\Game;
use App\Entity\User;
use App\Entity\Ranking;
use App\Service\RelativizingService;

class RelativizingServiceTest extends TestCase
{
    public function testByOpponentQuallity(): void
    {

        $zem = Helper::setId(new User(), 1)
            ->setUsername('Zem');
        $mab = Helper::setId(new User(), 2)
            ->setUsername('Mab');
        $daz = Helper::setId(new User(), 3)
            ->setUsername('Daz');
        $kor = Helper::setId(new User(), 4)
            ->setUsername('Kor');
        $games = [
            Helper::setId(new Game(), 1)
                ->setHome($daz)->setScoreHome(3)
                ->setAway($mab)->setScoreAway(2),
            Helper::setId(new Game(), 2)
                ->setHome($daz)->setScoreHome(3)
                ->setAway($mab)->setScoreAway(2),
            Helper::setId(new Game(), 3)
                ->setHome($zem)->setScoreHome(3)
                ->setAway($mab)->setScoreAway(2),
            Helper::setId(new Game(), 4)
                ->setHome($zem)->setScoreHome(2)
                ->setAway($kor)->setScoreAway(1),
            Helper::setId(new Game(), 5)
                ->setHome($kor)->setScoreHome(3)
                ->setAway($mab)->setScoreAway(1),
        ];
        $rankings = [
            Helper::setId(new Ranking(), 2)
                ->setOwner($zem)
                ->setRoundsWon(5),
            Helper::setId(new Ranking(), 3)
                ->setOwner($daz)
                ->setRoundsWon(6),
            Helper::setId(new Ranking(), 3)
                ->setOwner($mab)
                ->setRoundsWon(7),
            Helper::setId(new Ranking(), 3)
                ->setOwner($kor)
                ->setRoundsWon(4),
        ];
        $dazW = (new RelativizingService())->byOpponentQuality($daz, $rankings, $games);
        $mabW = (new RelativizingService())->byOpponentQuality($mab, $rankings, $games);
        $zemW = (new RelativizingService())->byOpponentQuality($zem, $rankings, $games);
        $korW = (new RelativizingService())->byOpponentQuality($kor, $rankings, $games);
        $this->assertEqualsWithDelta($dazW, 1, .00001);
        $this->assertEqualsWithDelta($mabW, .60714285714286, .00001);
        $this->assertEqualsWithDelta($zemW, .7, .00001);
        $this->assertEqualsWithDelta($korW, .875, .00001);
    }
}

