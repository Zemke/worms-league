<?php

namespace App\Tests\App\Service\Thing;

use PHPUnit\Framework\TestCase;
use App\Thing\MinMaxNorm;

class MinMaxNormTest extends TestCase
{
    public function testFull(): void
    {
        $xx = [1, -124, 38, 19, -23, 3];
        $a = 5;
        $b = 100;
        $normer = new MinMaxNorm($xx, $a, $b);
        $actual = $normer->full();
        uasort($actual, fn($a1, $a2) => $a1->comp($a2));
        asort($xx);
        dump(array_map(fn($d) => strval($d), $actual), $xx);
        $this->assertEquals(array_keys($xx), array_keys($actual));
        $this->assertEquals(array_values($actual)[0]->comp($a), 0);
        $this->assertEquals(array_reverse(array_values($actual))[0]->comp($b), 0);
    }
}

