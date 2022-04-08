<?php

namespace App\Tests\Entity;

use PHPUnit\Framework\TestCase;
use App\Entity\Game;
use App\Entity\Ranking;
use App\Entity\Replay;
use App\Entity\Season;
use App\Entity\User;

class RankingTest extends TestCase
{
    public function testUpdateByGame(): void
    {
        $homeUser = $this->createUser(1);
        $awayUser = $this->createUser(2);
        $thirdUser = $this->createUser(3);
        $games = [
            $this->createGame($homeUser, $awayUser),
            $this->createGame($awayUser, $homeUser),
            $this->createGame($homeUser, $thirdUser),
        ];
        $r = (new Ranking())
            ->setSeason($this->createSeason())
            ->setOwner($homeUser);
        foreach ($games as $game) {
            $r->updateByGame($game);
        }
        $this->assertEquals($r->getPoints(), 6);
        $this->assertEquals($r->getRoundsPlayed(), 15);
        $this->assertEquals($r->getRoundsWon(), 8);
        $this->assertEquals($r->getRoundsLost(), 7);
        $this->assertEquals($r->getGamesPlayed(), 3);
        $this->assertEquals($r->getGamesWon(), 2);
        $this->assertEquals($r->getGamesLost(), 1);
        $this->assertEquals($r->getStreak(), 1);
        $this->assertEquals($r->getRecent(), 'WLW');
        $this->assertEquals($r->getStreakBest(), 1);
        $this->assertEquals($r->getRoundsWonRatio(), 8 / 15);
        $this->assertEquals($r->getGamesWonRatio(), 2 / 3);
    }

    public function testUpdateByAllGames(): void
    {
        $homeUser = $this->createUser(1);
        $awayUser = $this->createUser(2);
        $thirdUser = $this->createUser(3);
        $games = [
            $this->createGame($homeUser, $awayUser),
            $this->createGame($awayUser, $homeUser),
            $this->createGame($homeUser, $thirdUser),
        ];
        $homeRanking = (new Ranking())
           ->setSeason($this->createSeason())
           ->setOwner($homeUser)
           ->updateByAllGames($games);
        $awayRanking = (new Ranking())
           ->setSeason($this->createSeason())
           ->setOwner($awayUser)
           ->updateByAllGames($games);
        $thirdRanking = (new Ranking())
           ->setSeason($this->createSeason())
           ->setOwner($thirdUser)
           ->updateByAllGames($games);

        $totalRounds =
            count($games[0]->getReplays())
            + count($games[1]->getReplays())
            + count($games[2]->getReplays());
        $this->assertEquals($totalRounds, 15);

        $this->assertEquals($homeRanking->getRoundsPlayedRatio(), 1);
        $this->assertEquals($homeRanking->getGamesPlayedRatio(), 1);
        $this->assertEquals($homeRanking->getActivity(), 3 / 7);

        $this->assertEquals($awayRanking->getRoundsPlayedRatio(), 10 / $totalRounds);
        $this->assertEquals($awayRanking->getGamesPlayedRatio(), 2 / 3);
        $this->assertEquals($awayRanking->getActivity(), 2 / 7);

        $this->assertEquals($thirdRanking->getRoundsPlayedRatio(), 5 / $totalRounds);
        $this->assertEquals($thirdRanking->getGamesPlayedRatio(), 1 / 3);
        $this->assertEquals($thirdRanking->getActivity(), 1 / 7);
    }

    private function createGame(User $homeUser, User $awayUser): Game
    {
        return (new Game())
            ->setHome($homeUser)
            ->setAway($awayUser)
            ->setScoreHome(3)
            ->setScoreAway(2)
            ->setSeason($this->createSeason())
            ->setReporter($homeUser)
            ->addReplay($this->createReplay($homeUser))
            ->addReplay($this->createReplay($homeUser))
            ->addReplay($this->createReplay($awayUser))
            ->addReplay($this->createReplay($awayUser))
            ->addReplay($this->createReplay($homeUser));
    }

    private function createReplay(User $winner): Replay
    {
        $m = $this->getMockBuilder(Replay::class)->getMock();
        $m->method('processed')->willReturn(true);
        $m->method('winner')->willReturn($winner);
        return $m;
    }

    private function createSeason(int $id = 1): Season
    {
        $m = $this->getMockBuilder(Season::class)->getMock();
        $m->method('getId')->willReturn($id);
        return $m;
    }

    private function createUser(int $id): User
    {
        $m = $this->getMockBuilder(User::class)->getMock();
        $m->method('getId')->willReturn($id);
        return $m;
    }
}
