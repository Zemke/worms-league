<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Game;
use App\Entity\Replay;
use App\Entity\ReplayData;
use App\Entity\User;
use App\Tests\Helper;

class ReplayDataTest extends TestCase
{
    public function testMatchUsers(): void
    {
        $d = [
            'teams' => [
                ['user' => 'Zemke'],
                ['user' => 'Rafka'],
            ]
        ];
        $rd = (new ReplayData())->setData($d);
        $uZemke = Helper::setId(new User(), 1)->setUsername('Zemke');
        $uRafka = Helper::setId(new User(), 2)->setUsername('Rafka');
        $this->assertEquals(
            $rd->matchUsers($uZemke, $uRafka),
            ['Zemke' => $uZemke, 'Rafka' => $uRafka]);
    }

    public function testMatchUsers_MabDaz(): void
    {
        $d = [
            'teams' => [
                ['user' => 'Daz'],
                ['user' => 'Mab'],
            ]
        ];
        $rd = (new ReplayData())->setData($d);
        $uDaz = Helper::setId(new User(), 1)->setUsername('Dario');
        $uMab = Helper::setId(new User(), 2)->setUsername('Mablak');
        $this->assertEquals(
            $rd->matchUsers($uMab, $uDaz),
            ['Daz' => $uDaz, 'Mab' => $uMab]);
    }

    public function testMatchUsers_clanTags(): void
    {
        $d = [
            'teams' => [
                ['user' => 'NNNlKoras'],
                ['user' => 'NNNxKayz'],
            ]
        ];
        $rd = (new ReplayData())->setData($d);
        $uKayz = Helper::setId(new User(), 1)->setUsername('Kayz');
        $uKoras = Helper::setId(new User(), 2)->setUsername('Koras');
        $this->assertEquals(
            $rd->matchUsers($uKoras, $uKayz),
            ['NNNxKayz' => $uKayz, 'NNNlKoras' => $uKoras]);
        $this->assertEquals(
            $rd->matchUsers($uKoras, $uKayz),
            ['NNNlKoras' => $uKoras, 'NNNxKayz' => $uKayz]);
        $this->assertEquals(
            $rd->matchUsers($uKayz, $uKoras),
            ['NNNlKoras' => $uKoras, 'NNNxKayz' => $uKayz]);
        $this->assertEquals(
            $rd->matchUsers($uKayz, $uKoras),
            [ 'NNNxKayz' => $uKayz, 'NNNlKoras' => $uKoras]);
    }

    public function testNames(): void
    {
        $d = [
            'teams' => [
                ['user' => 'Daz'],
                ['user' => 'Mab'],
            ]
        ];
        $rd = (new ReplayData())->setData($d);
        $this->assertEquals(['Daz', 'Mab'], $rd->names());
    }

    public function testWinner(): void
    {
        $d = [
            'teams' => [
                ['user' => 'Daz'],
                ['user' => 'Mab'],
            ],
            'turns' => [
                [
                    'damages' => [
                        [
                            'victim' => 'Daz',
                            'kills' => 2
                        ]
                    ],
                ],
                [
                    'damages' => [
                        [
                            'victim' => 'Mab',
                            'kills' => 5
                        ]
                    ],
                ],
                [
                    'damages' => [
                        [
                            'victim' => 'Daz',
                            'kills' => 4
                        ]
                    ],
                ],
            ]
        ];
        $this->assertEquals((new ReplayData())->setData($d)->winner(), 'Mab');

        $d['turns'][] = [
            'damages' => [
                [
                    'victim' => 'Mab',
                    'kills' => 2
                ]
            ],
        ];
        $this->assertEquals((new ReplayData())->setData($d)->winner(), 'Daz');

        $d['turns'][] = [
            'damages' => [
                [
                    'victim' => 'Daz',
                    'kills' => 1
                ]
            ],
        ];
        $this->assertNull((new ReplayData())->setData($d)->winner());
    }

    public function testScore(): void
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
                continue;
            }
            $game = (new Game())
                ->setHome($getUser($userConfirmer))
                ->setAway($getUser($userConfirmed));
                $files = glob(dirname(__FILE__) . "/../../src/DataFixtures/games_nnn40_stats/{$uploadId}/*/*.json");
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
}

