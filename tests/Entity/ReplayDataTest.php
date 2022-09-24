<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
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
        $this->markTestSkipped('invalid because test data json has no winsTheMatch element');
        $d = [
            'startedAt' => '2022-01-02 19:23:05 GMT',
            'gameEnd' => '00:19:07.46',
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
                    'weapons' => [],
                ],
                [
                    'damages' => [
                        [
                            'victim' => 'Mab',
                            'kills' => 5
                        ]
                    ],
                    'weapons' => [],
                ],
                [
                    'damages' => [
                        [
                            'victim' => 'Daz',
                            'kills' => 4
                        ]
                    ],
                    'weapons' => [],
                ],
            ]
        ];
        $this->assertEquals((new ReplayData())->setData($d)->winner(), 'Mab');

        $d['turns'][] = [
            'damages' => [
                [
                    'victim' => 'Mab',
                    'kills' => 2
                ],
            ],
            'weapons' => [],
        ];
        $this->assertEquals((new ReplayData())->setData($d)->winner(), 'Daz');

        $d['turns'][] = [
            'damages' => [
                [
                    'victim' => 'Daz',
                    'kills' => 1
                ],
            ],
            'weapons' => [],
        ];
        $this->assertNull((new ReplayData())->setData($d)->winner());
    }
}

