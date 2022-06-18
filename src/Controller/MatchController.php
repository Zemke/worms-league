<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SeasonRepository;
use App\Repository\GameRepository;
use App\Entity\ReplayData;

class MatchController extends AbstractController
{
    #[Route('/matches', name: 'app_match')]
    public function index(Request $request): Response
    {
        return $this->render('match/index.html.twig', [
            'seasonId' => $request->query->getInt('season', -1),
        ]);
    }

    public function matches(int $seasonId,
                            GameRepository $gameRepo,
                            SeasonRepository $seasonRepo): Response
    {
        $season = $seasonId === -1 ? $seasonRepo->findActive() : $seasonRepo->find($seasonId);
        return $this->render('_fragments/matches.html.twig', [
            'season' => $season,
            'games' => is_null($season) ? null : $gameRepo->findBySeasonEager($season),
        ]);
    }

    #[Route('/matches/{gameId}', name: 'app_match_view')]
    public function view(Request $request,
                         int $gameId,
                         GameRepository $gameRepo): Response
    {
        $game = $gameRepo->find($gameId);
        if (is_null($game)) {
            $this->addFlash('error', 'There is no such game.');
            if (!is_null($ref = $request->headers->get('referer'))) {
                return $this->redirect($ref);
            } else {
                return $this->redirectToRoute('app_match');
            }
        }
        return $this->render('match/view.html.twig', [
            'round' => $request->query->getInt('round', 1) - 1,
            'game' => $game,
        ]);
    }
}

