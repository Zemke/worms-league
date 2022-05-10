<?php

namespace App\DataFixtures;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Game;
use App\Entity\User;
use App\Entity\Season;

class NNNFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $season = (new Season())
            ->setActive(true)
            ->setStart(new \DateTime('2022-01-22 00:00:00'))
            ->setEnding(new \DateTime('2022-04-30 00:00:00'))
            ->setName('NNN40');
        $manager->persist($season);
        $gamescsv = fopen(dirname(__FILE__) . '/csv/games.csv', 'r');
        $head = fgetcsv($gamescsv);
        $users = [];
        $c = 0;
        while (($row = fgetcsv($gamescsv)) !== false) {
            $vv = array_combine($head, $row);
            $game = (new Game())
                ->setSeason($season)
                ->setCreated(new \DateTime($vv['dateat']));
            $gUsers = array_map(function ($u) use (&$users, $manager) {
                $usernames = array_map(fn($u1) => $u1->getUsername(), $users);
                if (($idx = array_search($u, $usernames)) === false) {
                    $nu = (new User())->setUsername($u)->setEmail("{$u}@zemke.io");
                    $nu->setPassword($this->hasher->hashPassword($nu, 'adminadminadmin'));
                    $manager->persist($nu);
                    $nx = $nu;
                } else {
                    $nx = $users[$idx];
                }
                $users[] = $nx;
                return $nx;
            }, [$vv['user_confirmer'], $vv['user_confirmed']]);
            $game->setHome($gUsers[0]);
            $game->setAway($gUsers[1]);
            $game->setReporter($game->getHome());
            $game->setScoreHome((int) $vv['score_confirmer']);
            $game->setScoreAway((int) $vv['score_confirmed']);
            $manager->persist($game);
            $c++;
            dump($c . ' ' . $game->getHome()->getUsername() . ' '
                 . $game->getScoreHome() . '–' . $game->getScoreAway() . ' '
                 . $game->getAway()->getUsername() . "\n");
            dump($vv);
        }
        fclose($gamescsv);
        $manager->flush();
    }
}

