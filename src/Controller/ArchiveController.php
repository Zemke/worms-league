<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SeasonRepository;
use App\Repository\PlayoffRepository;
use App\Service\StateService;

class ArchiveController extends AbstractController
{
    #[Route('/archive', name: 'app_archive')]
    public function index(SeasonRepository $seasonRepo,
                          StateService $stateService,): Response
    {
        $var['seasons'] = $seasonRepo->findForArchive();
        $var['playoffsWinners'] = array_combine(
            array_map(fn($s) => $s->getId(), $var['seasons']),
            array_map(fn($s) => $stateService->playoffsWinners($s), $var['seasons']));
        return $this->render('archive/index.html.twig', $var);
    }

    #[Route('/archive/{seasonId}', name: 'app_archive_view')]
    public function view(PlayoffRepository $playoffRepo,
                         SeasonRepository $seasonRepo,
                         Request $request,
                         int $seasonId): Response
    {
        $season = $seasonRepo->find($seasonId);
        $tabs = ['ladder', 'matches'];
        if (!empty($playoffRepo->findForPlayoffs($season))) {
            $tabs[] = 'playoffs';
        }
        $tab = $request->query->get('tab') ?? $tabs[0];
        return $this->render('archive/view.html.twig', [
            'season' => $season,
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}
