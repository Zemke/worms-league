<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArchiveController extends AbstractController
{
    #[Route('/archive', name: 'app_archive')]
    public function index(): Response
    {
        return $this->render('archive/index.html.twig', [
            'controller_name' => 'ArchiveController',
        ]);
    }
}
