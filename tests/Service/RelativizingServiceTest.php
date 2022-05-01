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

        $mab = Helper::setId(new User(), 2)
            ->setUsername('Mab');
        $daz = Helper::setId(new User(), 3)
            ->setUsername('Daz');
        $games = [
            Helper::setId(new Game(), 1)
                ->setHome($daz)->setScoreHome(3)
                ->setAway($mab)->setScoreAway(2),
        ];
        $rankings = [
            Helper::setId(new Ranking(), 2)
                ->setOwner($mab)
                ->setRoundsWon(2),
            Helper::setId(new Ranking(), 3)
                ->setOwner($daz)
                ->setRoundsWon(3),
        ];
        // Daz beat Mab. Therefore in the absolute ranking Daz leads Mab.
        // Mab's won rounds agains the first placed Daz are therefore weighted at 100%.
        $this->assertEquals(
           (new RelativizingService())->byOpponentQuality($daz, $rankings, $games),
           .5);
        $this->assertEquals(
           (new RelativizingService())->byOpponentQuality($mab, $rankings, $games),
           1);
    }
}
