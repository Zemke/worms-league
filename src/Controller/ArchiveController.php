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
        dump($var['seasons']);
        return $this->render('archive/index.html.twig', $var);
    }

    #[Route('/archive/{seasonId}', name: 'app_archive_view')]
    public function view(SeasonRepository $seasonRepo, Request $request ,int $seasonId): Response
    {
        $tab = $request->query->get('tab', 'ladder');
        if ($tab !== 'matches') {
            $tab = 'ladder';
        }
        return $this->render('archive/view.html.twig', ['seasonId' => $seasonId, 'tab' => $tab]);
    }
}
