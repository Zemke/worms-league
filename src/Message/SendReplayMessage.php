<?php

namespace App\Message;

final class SendReplayMessage
{
    /*
     * Add whatever properties & methods you need to hold the
     * data for this message class.
     */

    public function __construct(private int $replayId)
    {}

    public function getReplayId(): int
    {
        return $this->replayId;
    }
}

