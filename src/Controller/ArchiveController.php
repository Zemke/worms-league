<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SeasonRepository;

class ArchiveController extends AbstractController
{
    #[Route('/archive', name: 'app_archive')]
    public function index(SeasonRepository $seasonRepo): Response
    {
        $var['seasons'] = $seasonRepo->findForArchive();
        return $this->render('archive/index.html.twig', $var);
    }

    #[Route('/archive/{seasonId}', name: 'app_archive_view')]
    public function view(SeasonRepository $seasonRepo, Request $request, int $seasonId): Response
    {
        $season = $seasonRepo->find($seasonId);
        // TODO only show when there are actually playoffs for that season
        $tabs = ['ladder', 'matches', 'playoffs'];
        $tab = $request->query->get('tab') ?? $tabs[0];
        return $this->render('archive/view.html.twig', [
            'season' => $season,
            'tab' => $tab,
            'tabs' => $tabs,
        ]);
    }
}
