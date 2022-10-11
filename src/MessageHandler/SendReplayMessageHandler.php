<?php

namespace App\MessageHandler;

use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use App\Entity\Game;
use App\Entity\Playoff;
use App\Entity\ReplayMap;
use App\Entity\ReplayData;
use App\Message\SendReplayMessage;
use App\Message\RankingCalcMessage;
use App\Repository\GameRepository;
use App\Repository\PlayoffRepository;
use App\Repository\ReplayRepository;
use App\Service\WaaasService;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

final class SendReplayMessageHandler implements MessageHandlerInterface
{
    public function __construct(private LoggerInterface $logger,
                                private WaaasService $waaasService,
                                private GameRepository $gameRepo,
                                private PlayoffRepository $playoffRepo,
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
        if (!$replay->getGame()->fullyProcessed()) {
            return;
        }
        $replay->getGame()->score();
        if ($replay->getGame()->isPlayoff() && is_null($replay->getGame()->winner())) {
            throw new UnrecoverableMessageHandlingException(
                "{$replay->getGame()->asText()} is a playoff game and mustn't be drawn.");
        }
        $this->gameRepo->add($replay->getGame(), true);
        if ($replay->getGame()->isPlayoff()) {
            // playoff
            $finalStep = log(array_reduce(
               $this->playoffRepo->findForPlayoffs($replay->getGame()->getSeason()),
                fn ($acc, $g) => $acc + ($g->getPlayoff()->getStep() === 1),
                0), 2) + 1;
            $winner = $replay->getGame()->winner();
            if ($finalStep - 1 === $replay->getGame()->getPlayoff()->getStep()) {
                // semifinal
                $advUsers = [$loser, $winner];
                for ($i = 0; $i <= 1; $i++) {
                    $advGame = (new Game())
                        ->setSeason($replay->getGame()->getSeason())
                        ->setPlayoff((new Playoff())
                            ->setSpot(1)
                            ->setStep($finalStep + $i));
                    if ($replay->getGame()->getPlayoff()->getSpot() % 2 !== 0) {
                        $advGame->setHome($advUsers[$i]);
                    } else {
                        $advGame->setAway($advUsers[$i]);
                    }
                    $this->gameRepo->add($advGame, $i === 1);
                }
            } else if ($replay->getGame()->getPlayoff()->getStep() < $finalStep) {
                // pre-semifinal
                $advGame = (new Game())
                    ->setSeason($replay->getGame()->getSeason())
                    ->setPlayoff((new Playoff())
                        ->setSpot(ceil($replay->getGame()->getPlayoff()->getStep() / 2))
                        ->setStep($replay->getGame()->getPlayoff()->getStep() + 1));
                if ($replay->getGame()->getPlayoff()->getSpot() % 2 !== 0) {
                    $advGame->setHome($winner);
                } else {
                    $advGame->setAway($winner);
                }
                $this->gameRepo->add($advGame, true);
            }
            $this->chat($replay->getGame());
        } else if (!$replay->getGame()->getRanked()) {
            // ladder
            $this->bus->dispatch(new RankingCalcMessage($replay->getGame()->getId()));
            $this->chat($replay->getGame());
        }
    }

    private function chat(Game $game): void
    {
        try {
            $this->chatter->send(new ChatMessage(
                $game->asText() . ' https://wl.zemke.io/matches/' . $game->getId()));
        } catch (\Throwable $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
        }
    }
}

