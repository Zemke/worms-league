<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SeasonRepository;
use App\Repository\GameRepository;

class MatchController extends AbstractController
{
    #[Route('/matches', name: 'app_match')]
    public function index(GameRepository $gameRepo,
                          SeasonRepository $seasonRepo): Response
    {
        $season = $seasonRepo->findActive();
        $var = [ 'controller_name' => 'MatchController', ];
        $var['games'] = $gameRepo->findBySeason($season);
        dump($var);
        return $this->render('match/index.html.twig', $var);
    }

    #[Route('/matches/{gameId}', name: 'app_match_view')]
    public function view(int $gameId,
                         GameRepository $gameRepo): Response
    {
        $var['game'] = $gameRepo->find($gameId);
        return $this->render('match/view.html.twig', $var);
    }

}

