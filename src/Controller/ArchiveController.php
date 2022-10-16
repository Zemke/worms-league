<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SeasonRepository;
use App\Repository\PlayoffRepository;

class ArchiveController extends AbstractController
{
    #[Route('/archive', name: 'app_archive')]
    public function index(SeasonRepository $seasonRepo): Response
    {
        $var['seasons'] = $seasonRepo->findForArchive();
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
