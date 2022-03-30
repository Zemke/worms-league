<?php

namespace App\Message;

final class RankingCalcMessage
{
    public function __construct(private int $gameId)
    { }

    public function getGameId(): string
    {
        return $this->gameId;
    }
}
