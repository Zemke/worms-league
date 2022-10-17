<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Season;
use App\Repository\GameRepository;
use App\Repository\PlayoffRepository;
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
                         SeasonRepository $seasonRepo,
                         PlayoffRepository $playoffRepo): Response
    {
        $tpl = '_fragments/playoffs.html.twig';
        $season = $seasonId === -1 ? $seasonRepo->findActive() : $seasonRepo->find($seasonId);
        if ($season->current()) {
            $this->addFlash('info', 'The playoffs haven\'t started yet.');
            return $this->render($tpl, []);
        } else {
            $games = $playoffRepo->findForPlayoffs($season);
            if (empty($games)) {
                $this->addFlash('info', 'There are no playoff games.');
                return $this->render($tpl, []);
            } else {
                $tree = array_reduce($games, function ($acc, $g) {
                    $step = $g->getPlayoff()->getStep();
                    if (!in_array($step, array_keys($acc))) {
                        $acc[$step] = [];
                    }
                    $acc[$step][$g->getPlayoff()->getSpot()] = $g;
                    return $acc;
                }, []);
                return $this->render($tpl, [
                    'steps' => intval(log(count($tree[1]), 2) + 1),
                    'games' => $games,
                    'tree' => $tree,
                    'season' => $season,
                ]);
            }
        }
    }
}
