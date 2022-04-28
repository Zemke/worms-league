<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Repository\GameRepository;

class CommentController extends AbstractController
{
    #[Route('/{gameId}/comments', name: 'app_match_comment', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request,
                           int $gameId,
                           Security $security,
                           GameRepository $gameRepo,
                           CommentRepository $commentRepo): RedirectResponse
    {
        $game = $gameRepo->find($gameId);
        if (is_null($game)) {
            throw $this->createNotFoundException("Game {$gameId} does not exist.");
        }
        $body = preg_replace("/\r\n/", "\n", $request->request->get('body'));
        $comment = (new Comment())
            ->setAuthor($security->getUser())
            ->setBody(trim(preg_replace("/\n\n\n/","\n\n", $body)))
            ->setGame($game);
        $commentRepo->add($comment, true);
        return $this->redirectToRoute(
            'app_match_view',
            ['gameId' => $gameId, '_fragment' => 'comments']);
    }
}
