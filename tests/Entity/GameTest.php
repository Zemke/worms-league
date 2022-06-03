<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Game;
use App\Entity\Replay;
use App\Entity\ReplayData;
use App\Entity\User;
use App\Tests\Helper;

class GameTest extends TestCase
{
    public function testFullyProcessed_positive(): void
    {
        $replay1 = (new Replay())->setReplayData(
            (new ReplayData())->setData(['startedAt' => '2022-01-02 19:23:05 GMT']));
        $replay2 = (new Replay())->setReplayData(
            (new ReplayData())->setData(['startedAt' => '2022-01-02 19:23:05 GMT']));
        $game = (new Game())->addReplay($replay1)->addReplay($replay2);
        $this->assertTrue(count($game->getReplays()) === 2);
        $this->assertFalse(empty($game->getReplays()[0]->getReplayData()->getData()));
        $this->assertFalse(empty($game->getReplays()[1]->getReplayData()->getData()));
        $this->assertTrue($game->fullyProcessed());
    }

    public function testFullyProcessed_negativeByMissingReplayData(): void
    {
        $replay1 = (new Replay())->setReplayData(
            (new ReplayData())->setData(['startedAt' => '2022-01-02 19:23:05 GMT']));
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
            (new ReplayData())->setData(['startedAt' => '2022-01-02 19:23:05 GMT']));
        $replay2 = (new Replay())->setReplayData((new ReplayData()));
        $game = (new Game())->addReplay($replay1)->addReplay($replay2);
        $this->assertTrue(count($game->getReplays()) === 2);
        $this->assertFalse(empty($game->getReplays()[0]->getReplayData()->getData()));
        $this->assertTrue(empty($game->getReplays()[1]->getReplayData()->getData()));
        $this->assertFalse($game->fullyProcessed());
    }

    public function testScore(): void
    {
        $daz = Helper::setId(new User(), 1)->setUsername('Daz');
        $mab = Helper::setId(new User(), 2)->setUsername('Mab');
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

    public function testScore_mass(): void
    {
        $users = [];
        $getUser = function ($username) use (&$users) {
            array_key_exists($username, $users)
                ? $users[$username]
                : ($users[$username] = Helper::setId((new User())->setUsername($username), count($users) + 1));
            return $users[$username];
        };
        $f = fopen(dirname(__FILE__) . '/../../src/DataFixtures/csv/games_nnn40.csv', 'r');
        $head = fgetcsv($f);
        $total = 0;
        $correct = 0;

        $Z = new \ZipArchive();
        $res = $Z->open(dirname(__FILE__) . "/../../src/DataFixtures/games_nnn40_stats.zip");
        if ($res === false) {
            throw new \RuntimeException("could not open {$basename} archive");
        }
        $loc = sys_get_temp_dir() . '/php_wl_gametest_scoremass';
        $Z->extractTo($loc);

        while (($row = fgetcsv($f)) !== false) {
            [
                $dateAt,
                $scoreConfirmer,
                $scoreConfirmed,
                $userConfirmer,
                $userConfirmed,
                $uploadId
            ] = $row;
            if (in_array($uploadId, [23273, 22977, 22890, 22893, 22922, 22909, 22989, 22950, 22961])) {
                // 23273, 22977, 22890, 22893, 22922, 22909 incomplete or wrong replay upload
                // 22950 second round is a disconnect that was taken as a draw
                // 22961 second round is a disconnect
                // 22989 last round is a rage quit
                // there are many more erroneous replay sets but I didn't check them all
                continue;
            }
            $game = (new Game())
                ->setHome($getUser($userConfirmer))
                ->setAway($getUser($userConfirmed));
                $files = glob("{$loc}/games_nnn40_stats/{$uploadId}/*/*.json");
                foreach ($files as $file) {
                    $d = json_decode(file_get_contents($file), true);
                    $replay = (new Replay())->setReplayData((new ReplayData())->setData($d));
                    $game->addReplay($replay);
                }
                $game->score();
                $isCorrect = $game->getScoreHome() == $scoreConfirmer && $game->getScoreAway() == $scoreConfirmed;
                $correct += intval($isCorrect);
                $total++;
        }
        // There are many incomplete replays, replays with connections or quits etc.
        $this->assertTrue(dump($correct / $total) > .87);
    }

    public function testReplayData(): void
    {
        $inOrder = [
            '2022-01-02 20:48:59 GMT',
            '2022-01-03 21:48:59 GMT',
            '2022-01-04 21:48:59 GMT',
        ];
        $shuffled = $inOrder;
        $shuffled[] = $inOrder[0];
        unset($shuffled[0]);
        $g = new Game();
        foreach ($shuffled as $dt) {
            $g->addReplay((new Replay())->setReplayData((new ReplayData())->setData(['startedAt' => $dt])));
        }
        $mapFn = fn($v) => new \DateTime($v->getData()['startedAt']);
        $this->assertEquals(
            array_map($mapFn, $g->replayData()),
            array_map(fn($v) => (new \DateTime($v)), $inOrder));
    }

    public function testPlayedAt(): void
    {
        $expected = '2022-01-04 21:48:59 GMT';
        $g = (new Game())
            ->addReplay((new Replay())->setReplayData((new ReplayData())->setData(['startedAt' => '2022-01-02 20:48:59 GMT'])))
            ->addReplay((new Replay())->setReplayData((new ReplayData())->setData(['startedAt' => '2022-01-04 21:48:59 GMT'])))
            ->addReplay((new Replay())->setReplayData((new ReplayData())->setData(['startedAt' => '2022-01-03 21:48:59 GMT'])));
        $this->assertEquals($g->playedAt(), new \DateTime($expected));
    }

    private function stubReplayData(string $winner, array $matchUsers): ReplayData
    {
        $rd = $this->createStub(ReplayData::class);
        $rd->method('winner')->willReturn($winner);
        $rd->method('matchUsers')->willReturn($matchUsers);
        $rd->method('getData')->willReturn(['startedAt' => '2022-01-02 19:23:05 GMT']);
        return $rd;
    }
}
