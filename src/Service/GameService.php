<?php

namespace App\Service;

use App\Entity\Game;
use App\Repository\RankingRepository;
use Doctrine\ORM\EntityManagerInterface;

class GameService
{

    public function __construct(private RankingRepository $rankingRepo,
                                private GameRepository $gameRepo,
                                private SeasonRepository $seasonRepo,
                                private EntityManagerInterface $em)
    {}
}


