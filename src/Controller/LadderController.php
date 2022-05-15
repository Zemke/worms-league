<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\RankingRepository;
use App\Repository\SeasonRepository;

class LadderController extends AbstractController
{
    #[Route('/ladder', name: 'app_ladder')]
    public function index(RankingRepository $rankingRepo,
                          SeasonRepository $seasonRepo): Response
    {
        $var = [
            'controller_name' => 'LadderController',
            'season' => $seasonRepo->findActive(),
        ];
        $var['pp'] = array_map(
            fn($r) => ['p' => round(floatval(strval($r->ranking())))],
            $var['season']->getRankings()->toArray());

        return $this->render('ladder/index.html.twig', $var);
    }
}

