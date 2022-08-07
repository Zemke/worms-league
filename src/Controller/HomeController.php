<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SeasonRepository;
use App\Repository\UserRepository;

class HomeController extends AbstractController
{
    #[Route('/')]
    public function index(SeasonRepository $seasonRepo,
                          UserRepository $userRepo): Response
    {
        $var['season'] = $seasonRepo->findActive();
        $var['users'] = $userRepo->findAll();
        return $this->render('home/index.html.twig', $var);
    }
}
