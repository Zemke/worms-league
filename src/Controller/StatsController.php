<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\GameRepository;
use App\Entity\ReplayData;

class StatsController extends AbstractController
{
    public function stats(int $gameId,
                          int $round,
                          GameRepository $gameRepo): Response
    {
        $game = $gameRepo->find($gameId);
        $stats = $game->getReplays()[$round]->getReplayData()->getData();
        return $this->render('stats/stats.html.twig', [
            'averageTurnTimes' => $this->averageTurnTimes($stats),
            'stats' => $stats,
            'game' => $game,
            'round' => $round - 1,
            'gradients' => $this->gradients($stats),
            'kills' => $this->kills($stats),
            'suddenDeathBeforeTurn' => $this->suddenDeathBeforeTurn($stats),
        ]);
    }

    private function averageTurnTimes(array $stats): array
    {
        $tt = array_reduce($stats['turns'], function ($acc, $turn) {
            $acc[$turn['user']][0] += $turn['timeUsedSeconds'];
            $acc[$turn['user']][1] += 1;
            return $acc;
        }, [$stats['teams'][0]['user'] => [0, 0], $stats['teams'][1]['user'] => [0, 0]]);
        $tt = array_values($tt);
        return [
            round($tt[0][0] / $tt[0][1]),
            round($tt[1][0] / $tt[1][1]),
        ];
    }


    private function suddenDeathBeforeTurn(array $stats): int
    {
        $timestampToSeconds = function (string $timestamp): float {
            $timeParts = array_map(fn($v) => (int) $v, preg_split('/[^\d]/', $timestamp));
            return (
                ($timeParts[0] * 60 * 60)
                + ($timeParts[1] * 60)
                + ($timeParts[2])
                + $timeParts[2] / 100
            );
        };

        $turnSeconds = array_map(fn($turn) => $timestampToSeconds($turn['timestamp']), $stats['turns']);
        if (is_null($stats['suddenDeath'])) {
            return -1;
        }
        $suddenDeathSeconds = $timestampToSeconds($stats['suddenDeath']);
        for ($i = 0; $i < count($turnSeconds); $i++) {
            $turnSecond = $turnSeconds[$i];
            if ($turnSecond > $suddenDeathSeconds) {
                return $i + 1;
            }
        }
        return -1;
    }

    private function gradients(array $stats): array
    {
        $losingUser = current(array_filter(
            $stats['teams'],
            fn($t) => $t['team'] !== $stats['winsTheRound']))['user'];
        $totalHealthPointsPerTeam = $this->calcLostHealthPoints(
            $stats['turns'], $losingUser ? $losingUser : $stats['teams'][0]['user']);

        $result = [];
        $cTurns = count($stats['turns']);
        for ($turnNum = 0; $turnNum <= $cTurns; $turnNum++) {
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
                    $result .= "var(--dark) 0, ";
                    $result .= 'var(--dark) ' . ($lostHealth === 100 ? '50' : min($lostHealth / 2, 48)) . '%, ';
                    $result .= "{$teamColor} " . ($lostHealth === 100 ? '50' : min($lostHealth / 2, 48)) . '%, ';
                    $result .= "{$teamColor} 50%";
                } else if ($idx === 1) {
                    $result .= "{$teamColor} 50%, ";
                    $result .= "{$teamColor} " . ($lostHealth === 100 ? '0' : max(($remainingHealth / 2) + 50, 52)) . '%, ';
                    $result .= 'var(--dark) ' . ($lostHealth === 100 ? '0' : max(($remainingHealth / 2) + 50, 52)) .'%, ';
                    $result .= 'var(--dark) 100%';
                }
                return $result;
            }, $healthPoints, array_keys($healthPoints));
        }
        return $result;
    }

    private function calcLostHealthPoints(array $turns, string $victim): int
    {
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
        $cTurns = count($stats['turns']);
        for ($turnNum = 0; $turnNum < $cTurns; $turnNum++) {
            $result[] = array_reduce($stats['turns'][$turnNum]['damages'], function ($acc, $v) {
                $acc[$v['victim']] += $v['kills'];
                return $acc;
            }, [$stats['teams'][0]['user'] => 0, $stats['teams'][1]['user'] => 0]);
        }
        return $result;
    }
}
