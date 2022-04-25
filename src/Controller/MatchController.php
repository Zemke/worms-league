<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
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
        return $this->render('match/index.html.twig', [
            'games' => $gameRepo->findBySeason($season)
        ]);
    }

    #[Route('/matches/{gameId}', name: 'app_match_view')]
    public function view(Request $request,
                         int $gameId,
                         GameRepository $gameRepo): Response
    {
        return $this->render('match/view.html.twig', [
            'round' => $request->query->getInt('round', 1) - 1,
            'game' => $gameRepo->find($gameId),
            'averageTurnTimes' => [15, 20],
        ]);
    }
}

