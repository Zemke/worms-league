<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\ReplayData;
use App\Entity\User;

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
        $uZemke = $this->user(1, 'Zemke');
        $uRafka = $this->user(2, 'Rafka');
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
        $uDaz = $this->user(1, 'Dario');
        $uMab = $this->user(2, 'Mablak');
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
        $uKayz = $this->user(1, 'Kayz');
        $uKoras = $this->user(2, 'Koras');
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

    private function user(int $id, string $username): User
    {
        $m = $this->getMockBuilder(User::class)->getMock();
        $m->method('getId')->willReturn($id);
        $m->method('getUsername')->willReturn($username);
        return $m;
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
}

