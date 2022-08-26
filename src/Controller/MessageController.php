<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\MessageRepository;

class MessageController extends AbstractController
{
    #[Route('/message', name: 'app_message', methods: ['GET']) ]
    public function index(Request $request,
                          UserRepository $userRepo,
                          MessageRepository $messageRepo,): Response
    {
        return $this->render('message/index.html.twig', [
            'messages' => $messageRepo->findForShoutbox($this->getUser()),
            'users' => $userRepo->findAll(),
        ]);
    }

    #[Route('/message', name: 'app_message_add', methods: ['POST']) ]
    #[IsGranted('ROLE_USER')]
    public function add(Request $request,
                        MessageRepository $messageRepo,
                        UserRepository $userRepo,): Response
    {
        $data = $request->request;
        dump($data);
        dump($data->get('recipients'));
        $recipients = $userRepo->findBy(['id' => $data->get('recipients')]);
        $msg = (new Message())
            ->setAuthor($this->getUser())
            ->setBody($data->get('body'))
            ->addManyRecipients($recipients);
        $messageRepo->add($msg, true);
        return $this->redirectToRoute('app_home', ['_fragment' => 'shoutbox']);
    }
}
