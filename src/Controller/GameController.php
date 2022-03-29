<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use App\Repository\UserRepository;
use App\Repository\SeasonRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\Game;
use App\Entity\Replay;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\RankingService;
use App\Service\WaaasService;

class GameController extends AbstractController
{
    #[Route('/game', name: 'app_game')]
    public function index(): Response
    {
        return $this->render('game/index.html.twig', [
            'controller_name' => 'GameController',
        ]);
    }

    #[Route('/report', name: 'app_report', methods: ['GET', 'POST'])]
    public function report(Request $request,
                           UserRepository $users,
                           SeasonRepository $seasons,
                           EntityManagerInterface $em,
                           Security $security,
                           ValidatorInterface $validator,
                           RankingService $rankingService,
                           WaaasService $waaasService): Response
    {

        $var = [ 'controller_name' => 'GameController', ];
        $season = $seasons->findOneBy(['active' => true]);
        if (!isset($season)) {
            $this->addFlash('error', 'There\'s currently no season.');
        } else if ($request->getMethod() === 'POST') {
            $game = (new Game())
                ->setReporter($security->getUser())
                ->setHome($security->getUser())
                ->setAway($users->find($request->request->all()['opponent']))
                ->setSeason($season);
            foreach ($request->files->all('replays') as $file) {
                $game->addReplay((new Replay())->setFile($file));
            }
            if (count($validator->validate($game)) > 0) {
                $this->addFlash('error', 'The game is invalid.');
            } else {
                $em->persist($game);
                // TODO in another thread per replay process all replays and the do ranking calc
                $waaasService->send($game->getReplays()[0]);
            }
        } else {
            $var['opponents'] = $em->createQueryBuilder()
                ->select('u')
                ->from('App:User', 'u')
                ->where('u.id <> :authUserId')
                ->orderBy('u.username', 'ASC')
                ->getQuery()
                ->setParameter('authUserId', $security->getUser()->getId())
                ->getResult();
            if (empty($var['opponents'])) {
                $this->addFlash('info', 'There are no opponents.');
            }
        }
        return $this->render('game/report.html.twig', $var);
    }
}
