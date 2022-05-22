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
        $drawnGame = $this->createGame($homeUser, $thirdUser);
        $drawnGame->addReplay($this->createReplay($awayUser));
        $drawnGame->setScoreHome(3);
        $drawnGame->setScoreAway(3);
        $this->assertNull($drawnGame->winner());
        $games = [
            $drawnGame,
            $this->createGame($homeUser, $awayUser),
            $this->createGame($awayUser, $homeUser),
            $this->createGame($homeUser, $thirdUser),
        ];
        $r = (new Ranking())
            ->setSeason($this->createSeason())
            ->setOwner($homeUser);

        $r->updateByGames($games);

        $this->assertEquals($r->getRoundsPlayed(), 21);
        $this->assertEquals($r->getRoundsWon(), 11);
        $this->assertEquals($r->getRoundsLost(), 10);
        $this->assertEquals($r->getGamesPlayed(), 4);
        $this->assertEquals($r->getGamesWon(), 2);
        $this->assertEquals($r->getGamesLost(), 1);
        $this->assertEquals($r->getStreak(), 1);
        $this->assertEquals($r->getStreakBest(), 1);
        $this->assertEquals($r->getRoundsWonRatio(), 11 / 21);
        $this->assertEquals($r->getGamesWonRatio(), 2 / 4);

        $this->assertEquals($r->getActivity(), 4 / 7);
        $this->assertEquals($r->getRoundsPlayedRatio(), 1);
        $this->assertEquals($r->getGamesPlayedRatio(), 1);
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
           ->updateByGames($games);
        $awayRanking = (new Ranking())
           ->setSeason($this->createSeason())
           ->setOwner($awayUser)
           ->updateByGames($games);
        $thirdRanking = (new Ranking())
           ->setSeason($this->createSeason())
           ->setOwner($thirdUser)
           ->updateByGames($games);

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
            ->setCreated(new \DateTime('now'))
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
