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
    public function testByQuality(): void
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
            Helper::setId(new Ranking(), 1)
                ->setOwner($zem)
                ->setRoundsWon(5),
            Helper::setId(new Ranking(), 2)
                ->setOwner($daz)
                ->setRoundsWon(6),
            Helper::setId(new Ranking(), 3)
                ->setOwner($mab)
                ->setRoundsWon(7),
            Helper::setId(new Ranking(), 4)
                ->setOwner($kor)
                ->setRoundsWon(4),
        ];
        $dazW = (new RelativizingService())->byQuality($daz, $rankings, $games);
        $mabW = (new RelativizingService())->byQuality($mab, $rankings, $games);
        $zemW = (new RelativizingService())->byQuality($zem, $rankings, $games);
        $korW = (new RelativizingService())->byQuality($kor, $rankings, $games);
        $this->assertEqualsWithDelta($dazW, 1, .00001);
        $this->assertEqualsWithDelta($mabW, .2790178571428571, .00001);
        $this->assertEqualsWithDelta($zemW, .60625, .00001);
        $this->assertEqualsWithDelta($korW, .78125, .00001);
    }

    public function testByQuality_fair(): void
    {
        $zem = Helper::setId(new User(), 1)
            ->setUsername('Zem');
        $kor = Helper::setId(new User(), 2)
            ->setUsername('Kor');
        $mab = Helper::setId(new User(), 3)
            ->setUsername('Mab');
        $daz = Helper::setId(new User(), 4)
            ->setUsername('Daz');
        $games = [
            Helper::setId(new Game(), 1)
                ->setHome($daz)->setScoreHome(3)
                ->setAway($kor)->setScoreAway(1),
            Helper::setId(new Game(), 1)
                ->setHome($mab)->setScoreHome(3)
                ->setAway($zem)->setScoreAway(1),
        ];
        $rankings = [
            Helper::setId(new Ranking(), 1)
                ->setOwner($zem)
                ->setRoundsWon(20),
            Helper::setId(new Ranking(), 2)
                ->setOwner($kor)
                ->setRoundsWon(10),
            Helper::setId(new Ranking(), 3)
                ->setOwner($daz)
                ->setRoundsWon(3),
            Helper::setId(new Ranking(), 4)
                ->setOwner($mab)
                ->setRoundsWon(3),
        ];
        $dazW = (new RelativizingService())->byQuality($daz, $rankings, $games);
        $mabW = (new RelativizingService())->byQuality($mab, $rankings, $games);
        $this->assertEqualsWithDelta($dazW, .2962962962962962, .00001);
        $this->assertEqualsWithDelta($mabW, 1., .00001);
    }

    /**
     * Value of won rounds doesn't increase linearly against the same opponent.
     */
    public function testByFarming_notLinear(): void
    {
        $kor = Helper::setId(new User(), 2)
            ->setUsername('Kor');
        $mab = Helper::setId(new User(), 3)
            ->setUsername('Mab');
        $daz = Helper::setId(new User(), 4)
            ->setUsername('Daz');
        $games = [
            Helper::setId(new Game(), 1)
                ->setHome($daz)->setScoreHome(5)
                ->setAway($kor)->setScoreAway(0),
            Helper::setId(new Game(), 1)
                ->setHome($mab)->setScoreHome(3)
                ->setAway($kor)->setScoreAway(0),
        ];
        $rankings = [
            Helper::setId(new Ranking(), 2)
                ->setOwner($kor)
                ->setRoundsWon(10),
            Helper::setId(new Ranking(), 3)
                ->setOwner($daz)
                ->setRoundsWon(5),
            Helper::setId(new Ranking(), 4)
                ->setOwner($mab)
                ->setRoundsWon(3),
        ];
        $dazW = (new RelativizingService())->byFarming($daz, $rankings, $games);
        $mabW = (new RelativizingService())->byFarming($mab, $rankings, $games);
        $this->assertTrue($dazW < $mabW);
        $this->assertEqualsWithDelta($mabW, .6326166916554051458844579, .00001);
        $this->assertEqualsWithDelta($dazW, .4110220979961794394653125, .00001);
        // Daz would still get more points but not linearly (by rounds) more.
        $this->assertTrue((5 * $dazW) > (3 * $mabW));
    }
}

