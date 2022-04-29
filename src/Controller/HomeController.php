<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SeasonRepository;

class HomeController extends AbstractController
{
    #[Route('/')]
    public function index(SeasonRepository $seasonRepo): Response
    {
        $var['season'] = $seasonRepo->findActive();
        return $this->render('home/index.html.twig', $var);
    }
}
