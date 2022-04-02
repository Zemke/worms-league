<?php

namespace App\Entity;

enum Texture: int
{
    case ART = 5;
    case _BEACH = 0;
    case CHEESE = 6;
    case CONSTRUCTION = 7;
    case DESERT = 8;
    case _DESERT = 1;
    case DUNGEON = 9;
    case EASTER = 10;
    case _FARM = 2;
    case FOREST = 11;
    case _FOREST = 3;
    case FRUIT = 12;
    case GULF = 13;
    case HELL = 14;
    case _HELL = 4;
    case HOSPITAL = 15;
    case JUNGLE = 16;
    case MANHATTAN = 17;
    case MEDIEVAL = 18;
    case MUSIC = 19;
    case PIRATE = 20;
    case SNOW = 21;
    case SPACE = 22;
    case SPORTS = 23;
    case TENTACLE = 24;
    case TIME = 25;
    case TOOLS = 26;
    case TRIBAL = 27;
    case URBAN = 28;

    /**
     * Parse a typical texture string (Data\Level\Cheese) into a Texture enum.
     *
     * @throws UnhandledMatchError when there's no match
     * @return Texture
     */
    public static function parse(string $str): Texture
    {
        $exp = explode('\\', $str);
        return match(strtolower(end($exp))) {
            'art' => Texture::ART,
            '-beach' => Texture::_BEACH,
            'cheese' => Texture::CHEESE,
            'construction' => Texture::CONSTRUCTION,
            'desert' => Texture::DESERT,
            '-desert' => Texture::_DESERT,
            'dungeon' => Texture::DUNGEON,
            'easter' => Texture::EASTER,
            '-farm' => Texture::_FARM,
            'forest' => Texture::FOREST,
            '-forest' => Texture::_FOREST,
            'fruit' => Texture::FRUIT,
            'gulf' => Texture::GULF,
            'hell' => Texture::HELL,
            '-hell' => Texture::_HELL,
            'hospital' => Texture::HOSPITAL,
            'jungle' => Texture::JUNGLE,
            'manhattan' => Texture::MANHATTAN,
            'medieval' => Texture::MEDIEVAL,
            'music' => Texture::MUSIC,
            'pirate' => Texture::PIRATE,
            'snow' => Texture::SNOW,
            'space' => Texture::SPACE,
            'sports' => Texture::SPORTS,
            'tentacle' => Texture::TENTACLE,
            'time' => Texture::TIME,
            'tools' => Texture::TOOLS,
            'tribal' => Texture::TRIBAL,
            'urban' => Texture::URBAN,
        };
    }

    public function local(): string
    {
        $fn = fn($str) => ucfirst(strtolower($str));
        if (str_contains($this->name, '_')) {
            return '-' . $fn(substr($this->name, 1));
        }
        return $fn($this->name);
    }
}

