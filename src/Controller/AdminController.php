<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Repository\GameRepository;
use App\Repository\SeasonRepository;

class AdminController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin', name: 'app_admin')]
    public function index(GameRepository $gameRepo,
                          SeasonRepository $seasonRepo,
                          Request $request,
                          EntityManagerInterface $em,
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
                    $gameRepo->remove($g, true);
                    $this->addFlash('success', 'Deleted game ' . $g->asText());
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
        usort($games, fn($g1, $g2) => $g2->getCreated() > $g1->getCreated() ? 1 : -1);
        return $this->render('admin/index.html.twig', ['games' => $games,]);
    }
}

