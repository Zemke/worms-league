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

    /** active season outside its datetime range without unplayed playoff games */
    case ENDING;
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
        foreach ($this->playoffRepo->findForPlayoffs($s) as $g) {
            if (!$g->played()) {
                return State::PLAYOFFS;
            }
        }
        return State::ENDING;
    }

    /**
     * @return Game[]
     */
    public function opponents(User $user): array
    {
        $s = $this->seasonRepo->findActive();
        if ($s->current()) {
            return $this->userRepo->findOther($user);
        }
        foreach ($this->playoffRepo->findForPlayoffs($s) as &$g) {
            if ($g->isHomeOrAway($user) && !$g->played()) {
                return [$g->opponent($user)];
            }
        }
        return [];
    }
}


