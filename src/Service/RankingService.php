<?php

namespace App\Service;

use App\Entity\Game;
use App\Repository\RankingRepository;
use Doctrine\ORM\EntityManagerInterface;

class RankingService
{

    public function __construct(private RankingRepository $rankingRepo,
                                private EntityManagerInterface $em)
    {}

    public function report(Game $game): Game
    {
        $winner = $this->rankingRepo->findOneBy(['owner' => $game->winner()]);
        $loser = $this->rankingRepo->findOneBy(['owner' => $game->loser()]);
        if ($game->draw()) {
            $winner->plusPoints(1);
            $loser->plusPoints(1);
            $this->em->persist($winner);
            $this->em->persist($loser);
        } else {
            $winner->plusPoints(3);
            $this->em->persist($winner);
        }
        $this->em->persist($game);
        $this->em->flush();
        return $game;
    }
}

