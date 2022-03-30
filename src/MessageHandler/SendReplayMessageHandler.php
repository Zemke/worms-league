<?php

namespace App\MessageHandler;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Psr\Log\LoggerInterface;
use App\Message\SendReplayMessage;
use App\Repository\ReplayRepository;
use App\Repository\ReplayDataRepository;
use App\Service\WaaasService;

final class SendReplayMessageHandler implements MessageHandlerInterface
{
    public function __construct(private LoggerInterface $logger,
                                private WaaasService $waaasService,
                                private ReplayRepository $replayRepo,
                                private ReplayDataRepository $replayDataRepo,
                                private MessageBusInterface $bus)
    {}

    public function __invoke(SendReplayMessage $message)
    {
        $replay = $this->replayRepo->find($message->getReplayId());
        $replayData = $this->waaasService->send($replay);
        $this->logger->info('before', ['fp' => $replay->getGame()->fullyProcessed()]);
        $replay->setReplayData($replayData);
        $this->replayDataRepo->add($replayData, true);
        $this->logger->info('after', ['fp' => $replay->getGame()->fullyProcessed()]);
        if ($replay->getGame()->fullyProcessed()) {
            $bus->dispatch(new RankingCalcMessage($replay->getGame()->getId()));
        }
    }
}
