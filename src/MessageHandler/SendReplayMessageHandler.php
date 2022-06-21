<?php

namespace App\MessageHandler;

use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use App\Entity\ReplayMap;
use App\Entity\ReplayData;
use App\Message\SendReplayMessage;
use App\Message\RankingCalcMessage;
use App\Repository\GameRepository;
use App\Repository\ReplayRepository;
use App\Service\WaaasService;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

final class SendReplayMessageHandler implements MessageHandlerInterface
{
    public function __construct(private LoggerInterface $logger,
                                private WaaasService $waaasService,
                                private GameRepository $gameRepo,
                                private ReplayRepository $replayRepo,
                                private ValidatorInterface $validator,
                                private MessageBusInterface $bus,
                                private ChatterInterface $chatter,)
    {}

    public function __invoke(SendReplayMessage $message)
    {
        $replay = $this->replayRepo->find($message->getReplayId());
        if (!$replay->processed()) {
            $replayData = $this->waaasService->send($replay);
            if (count($err = $this->validator->validate($replayData)) > 0) {
                throw new UnrecoverableMessageHandlingException(strval($err));
            }
            $replay->setReplayData($replayData);
            $this->replayRepo->add($replay, true);
        } else {
            $this->logger->info("Game {$replay->getGame()->getId()} is already processed");
            $replayData = $replay->getReplayData();
        }
        if (is_null($replay->getReplayMap())) {
            $mapUrl = $replayData->getData()['map'];
            if (is_null($mapUrl)) {
                $this->logger->warn("ReplayData {$replayData->getId()} has no map apparently");
            } else {
                    $tmpfile = $this->waaasService->map($mapUrl);
                    try {
                        $uri = stream_get_meta_data($tmpfile)['uri'];
                        $file = new UploadedFile($uri, basename($uri), null, null, true);
                        $replayMap = new ReplayMap($replay->getGame()->getId(), $replay->getName());
                        $replay->setReplayMap($replayMap->setFile($file));
                        $this->replayRepo->add($replay, true);
                    } catch (\Throwable $e) {
                        $this->logger->critical('Couldn\'t get map', ['e' => $e]);
                    } finally {
                        fclose($tmpfile);
                    }
            }
        } else {
            $this->logger->info("Replay {$replay->getId()} already has a map.");
        }
        if ($replay->getGame()->fullyProcessed()) {
            $this->gameRepo->add($replay->getGame()->score(), true);
            if (!$replay->getGame()->getRanked()) {
                $this->bus->dispatch(new RankingCalcMessage($replay->getGame()->getId()));
            }
            try {
                $msg = "{$replay->getGame()->getHome()->getUsername()}  {$replay->getGame()->getScoreHome()}"
                    . '–'
                    . "{$replay->getGame()->getScoreAway()} {$replay->getGame()->getAway()->getUsername()}"
                    . ' https://wl.zemke.io/matches/' . $replay->getGame()->getId();
                $this->chatter->send(new ChatMessage($msg));
            } catch (\Throwable $e) {
                $this->logger->critical($e->getMessage(), ['exception' => $e]);
            }
        }
    }
}

