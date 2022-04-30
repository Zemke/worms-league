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

        $games = array_reduce($gameRepo->findOfUser($user), function ($acc, $g) use ($user) {
            if (!$g->fullyProcessed()) {
                return $acc;
            }
            $opp = $g->opponent($user);
            $idx = array_search($opp, array_column($acc, 'opp'));
            if ($idx === false) {
                $acc[] = ['opp' => $opp, 'won' => $g->scoreOf($user), 'lost' => $g->scoreOf($opp)];
            } else {
                $acc[$idx]['won'] += $g->scoreOf($user);
                $acc[$idx]['lost'] += $g->scoreOf($opp);
            }
            return $acc;
        }, []);
        return $this->render('user/view.html.twig', [
            'user' => $user,
            'games' => $games,
        ]);
    }
}
