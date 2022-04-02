<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Texture;

class TextureTest extends TestCase
{
    public function testLocal(): void
    {
        $this->assertEquals(Texture::_BEACH->local(), '-Beach');
        $this->assertEquals(Texture::DESERT->local(), 'Desert');
    }

    public function testParse(): void
    {
        $this->assertEquals(Texture::parse('Data\Level\Cheese'), Texture::CHEESE);
        $this->assertEquals(Texture::parse('Data\Level\-Hell'), Texture::_HELL);
    }
}
