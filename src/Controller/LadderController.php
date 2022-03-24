<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LadderController extends AbstractController
{
    #[Route('/ladder', name: 'app_ladder')]
    public function index(): Response
    {
        return $this->render('ladder/index.html.twig', [
            'controller_name' => 'LadderController',
        ]);
    }
}
