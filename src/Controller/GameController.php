<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\Game;

class GameController extends AbstractController
{
    #[Route('/game', name: 'app_game')]
    public function index(): Response
    {
        return $this->render('game/index.html.twig', [
            'controller_name' => 'GameController',
        ]);
    }

    #[Route('/report', name: 'app_report', methods: ['GET', 'POST'])]
    public function report(Request $request,
                           UserRepository $users,
                           EntityManagerInterface $em,
                           Security $security,
                           ValidatorInterface $validator): Response
    {
        if ($request->getMethod() === 'POST') {
            dump($request);
            dump($users->find($request->request->all()['opponent']));
            $game = (new Game())
                ->setReporter($security->getUser())
                ->setHome($security->getUser())
                ->setAway($users->find($request->request->all()['opponent']));
            $validator->validate($game);
        } else {
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
}
