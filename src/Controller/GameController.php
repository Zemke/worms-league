<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use App\Repository\UserRepository;

class GameController extends AbstractController
{
    #[Route('/game', name: 'app_game')]
    public function index(): Response
    {
        return $this->render('game/index.html.twig', [
            'controller_name' => 'GameController',
        ]);
    }

    #[Route('/report', name: 'app_report')]
    public function report(UserRepository $users,
                           EntityManagerInterface $em,
                           Security $security): Response
    {
        $opponents = $em->createQueryBuilder()
            ->select('u')
            ->from('App:User', 'u')
            ->where('u.id <> :authUserId')
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->setParameter('authUserId', $security->getUser()->getId())
            ->getResult();
        dump($opponents);
        return $this->render('game/report.html.twig', [
            'controller_name' => 'GameController',
            'opponents' => $opponents,
        ]);
    }
}
