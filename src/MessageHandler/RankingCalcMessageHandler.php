<?php

namespace App\MessageHandler;

use App\Message\RankingCalcMessage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class RankingCalcMessageHandler implements MessageHandlerInterface
{
    public function __invoke(RankingCalcMessage $message)
    {
        // do something with your message
    }
}
