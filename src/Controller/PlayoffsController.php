<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Season;
use App\Repository\GameRepository;
use App\Repository\SeasonRepository;

class PlayoffsController extends AbstractController
{
    #[Route('/playoffs', name: 'app_playoffs')]
    public function index(Request $request): Response
    {
        return $this->render('playoffs/index.html.twig', [
            'id' => $request->query->getInt('season', -1),
        ]);
    }

    public function view(int $seasonId,
                         GameRepository $gameRepo,
                         SeasonRepository $seasonRepo): Response
    {
        $season = $seasonId === -1 ? $seasonRepo->findActive() : $seasonRepo->find($seasonId);
        $games = $gameRepo->findBySeason($season); // TODO get playoff games of that season
        return $this->render('_fragments/playoffs.html.twig', [
            'games' => $games
        ]);
    }
}
