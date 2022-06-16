<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class BugController extends AbstractController
{
    #[Route('/bug', name: 'app_bug')]
    public function index(Request $request, TransportInterface $transport,): Response
    {
        if ($request->getMethod() === 'POST') {
            $email = (new Email())
                ->to('florian@zemke.io')
                ->subject('Bug Inquiry')
                ->text($request->request->get('desc'));
            $transport->send($email);
            $this->addFlash('success', 'Wow, wild story. I will look after that.');
        }
        return $this->render('bug/index.html.twig');
    }
}
