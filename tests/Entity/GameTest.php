<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Game;
use App\Entity\Replay;
use App\Entity\ReplayData;

class GameTest extends TestCase
{
    public function testFullyProcessed_positive(): void
    {
        $replay1 = (new Replay())->setReplayData(
            (new ReplayData())->setData(['hello' => 'world']));
        $replay2 = (new Replay())->setReplayData(
            (new ReplayData())->setData(['hello' => 'world']));
        $game = (new Game())->addReplay($replay1)->addReplay($replay2);
        $this->assertTrue(count($game->getReplays()) === 2);
        $this->assertFalse(empty($game->getReplays()[0]->getReplayData()->getData()));
        $this->assertFalse(empty($game->getReplays()[1]->getReplayData()->getData()));
        $this->assertTrue($game->fullyProcessed());
    }

    public function testFullyProcessed_negativeByMissingReplayData(): void
    {
        $replay1 = (new Replay())->setReplayData(
            (new ReplayData())->setData(['hello' => 'world']));
        $replay2 = new Replay();
        $game = (new Game())->addReplay($replay1)->addReplay($replay2);
        $this->assertTrue(count($game->getReplays()) === 2);
        $this->assertFalse(empty($game->getReplays()[0]->getReplayData()->getData()));
        $this->assertTrue(is_null($game->getReplays()[1]->getReplayData()));
        $this->assertFalse($game->fullyProcessed());
    }

    public function testFullyProcessed_negativeByEmptyData(): void
    {
        $replay1 = (new Replay())->setReplayData(
            (new ReplayData())->setData(['hello' => 'world']));
        $replay2 = (new Replay())->setReplayData((new ReplayData()));
        $game = (new Game())->addReplay($replay1)->addReplay($replay2);
        $this->assertTrue(count($game->getReplays()) === 2);
        $this->assertFalse(empty($game->getReplays()[0]->getReplayData()->getData()));
        $this->assertTrue(empty($game->getReplays()[1]->getReplayData()->getData()));
        $this->assertFalse($game->fullyProcessed());
    }
}
