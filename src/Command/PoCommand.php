<?php

namespace App\Command;

use App\Entity\Game;
use App\Entity\User;
use App\Entity\Playoff;
use App\Entity\Replay;
use App\Entity\ReplayData;
use App\Entity\ReplayMap;
use App\Repository\SeasonRepository;
use App\Repository\UserRepository;
use App\Repository\GameRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsCommand(
    name: 'app:po',
    description: 'Add a short description for your command',
)]
class PoCommand extends Command
{

    public function __construct(
        private SeasonRepository $seasonRepo,
        private UserRepository $userRepo,
        private GameRepository $gameRepo,)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->season = $this->seasonRepo->findOneByName('ESL #1 Alt-F4');
        if (is_null($this->season)) {
            throw new \RuntimeException('no such season');
        }
        $this->zemke = $this->userRepo->findOneByUsername('Zemke');
        if (is_null($this->zemke)) {
            throw new \RuntimeException('Zemke does not exist.');
        }
        $users = [
            $this->userRepo->findOneByUsername('Dream'),
            $this->userRepo->findOneByUsername('FoxHound'),
            $this->userRepo->findOneByUsername('Mega`Adnan'),
            $this->userRepo->findOneByUsername('Ledan'),
            $this->userRepo->findOneByUsername('Kayz'),
            $this->userRepo->findOneByUsername('SIBASA'),
            $this->userRepo->findOneByUsername('djongador'),
            $this->userRepo->findOneByUsername('KRD'),
        ];
        if (in_array(null, $users, true)) {
            throw new \RuntimeException(
                'unfound user '
                . implode(', ', array_map(fn($u) => $u?->getUsername(), $users)));
        }

        // quarterfinal
        for ($i = 1; $i < count($users); $i += 2) {
            $g = $this->play(
                $users[$i-1], $users[$i],
                (new Playoff())->setStep(1)->setSpot(intval(ceil($i/2))));
            $this->gameRepo->add($g);
        }

        // semifinal
        $sf1 = $this->play(
            $users[0], $users[2],
            (new Playoff())->setStep(2)->setSpot(1));
        $this->gameRepo->add($sf1);
        $sf2 = $this->play(
            $users[4], $users[7],
            (new Playoff())->setStep(2)->setSpot(2));
        $this->gameRepo->add($sf2);

        // third place
        $thp = $this->play(
            $users[2], $users[7],
            (new Playoff())->setStep(3)->setSpot(1));
        $this->gameRepo->add($thp);

        // final
        $fin = $this->play(
            $users[0], $users[4],
            (new Playoff())->setStep(4)->setSpot(1));

        // save final and flush
        $this->gameRepo->add($fin, true);

        return Command::SUCCESS;
    }

    public function &play(User &$home, User &$away, Playoff $playoff): Game
    {
        $g = (new Game())
            ->setHome($home)
            ->setAway($away)
            ->setReporter($this->zemke)
            ->setReportedAt(new \DateTimeImmutable('now'))
            ->setSeason($this->season)
            ->setPlayoff($playoff);
        ;
        $p = "/tmp/wlpo/{$playoff->getStep()}/{$playoff->getSpot()}/";
        foreach (glob("{$p}*.WAgame") as $f) {
            $bn = basename($f, '.WAgame');
            $r = (new Replay())
                ->setFile($this->toUploadedFile($f))
                ->setReplayData((new ReplayData())
                    ->setData(json_decode(file_get_contents($p . $bn . '.json'), true)))
                ->setReplayMap((new ReplayMap())
                    ->setFile($this->toUploadedFile($p . $bn . '.png')))
            ;
            $g->addReplay($r);
        }
        $g->score();
        return $g;
    }

    public function toUploadedFile(string $s): UploadedFile
    {
        $f = fopen($s, 'r');
        $uri = stream_get_meta_data($f)['uri'];
        return new UploadedFile($uri, basename($uri), null, null, true);
    }
}
