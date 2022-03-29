<?php

namespace App\MessageHandler;

use App\Message\SendReplayMessage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use App\Repository\ReplayRepository;
use App\Repository\ReplayDataRepository;
use App\Service\WaaasService;

final class SendReplayMessageHandler implements MessageHandlerInterface
{
    public function __construct(private WaaasService $waaasService,
                                private ReplayRepository $replayRepo,
                                private ReplayDataRepository $replayDataRepo,)
    {}

    public function __invoke(SendReplayMessage $message)
    {
        $replay = $this->replayRepo->find($message->getReplayId());
        $replayData = $this->waaasService->send($replay);
        $this->replayDataRepo->add($replayData, true);
        if ($replay->getGame()->fullyProcessed()) {
            // TODO dispatch ranking calc
        }
    }
}
