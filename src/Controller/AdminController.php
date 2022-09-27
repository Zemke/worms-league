<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Vich\UploaderBundle\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Entity\ConfigName;
use App\Entity\Config;
use App\Entity\Game;
use App\Entity\Season;
use App\Repository\ConfigRepository;
use App\Repository\GameRepository;
use App\Repository\SeasonRepository;
use App\Repository\UserRepository;
use App\Service\RankingService;

class AdminController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin', name: 'app_admin')]
    public function index(GameRepository $gameRepo,
                          SeasonRepository $seasonRepo,
                          ConfigRepository $configRepo,
                          Request $request,
                          StorageInterface $storage,
                          EntityManagerInterface $em,
                          LoggerInterface $logger,
                          ValidatorInterface $validator,): Response
    {
        if ($request->getMethod() === 'POST') {
            [
                'game' => $gameId,
                'scoreHome' => $scoreHome,
                'scoreAway' => $scoreAway
            ] = $request->request->all();
            $scoreHomeNone = is_null($scoreHome) || trim($scoreHome) === '';
            $scoreAwayNone = is_null($scoreAway) || trim($scoreAway) === '';
            if ($request->request->get('delete') === 'on') {
                if (!$scoreHomeNone || !$scoreAwayNone) {
                    $this->addFlash('error', 'Leave score blank to delete game.');
                } else {
                    $g = $gameRepo->find($gameId);
                    $txt = $g->asText();
                    try {
                        $pp = array_map(
                            fn($rep) => $storage->resolvePath($rep, 'file'),
                            $g->getReplays()->getValues());
                    } catch(\Throwable $e) {
                        $this->logger->critical('Replay could not be deleted', ['e' => $e]);
                        $this->addFlash('warn', 'The replays could not be deleted');
                    }
                    $gameRepo->remove($g, true);
                    if (isset($pp)) {
                        try {
                            foreach ($pp as $p) {
                                \unlink($p);
                            }
                            if (!empty($pp)) {
                                rmdir(dirname($pp[0]));
                            }
                        } catch(\Throwable $e) {
                            $this->logger->critical('Replay could not be deleted', ['e' => $e]);
                            $this->addFlash('warn', 'The replays could not be deleted');
                        }
                    }
                    $this->addFlash('success', 'Deleted game ' . $txt);
                }
            } else {
                $g = $gameRepo->find($gameId);
                if ($scoreHomeNone || $scoreAwayNone) {
                    $this->addFlash('error', 'Invalid score.');
                } else {
                    $g->setScoreHome($scoreHome);
                    $g->setScoreAway($scoreAway);
                    $errEff = false;
                    if (count($err = $validator->validate($g)) > 0) {
                        foreach ($err as $r) {
                            if ($r->getPropertyPath() === 'enoughReplays') continue;
                            $errEff = true;
                            $this->addFlash('error', $r->getMessage());
                        }
                    }
                    if ($errEff) {
                        $em->refresh($g);
                    } else {
                        $gameRepo->add($g, true);
                        $this->addFlash('success', 'Updated to ' . $g->asText());
                    }
                }
            }
        }
        $games = $gameRepo->findBySeason($seasonRepo->findActive());
        $seasons = $seasonRepo->findAll();
        $text = $configRepo->find(ConfigName::TEXT->toId());
        usort($games, fn($g1, $g2) => $g2->getCreated() > $g1->getCreated() ? 1 : -1);
        $hasActive = false;
        foreach ($seasons as $s) {
            if ($s->getActive()) {
                $hasActive = true;
                break;
            }
        }
        return $this->render('admin/index.html.twig', [
            'games' => $games,
            'text' => $text?->getValue(),
            'seasons' => $seasons,
            'hasActive' => $hasActive,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin_trigger', name: 'app_admin_trigger', methods: ['POST'])]
    public function text(SeasonRepository $seasonRepo, RankingService $rankingService): Response
    {
        $season = $seasonRepo->findActive();
        if (is_null($season)) {
            $this->addFlash('error', 'There is no active season.');
        } else {
            $rankingService->reCalc($season);
            $this->addFlash('success', 'Calculation has run successfully.');
        }
        return $this->redirectToRoute('app_admin');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin_text', name: 'app_admin_text', methods: ['POST'])]
    public function trigger(Request $request,
                            ConfigRepository $configRepo): Response
    {
        $id = ConfigName::TEXT->toId();
        $c = $configRepo->find($id);
        if (empty(trim($request->request->get('text')))) {
            $configRepo->remove($c, true);
            $this->addFlash('success', 'Successfully removed text.');
        } else {
            if (is_null($c)) {
                $c = (new Config)
                    ->setAuthor($this->getUser())
                    ->setName($id)
                    ->setValue($request->request->get('text'));
            } else {
                $c
                    ->setValue($request->request->get('text'))
                    ->setAuthor($this->getUser());
            }
            $configRepo->add($c, true);
            $this->addFlash('success', 'Successfully added text.');
        }
        return $this->redirectToRoute('app_admin');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin_active', name: 'app_admin_active', methods: ['POST'])]
    public function active(SeasonRepository $seasonRepo,
                           Request $request): Response
    {
        $sel = $request->request->get('active');
        if (empty($sel)) {
            $actSeason = $seasonRepo->findActive();
            if (!is_null($actSeason)) {
                $seasonRepo->add($actSeason->setActive(false), true);
            }
            $this->addFlash('success', 'No season is active currently');
        } else {
            $ss = $seasonRepo->findAll();
            foreach ($ss as &$s) {
                if ($s->getActive()) {
                    $s->setActive(false);
                    $seasonRepo->add($s);
                }
            }
            $selSeason = $seasonRepo->find($sel);
            $selSeason->setActive(true);
            $seasonRepo->add($selSeason, true);
            $this->addFlash('success', "Successfully set active season: {$selSeason->getName()}.");
        }
        return $this->redirectToRoute('app_admin');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin_new_season', name: 'app_admin_new_season', methods: ['POST'])]
    public function newSeason(SeasonRepository $seasonRepo, Request $request): Response
    {
        $season = $seasonRepo->findActive();
        $d = $request->request->all();
        $s = (new Season())

            ->setName($d['name'])
            ->setActive(false)
            ->setStart(new \DateTime($d['start']))
            ->setEnding(new \DateTime($d['ending']));
        $seasonRepo->add($s, true);
        $this->addFlash('success', 'Season has been created');
        return $this->redirectToRoute('app_admin');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin_edit_season', name: 'app_admin_edit_season', methods: ['POST'])]
    public function editSeason(SeasonRepository $seasonRepo, Request $request): Response
    {
        $d = $request->request->all();
        $s = $seasonRepo->find($d['id'])
            ->setName($d['name'])
            ->setStart(new \DateTime($d['start']))
            ->setEnding(new \DateTime($d['ending']));
        $seasonRepo->add($s, true);
        $this->addFlash('success', 'Saved season');
        return $this->redirectToRoute('app_admin');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/playoffs', name: 'app_admin_playoffs', methods: ['GET', 'POST'])]
    public function playoffs(SeasonRepository $seasonRepo,
                             UserRepository $userRepo,
                             Request $request): Response
    {
        if ($request->getMethod() === 'POST') {
            $i = 1;
            $gg = [];
            $payload = $request->request->all();
            while (array_key_exists("game{$i}_home", $payload)
                   && array_key_exists("game{$i}_away", $payload)) {
                $gg[] = $payload["game{$i}_home"];
                $gg[] = $payload["game{$i}_away"];
                $i++;
            }
            if (count(array_unique($gg)) !== count($gg)) {
                $this->addFlash('error', 'You have assigned user(s) twice.');
                return $this->redirect($request->getUri());
            } else {
                $games = array_reduce($userRepo->findBy(['id' => $gg]), function ($acc, $u) {
                    $g = end($acc);
                    if ($g !== false && is_null($g->getAway())) {
                        $g->setAway($u);
                    } else {
                        $acc[] = (new Game())->setHome($u);
                    }
                    return $acc;
                }, []);
                dd($games);
                // TODO persist playoff games and set season status
                $this->addFlash('success', 'Playoffs created successfully.');
                return $this->redirectToRoute('app_playoffs');
            }
        } else {
            $rankings = $seasonRepo->findActive()->getRankings()->getValues();
            usort($rankings, fn($a, $b) => $a->ranking()->comp($b->ranking()));
            $in = array_map(fn($u) => intval($u), $request->query->all('users'));
            $final = !empty($in);
            $place = 1;
            $users = array_reduce($rankings, function ($acc, $r) use (&$place, $in, $final) {
                if (!$final || in_array($r->getOwner()->getId(), $in)) {
                    $acc[] = [
                        'username' => $r->getOwner()->getUsername(),
                        'place' => $place,
                        'id' => $r->getOwner()->getId(),
                    ];
                }
                $place++;
                return $acc;
            }, []);
            if ($final && count($users) % 2 !== 0) {
                $this->addFlash('error', 'Please select an even number of players.');
                $final = false;
                return $this->redirectToRoute('app_admin_playoffs');
            }
        }
        return $this->render('admin/playoffs.html.twig', ['users' => $users, 'final' => $final]);
    }
}

