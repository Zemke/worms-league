<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Tests\Helper;
use App\Entity\Game;
use App\Entity\User;
use App\Entity\Ranking;
use App\Service\RelativizingService;
use App\Thing\Decimal;

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
        $this->assertEquals("1.00000000000000000000", $dazW);
        $this->assertEquals("0.27901785714285714284", $mabW);
        $this->assertEquals("0.60625000000000000000", $zemW);
        $this->assertEquals("0.78125000000000000000", $korW);
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
        $this->assertEquals("0.29629629629629629628", $dazW);
        $this->assertEquals("1.", $mabW);
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
            Helper::setId(new Game(), 2)
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
        $mabW = (new RelativizingService())->byFarming($mab, $rankings, $games);
        $dazW = (new RelativizingService())->byFarming($daz, $rankings, $games);
        $this->assertTrue($dazW->comp($mabW) < 0);
        $this->assertEquals("0.63261669165540608575", $mabW);
        $this->assertEquals("0.41102209799617880958", $dazW);
        // Daz would still get more points but not linearly (by rounds) more.
        $this->assertTrue($dazW->mul(5)->comp($mabW->mul(3)) > 0);
    }

    public function testByEffort(): void
    {
        $kor = Helper::setId(new User(), 2)
            ->setUsername('Kor');
        $mab = Helper::setId(new User(), 3)
            ->setUsername('Mab');
        $daz = Helper::setId(new User(), 4)
            ->setUsername('Daz');
        $rankings = [
            Helper::setId(new Ranking(), 1)
                ->setOwner($kor)
                ->setRoundsPlayed(4)
                ->setRoundsWon(3),
            Helper::setId(new Ranking(), 2)
                ->setOwner($mab)
                ->setRoundsPlayed(3)
                ->setRoundsWon(3),
            Helper::setId(new Ranking(), 3)
                ->setOwner($daz)
                ->setRoundsPlayed(2)
                ->setRoundsWon(3),
        ];
        $dazW = (new RelativizingService())->byEffort($daz, $rankings, []);
        $mabW = (new RelativizingService())->byEffort($mab, $rankings, []);
        $korW = (new RelativizingService())->byEffort($kor, $rankings, []);
        $this->assertTrue($dazW->comp($mabW) > 0 && $mabW->comp($korW) > 0);
        $this->assertTrue($korW->comp(0) > 0);
        $this->assertEquals(strval($dazW->sub($mabW)->add(Decimal::least())), strval($mabW->sub($korW)));
        $this->assertEquals("1.00000000000000000000000000000000000000000000000000", $dazW);
        $this->assertEquals("0.50000000000000000000000000000000000000000000000000", $mabW);
        $this->assertEquals("0.00000000000000000000000000000000000000000000000001", $korW);
        $this->assertEquals(strval(Decimal::least()), $korW);
    }

    public function testByEntropy(): void
    {
        $kor = Helper::setId(new User(), 2)
            ->setUsername('Kor');
        $mab = Helper::setId(new User(), 3)
            ->setUsername('Mab');
        $daz = Helper::setId(new User(), 4)
            ->setUsername('Daz');
        $games = [
            Helper::setId(new Game(), 1)
                ->setHome($daz)->setScoreHome(2)
                ->setAway($kor)->setScoreAway(1)
                ->setCreated(new \DateTime('now -1 hours')),
            Helper::setId(new Game(), 1)
                ->setHome($daz)->setScoreHome(3)
                ->setAway($kor)->setScoreAway(1)
                ->setCreated(new \DateTime('now -2 hours')),
            Helper::setId(new Game(), 3)
                ->setHome($mab)->setScoreHome(3)
                ->setAway($daz)->setScoreAway(1)
                ->setCreated(new \DateTime('now -1 days')),
        ];
        $entropyNorm = [];
        $korW = (new RelativizingService())->byEntropy($kor, $games, $entropyNorm);
        $mabW = (new RelativizingService())->byEntropy($mab, $games, $entropyNorm);
        $dazW = (new RelativizingService())->byEntropy($daz, $games, $entropyNorm);
        $this->assertEquals(
            $korW->comp('0.98478260869565217391304347826086956521739130434782'),
            0);
        $this->assertEquals(
            $mabW->comp('0.30000000000000000000000000000000000000000000000000'),
            0);
        $this->assertEquals(
            $dazW->comp('0.86811594202898550724637681159420289855072463768115'),
            0);
    }
}

