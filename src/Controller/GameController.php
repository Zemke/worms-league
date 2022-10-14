<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Repository\UserRepository;
use App\Repository\SeasonRepository;
use App\Entity\Game;
use App\Entity\Replay;
use App\Service\RankingService;
use App\Service\StateService;
use App\Service\WaaasService;
use App\Message\SendReplayMessage;

class GameController extends AbstractController
{
    #[Route('/report', name: 'app_report', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function report(Request $request,
                           StateService $stateService,
                           UserRepository $users,
                           SeasonRepository $seasons,
                           EntityManagerInterface $em,
                           ValidatorInterface $validator,
                           MessageBusInterface $bus,
                           LoggerInterface $logger,
                           MailerInterface $mailer,): Response
    {

        $user = $this->getUser();
        $var = [ 'season' => $seasons->findOneBy(['active' => true]) ];
        $openGames = $stateService->openGames($user);
        if (count($openGames) === 1 && current($openGames)->isPlayoff()) {
            $game = current($openGames);
        }
        $var['opponents'] = array_map(fn($g) => $g->opponent($user), $stateService->openGames($user));
        if (empty($var['opponents'])) {
            $this->addFlash('info', 'There are no opponents for you.');
        }
        if (!isset($var['season'])) {
            $this->addFlash('error', 'There\'s currently no season.');
        } else if ($request->getMethod() === 'POST') {
            if (!$this->isCsrfTokenValid('report', $request->request->get('token'))) {
                return new Response('', 403);
            }
            $opp = $users->find($request->request->all()['opponent']);
            if (is_null($opp) || !in_array($opp->getId(), array_map(fn($u) => $u->getId(), $var['opponents']))) {
                $this->addFlash('error', 'Invalid opponent.');
            } else {
                if (!isset($game)) {
                    $game = (new Game())
                        ->setHome($user)
                        ->setAway($opp)
                        ->setSeason($var['season']);
                }
                $game
                    ->setReporter($user)
                    ->setReportedAt(new \DateTimeImmutable('now'));
                foreach ($request->files->all('replays') as $file) {
                    $game->addReplay((new Replay())->setFile($file));
                }
                if (count($err = $validator->validate($game)) > 0) {
                    foreach ($err as $r) {
                        $this->addFlash('error', $r->getMessage());
                    }
                } else {
                    $em->persist($game);
                    $em->flush();
                    foreach ($game->getReplays() as $replay) {
                        $bus->dispatch(new SendReplayMessage($replay->getId()));
                    }
                    try {
                        $email = (new Email())
                            ->to('florian@zemke.io')
                            ->subject('WL REP: ' . $game->getId())
                            ->text('https://wl.zemke.io/matches/' . $game->getId());
                        $mailer->send($email);
                    } catch (\Throwable $e) {
                        $logger->critical($e->getMessage(), ['exception' => $e]);
                    }
                    $this->addFlash('success', 'Your game has been reported and is being processed.');
                    return $this->redirectToRoute(
                        'app_match_view',
                        ['gameId' => $game->getId()]
                    );
                }
            }
        }
        return $this->render('game/report.html.twig', $var);
    }
}

