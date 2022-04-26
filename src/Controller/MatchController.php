<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SeasonRepository;
use App\Repository\GameRepository;
use App\Entity\ReplayData;

class MatchController extends AbstractController
{
    #[Route('/matches', name: 'app_match')]
    public function index(GameRepository $gameRepo,
                          SeasonRepository $seasonRepo): Response
    {
        $season = $seasonRepo->findActive();
        return $this->render('match/index.html.twig', [
            'games' => $gameRepo->findBySeason($season)
        ]);
    }

    #[Route('/matches/{gameId}', name: 'app_match_view')]
    public function view(Request $request,
                         int $gameId,
                         GameRepository $gameRepo): Response
    {
        return $this->render('match/view.html.twig', [
            'round' => $request->query->getInt('round', 1) - 1,
            'game' => $gameRepo->find($gameId),
        ]);
    }

    public function stats(int $gameId,
                          int $round,
                          GameRepository $gameRepo): Response
    {
        $game = $gameRepo->find($gameId);
        $stats = $game->getReplays()[$round]->getReplayData()->getData();
        return $this->render('match/stats.html.twig', [
            'averageTurnTimes' => [15, 20],
            'stats' => $stats,
            'game' => $game,
            'round' => $round - 1,
            'gradients' => $this->gradients($stats),
            'kills' => $this->kills($stats),
        ]);
    }

    private function gradients(array $stats): array
    {
        $losingUser = current(array_filter(
            $stats['teams'],
            fn($t) => $t['team'] !== $stats['winsTheRound']))['user'];
        $totalHealthPointsPerTeam =
            $this->calcLostHealthPoints($stats['turns'], $losingUser | $stats['teams'][0]['user']);

        $result = [];
        $cStats = count($stats);
        for ($turnNum = 0; $turnNum <= $cStats; $turnNum++) {
            $pastTurns = array_slice($stats['turns'], 0, $turnNum);
            $healthPoints =
                array_map(fn($team) => [
                    'team' => $team,
                    'health' => $totalHealthPointsPerTeam -
                        $this->calcLostHealthPoints($pastTurns, $team['user']),
                ], $stats['teams']);

            $result[] =  array_map(function ($health, $idx) use ($totalHealthPointsPerTeam) {
                $remainingHealth =
                    round($health['health'] / $totalHealthPointsPerTeam * 10000) / 100;
                $lostHealth = 100 - $remainingHealth;
                $teamColor = ReplayData::COLORS[strtolower($health['team']['color'])];
                $result = '';
                if ($idx === 0) {
                    $result .= "#1B2021 0, ";
                    $result .= '#1B2021 ' . ($lostHealth === 100 ? '50' : min($lostHealth / 2, 48)) . '%, ';
                    $result .= "{$teamColor} " . ($lostHealth === 100 ? '50' : min($lostHealth / 2, 48)) . '%, ';
                    $result .= "{$teamColor} 50%";
                } else if ($idx === 1) {
                    $result .= "{$teamColor} 50%, ";
                    $result .= "{$teamColor} " . ($lostHealth === 100 ? '0' : max(($remainingHealth / 2) + 50, 52)) . '%, ';
                    $result .= '#1B2021 ' . ($lostHealth === 100 ? '0' : max(($remainingHealth / 2) + 50, 52)) .'%, ';
                    $result .= '#1B2021 100%';
                }
                return $result;
            }, $healthPoints, array_keys($healthPoints));
        }
        return $result;
    }

    private function calcLostHealthPoints(array $turns, string $victim) {
        return array_reduce(
            $turns,
            fn($acc, $t) =>
                $acc + array_reduce(
                    array_filter($t['damages'], fn($d) => $d['victim'] === $victim),
                    fn($accD, $currD) => $accD + $currD['damage'], 0)
        , 0);
    }

    private function kills(array $stats): array
    {
        $result = [];
        $cStats = count($stats);
        for ($turnNum = 0; $turnNum < $cStats; $turnNum++) {
            $result[] = array_reduce($stats['turns'][$turnNum]['damages'], function ($acc, $v) {
                $acc[$v['victim']] += $v['kills'];
                return $acc;
            }, [$stats['teams'][0]['user'] => 0, $stats['teams'][1]['user'] => 0]);
        }
        return $result;
    }
}

