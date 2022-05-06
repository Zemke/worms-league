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
        $user = ctype_digit($usernameOrId)
            ? $userRepo->find($usernameOrId)
            : $userRepo->findOneByUsername($usernameOrId);
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
        $totalWon = array_reduce($games, fn($acc, $g) => $acc + $g['won'], 0);
        $total = array_reduce($games, fn($acc, $g) => $acc + $g['won'] + $g['lost'], 0);
        foreach ($games as &$g) {
            $g['diff'] = $g['won'] - $g['lost'];
            $g['wonRatio'] = round((($g['won'] / ($g['won'] + $g['lost'])) * 100));
            $g['total'] = $g['won'] + $g['lost'];
            $g['totalRatio'] = round((($g['total']) / $total) * 100);
            $g['totalWonRatio'] = round(($g['won'] / $totalWon) * 100);
        }
        $games[] = [
            'opp' => null,
            'won' => $totalWon,
            'lost' => $total - $totalWon,
            'diff' => $totalWon-($total-$totalWon),
            'wonRatio' => round(($totalWon/$total) * 100),
            'total' => $total,
            'totalRatio' => 100,
            'totalWonRatio' => 100,
        ];
        return $this->render('user/view.html.twig', [
            'user' => $user,
            'games' => $games,
            'total' => $total,
            'totalWon' => $totalWon,
            'totalDiff' => $totalWon-($total-$totalWon),
            'totalWonRatio' => round(($totalWon/$total) * 100),
        ]);
    }
}
