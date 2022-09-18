<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\ConfigName;
use App\Entity\Config;
use App\Repository\ConfigRepository;
use App\Repository\SeasonRepository;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(SeasonRepository $seasonRepo,
                          ConfigRepository $configRepo,): Response
    {
        $var['season'] = $seasonRepo->findActive();
        $var['text'] = $configRepo->find(ConfigName::TEXT->toId())?->getValue();
        return $this->render('home/index.html.twig', $var);
    }
}
