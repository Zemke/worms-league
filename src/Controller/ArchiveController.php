<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/archive/{id}', name: 'app_archive_view')]
    public function view(SeasonRepository $seasonRepo, int $id): Response
    {
        $season = $seasonRepo->find($id);
        return $this->render('archive/index.html.twig', ['season' => $season]);
    }
}
