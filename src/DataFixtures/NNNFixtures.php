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
                $this->perSeason($season, $gamescsv, $users, $manager);
            } finally {
                fclose($gamescsv);
            }
        }
        $manager->flush();
    }

    public function perSeason(Season $season, mixed $gamescsv, &$users, ObjectManager &$manager): void
    {
        $head = fgetcsv($gamescsv);
        $c = 0;
        while (($row = fgetcsv($gamescsv)) !== false) {
            $vv = array_combine($head, $row);
            $game = (new Game())
                ->setSeason($season)
                ->setCreated(new \DateTime($vv['dateat']));
            $gUsers = array_map(function ($u) use (&$users, &$manager) {
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
            $trf = new TestReplayFactory();
            foreach ($trf->inst($game) as $replay) {
                $game->addReplay($replay);
            }
            $manager->persist($game);
            $c++;
            dump($c . ' ' . $game->getHome()->getUsername() . ' '
                 . $game->getScoreHome() . '–' . $game->getScoreAway() . ' '
                 . $game->getAway()->getUsername());
            dump($vv);
            if ($c % NNNFixtures::BATCH_SIZE === 0) {
                $manager->flush();
            }
        }
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

class TestReplayFactory
{
    const BASE_REPLAYS = 5;

    public function __construct()
    {
        $this->repLoc = $this->extractZip('replays.zip');
        $this->mapLoc = $this->extractZip('maps.zip');
    }

    private function extractZip(string $basename): string
    {
        $zip = new \ZipArchive();
        $res = $zip->open(dirname(__FILE__) . '/' . $basename);
        if ($res === false) {
            throw new \RuntimeException("could not open {$basename} archive");
        }
        $loc = sys_get_temp_dir() . '/php_wl_' . substr($basename, 0, -4);
        $zip->extractTo($loc);
        return $loc;
    }

    public function inst(Game $game): array
    {
        $colorHome = array_rand(ReplayData::COLORS);
        while (($colorAway = array_rand(ReplayData::COLORS)) === $colorHome) {
            continue;
        }
        if (is_null($winner = $game->winner())) {
            throw new \RuntimeException(
                'there\'s got to be a winner for this for simplicity\'s sake');
        }
        $winnerIsHome = $game->isHome($winner);
        $winning = str_shuffle(
            str_repeat('H', $game->getScoreHome() - ((int) $winnerIsHome))
            . str_repeat('A', $game->getScoreAway() - ((int) !$winnerIsHome)));
        $winning .= $winnerIsHome ? 'H' : 'A';
        $winning = str_split($winning);
        $winner = [
            'H' => ['user' => $game->getHome(), 'color' => ucfirst($colorHome)],
            'A' => ['user' => $game->getAway(), 'color' => ucfirst($colorAway)],
        ];
        $loser = ['A' => $winner['H'], 'H' => $winner['A']];
        $totalScore = $game->getScoreHome() + $game->getScoreAway();
        if ($totalScore > 5) {
            throw new \RuntimeException(
                'not supporting score > 5 as there are only five example
                replays and they get deleted once persisted');
        }
        $num = array_map(fn($i) => $i % self::BASE_REPLAYS + 1, range(0, $totalScore-1));
        shuffle($num);
        $ret = [];
        for ($i = 0; $i < $totalScore; $i++) {
            $w = $winning[$i];
            $mapLoc = $this->mapLoc . "/maps/{$num[$i]}.png";
            $repLoc = $this->repLoc . "/replays/{$num[$i]}.WAgame";
            $map = new UploadedFile($mapLoc, basename($mapLoc), null, null, true);
            $replay = new UploadedFile($repLoc, basename($repLoc), null, null, true);
            $ret[] = (new TestReplay(
                $winner[$w]['user']->getUsername(),
                $loser[$w]['user']->getUsername(),
                $winner[$w]['color'],
                $loser[$w]['color'],
                Texture::cases()[array_rand(Texture::cases())],
                $num[$i],
                $replay,
                $map))->toReplay();
        }
        return $ret;
    }
}

class TestReplay
{
    private const USERS = ['Monster`tita', 'dt-Mablak'];
    private const TEAMS = ['~CWT2021', 'Ro Bad'];
    private const ALT_TEAMS = ['Royalty', 'Venom'];
    private const COLORS = ['Blue', 'Red'];

    public function __construct(
        private string $winner,
        private string $loser,
        private string $colorWinner,
        private string $colorLoser,
        private Texture $texture,
        private int $baseReplay,
        private UploadedFile $replay,
        private UploadedFile $map)
    {
    }

    public function toReplay(): Replay
    {
        return (new Replay())
            ->setFile($this->replay)
            ->setReplayData((new ReplayData())->setData($this->modifiedData()))
            ->setReplayMap((new ReplayMap())->setFile($this->map));
    }

    private function modifiedData(): array
    {
        $json = json_decode(file_get_contents(dirname(__FILE__) . '/stats.json'));
        $raw = json_encode($json[$this->baseReplay-1]);
        [$winner, $loser] = $this->baseReplay === 2
            ? TestReplay::USERS : array_reverse(TestReplay::USERS);
        $raw = preg_replace("/{$winner}/", $this->winner, $raw);
        $raw = preg_replace("/{$loser}/", $this->loser, $raw);
        $raw = preg_replace('/"' . TestReplay::TEAMS[0] . '"/', '"Royalty"', $raw);
        $raw = preg_replace('/"' . TestReplay::TEAMS[1] . '"/', '"Venom"', $raw);
        $json = (array) json_decode($raw, true);
        [$winnerColor, $loserColor] = $this->baseReplay === 2
            ? TestReplay::COLORS : array_reverse(TestReplay::COLORS);
        for ($i = 0; $i <= 1; $i++) {
            $json['teams'][$i]['color'] = $json['teams'][$i]['color'] === $winnerColor
                ? $this->colorWinner : $this->colorLoser;
        }
        assert($json['teams'][0]['color'] !== $json['teams'][1]['color']);
        $json['texture'] = $this->texture->game();
        return $json;
    }
}

