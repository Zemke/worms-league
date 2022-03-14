<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use App\Form\LoginFormType;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $form = $this->createForm(LoginFormType::class, new User());
        $error = $authenticationUtils->getLastAuthenticationError();
        dump($error);
        return $this->renderForm('login/index.html.twig', [
            'controller_name' => 'LoginController',
            'form' => $form,
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $error,
        ]);
    }
}
