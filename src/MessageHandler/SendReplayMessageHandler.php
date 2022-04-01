<?php

namespace App\MessageHandler;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Log\LoggerInterface;
use App\Entity\ReplayMap;
use App\Message\SendReplayMessage;
use App\Message\RankingCalcMessage;
use App\Repository\GameRepository;
use App\Service\WaaasService;

final class SendReplayMessageHandler implements MessageHandlerInterface
{
    public function __construct(private LoggerInterface $logger,
                                private WaaasService $waaasService,
                                private GameRepository $gameRepo,
                                private MessageBusInterface $bus,)
    {}

    public function __invoke(SendReplayMessage $message)
    {
        $replay = $this->replayRepo->find($message->getReplayId());
        if (!$replay->processed()) {
            $replayData = $this->waaasService->send($replay);
            $replay->setReplayData($replayData);
            $this->replayRepo->add($replay, true);
        } else {
            $this->logger->info("Game {$replay->getGame()->getId()} is already processed");
            $replayData = $replay->getReplayData();
        }
        $mapUrl = $replayData->getData()['map'];
        if (is_null($mapUrl)) {
            $this->logger->warn("ReplayData {$replayData->getId()} has no map apparently");
        } else {
            $tmpfile = $this->waaasService->map($mapUrl);
            $uri = stream_get_meta_data($tmpfile)['uri'];
            $file = new UploadedFile($uri, basename($uri), null, null, true);
            $replayMap = new ReplayMap($replay->getGame()->getId(), $replay->getName());
            $replay->setReplayMap($replayMap->setFile($file));
            $this->replayRepo->add($replay, true);
        }
        if ($replay->getGame()->fullyProcessed()) {
            $this->gameRepo->add($replay->getGame()->score(), true);
            if (!$replay->getGame()->getRanked()) {
                $this->bus->dispatch(new RankingCalcMessage($replay->getGame()->getId()));
            }
        }
    }
}

