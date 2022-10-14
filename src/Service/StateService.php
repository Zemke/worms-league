<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
use App\Repository\PlayoffRepository;
use App\Repository\SeasonRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/** Current state of Worms League */
enum State
{

    /** no season with active set to true */
    case NONE;

    /** active season within its datetime range */
    case LADDER;

    /** active season outside its datetime range but with unplayed playoff games */
    case PLAYOFFS;

    /** active season outside its datetime range without any playoff games */
    case LADDER_ENDING;

    /** active season outside its datetime range with playoff games that are all played */
    case PLAYOFFS_ENDING;
}


class StateService
{

    public function __construct(private SeasonRepository $seasonRepo,
                                private PlayoffRepository $playoffRepo,
                                private UserRepository $userRepo,)
    {}

    public function state(): State
    {
        $s = $this->seasonRepo->findActive();
        if (is_null($s)) {
            return State::NONE;
        }
        if ($s->current()) {
            return State::LADDER;
        }
        $po = $this->playoffRepo->findForPlayoffs($s);
        $poC = count($po);
        $poPl = count(array_filter($po, fn($g) => $g->played()));
        $poUn = $poC - $poPl;
        if ($poC === 0) {
            return State::LADDER_ENDING;
        }
        if ($poUn === 0) {
            return State::PLAYOFFS_ENDING;
        }
        return State::PLAYOFFS;
    }

    /**
     * @return Game[]
     */
    public function openGames(User $user): array
    {
        $s = $this->seasonRepo->findActive();
        if ($s->current()) {
            return array_map(fn($u) => (new Game())->setHome($user)->setAway($u), $this->userRepo->findOther($user));
        }
        foreach ($this->playoffRepo->findForPlayoffs($s) as &$g) {
            if ($g->isHomeOrAway($user) && !$g->played() && $g->isPaired()) {
                return [dump($g)];
            }
        }
        return [];
    }

    public function ladderWinners(): array
    {
        return [ // TODO ladderWinners
            (new User())->setUsername('Mega`Adnan'),
            (new User())->setUsername('Kayz'),
            (new User())->setUsername('WorldMaster'),
        ];
    }

    public function playoffsWinners(): array
    {
        return [ // TODO playoffsWinners
            (new User())->setUsername('Rafka'),
            (new User())->setUsername('Mablak'),
            (new User())->setUsername('GopnicK'),
        ];
    }
}


