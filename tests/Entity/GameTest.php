<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Game;
use App\Entity\Replay;
use App\Entity\ReplayData;
use App\Entity\User;

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

    public function testScore(): void
    {
        $daz = $this->stubUser(1, 'Daz');
        $mab = $this->stubUser(2, 'Mab');
        $m = ['Daz' => $daz, 'Mab' => $mab];
        $params = [['Daz', $m], ['Mab', $m], ['Daz', $m], ['Daz', $m]];
        $replays = array_map(
            fn($p) => (new Replay())->setReplayData($this->stubReplayData(...$p)),
            $params);
        $game = (new Game())->setHome($daz)->setAway($mab);
        foreach ($replays as $replay) {
            $game->addReplay($replay);
        }
        $game->score();
        $this->assertEquals($game->getScoreHome(), 3);
        $this->assertEquals($game->getScoreAway(), 1);
    }

    private function stubReplayData(string $winner, array $matchUsers): ReplayData
    {
        $rd = $this->createStub(ReplayData::class);
        $rd->method('winner')->willReturn($winner);
        $rd->method('matchUsers')->willReturn($matchUsers);
        $rd->method('getData')->willReturn(['asdf' => 'asdf']);
        return $rd;
    }

    private function stubUser(int $id, ?string $username = null): User
    {
        $stub = $this->createStub(User::class);
        $stub->method('getId')->willReturn($id);
        $stub->method('getUsername')->willReturn($username);
        return $stub;
    }
}
