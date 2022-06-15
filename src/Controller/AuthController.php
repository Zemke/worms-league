<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use App\Form\PasswordForgottenFormType;
use App\Repository\UserRepository;

class AuthController extends AbstractController
{
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
    }

    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('auth/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }


    #[Route('/forgotten', name: 'app_forgotten')]
    public function forgotten(Request $request,
                              UserRepository $userRepo): Response
    {
        $user = new User();
        $form = $this->createForm(PasswordForgottenFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $upd = $userRepo->updateActivationKey($form->get('email')->getData());
            if (!is_null($upd)) {
                $resetUrl = 'http://todo.com';
                (new Email())
                    ->from('noreply@zemke.io')
                    ->to($upd->getEmail())
                    ->subject('Password Reset')
                    ->text('Reset your password here: ' . $resetUrl);
                $this->addFlash('success', 'Thanks, an email with password reset instructions has been sent');
            }
        }

        return $this->render('auth/forgotten.html.twig', [
            'forgottenForm' => $form->createView(),
        ]);
    }
}

