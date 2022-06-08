<?php

namespace App\DataFixtures;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Game;
use App\Entity\User;
use App\Entity\Season;
use App\Entity\Replay;
use App\Entity\ReplayData;
use App\Entity\ReplayMap;
use App\Entity\Texture;
use App\Tests\GamesGenerator;

class NNNFixtures extends Fixture
{
    private const BATCH_SIZE = 10;

    public function __construct(private UserPasswordHasherInterface $hasher,
                                private KernelInterface $appKernel)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->remove('maps');
        $this->remove('replays');
        $seasons = [
            (new Season())
                ->setActive(true)
                ->setStart(new \DateTime('2021-07-15 00:00:00'))
                ->setEnding(new \DateTime('2021-10-18 00:00:00'))
                ->setName('NNN38'),
            (new Season())
                ->setActive(false)
                ->setStart(new \DateTime('2021-10-19 00:00:00'))
                ->setEnding(new \DateTime('2022-01-22 00:00:00'))
                ->setName('NNN39'),
            (new Season())
                ->setActive(false)
                ->setStart(new \DateTime('2022-01-22 00:00:00'))
                ->setEnding(new \DateTime('2022-04-30 00:00:00'))
                ->setName('NNN40'),
            (new Season())
                ->setActive(false)
                ->setStart(new \DateTime('2022-04-30 00:00:00'))
                ->setEnding(new \DateTime('2022-07-30 00:00:00'))
                ->setName('NNN41Current'),
        ];
        $users = [];
        foreach ($seasons as $season) {
            $manager->persist($season);
            dump('-------------- season ' . $season->getName());
            $gamescsv = fopen(dirname(__FILE__) . '/csv/games_' . strtolower($season->getName()) . '.csv', 'r');
            try {
                $c = 0;
                $games = GamesGenerator::fromCsv($season, $gamescsv, $users, true,
                    function ($o) use (&$c, &$manager) {
                        if ($o instanceof User) {
                            $o->setPassword(
                                $this->hasher->hashPassword($o, 'adminadminadmin'));
                        } else if ($o instanceof Game) {
                            dump($o->getHome()->getUsername() . ' '
                                 . $o->getScoreHome() . '–' . $o->getScoreAway() . ' '
                                 . $o->getAway()->getUsername());
                        }
                        $manager->persist($o);
                        $c++;
                        if ($c % self::BATCH_SIZE === 0) {
                            $manager->flush();
                        }
                    });
            } finally {
                fclose($gamescsv);
            }
        }
        $manager->flush();
    }

    private function remove(string $dir): void
    {
        $files = glob($this->appKernel->getProjectDir() . "/public/{$dir}/*");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            } else if (is_dir($file)) {
                $this->remove('replays/*');
                rmdir($file);
            }
        }
    }
}

