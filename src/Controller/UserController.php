<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use App\Repository\GameRepository;

class UserController extends AbstractController
{
    #[Route('/user/{usernameOrId}', name: 'app_user_view')]
    public function index(string $usernameOrId,
                          UserRepository $userRepo,
                          GameRepository $gameRepo,): Response
    {
        if (ctype_digit($usernameOrId)) {
            $user = $userRepo->find($usernameOrId);
        } else {
            $user = $userRepo->findOneByUsername($usernameOrId);
        }
        $games = $gameRepo->findOfUser($user);
        return $this->render('user/view.html.twig', [
            'user' => $user,
            'games' => $games,
        ]);
    }
}
