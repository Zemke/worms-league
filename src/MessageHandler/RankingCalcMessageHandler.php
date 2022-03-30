<?php

namespace App\MessageHandler;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use App\Message\RankingCalcMessage;
use App\Entity\Game;
use App\Repository\GameRepository;
use App\Service\RankingService;

final class RankingCalcMessageHandler implements MessageHandlerInterface
{
    public function __construct(private GameRepository $gameRepo,
                                private RankingService $rankingService,)
    {}

    public function __invoke(RankingCalcMessage $message)
    {
        $game = $this->gameRepo->find($message->getGameId());
        $this->rankingService->calc($game);
    }
}

