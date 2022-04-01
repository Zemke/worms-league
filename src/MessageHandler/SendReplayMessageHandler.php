<?php

namespace App\MessageHandler;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;
use App\Message\SendReplayMessage;
use App\Message\RankingCalcMessage;
use App\Repository\ReplayRepository;
use App\Repository\ReplayDataRepository;
use App\Repository\GameRepository;
use App\Service\WaaasService;

final class SendReplayMessageHandler implements MessageHandlerInterface
{
    public function __construct(private LoggerInterface $logger,
                                private WaaasService $waaasService,
                                private ReplayRepository $replayRepo,
                                private GameRepository $gameRepo,
                                private MessageBusInterface $bus,)
    {}

    public function __invoke(SendReplayMessage $message)
    {
        $replay = $this->replayRepo->find($message->getReplayId());
        if (!empty($replay->getReplayData())) {
            throw new \RuntimeException(
                "Replay data for replay {$replay->getId()} is already available");
        }
        $replayData = $this->waaasService->send($replay);
        $map = $this->waaasService->map($replayData['map']);
        $replay->setReplayData($replayData);
        $this->replayRepo->add($replay, true);
        if ($replay->getGame()->fullyProcessed()) {
            $this->gameRepo->add($replay->getGame()->score(), true);
            $this->bus->dispatch(new RankingCalcMessage($replay->getGame()->getId()));
        }
    }
}

