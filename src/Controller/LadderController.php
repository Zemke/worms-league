<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\RankingRepository;
use App\Repository\SeasonRepository;

class LadderController extends AbstractController
{
    #[Route('/ladder', name: 'app_ladder')]
    public function index(Request $request,
                          RankingRepository $rankingRepo,
                          SeasonRepository $seasonRepo): Response
    {
        $seasonId = $request->query->getInt('season', -1);
        $season = $seasonId === -1 ? $seasonRepo->findActive() : $seasonRepo->find($seasonId);
        $var = [
            'controller_name' => 'LadderController',
            'season' => $season,
        ];
        $var['pp'] = array_map(
            fn($r) => ['p' => round(floatval(strval($r->ranking())))],
            $var['season']->getRankings()->toArray());

        return $this->render('ladder/index.html.twig', $var);
    }
}

