<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MessageController extends AbstractController
{
    #[Route('/message', name: 'app_message', methods: ['POST']) ]
    public function index(Request $request): Response
    {
        dump($request);
        return $this->render('message/index.html.twig', [
            'controller_name' => 'power to manipulate',
        ]);
    }
}
