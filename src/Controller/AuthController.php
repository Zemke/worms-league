<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use App\Form\PasswordForgottenFormType;
use App\Form\PasswordResetFormType;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\Transport\TransportInterface;

class AuthController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request,
                             UserPasswordHasherInterface $userPasswordHasher,
                             UserRepository $userRepo,
                             TransportInterface $transport,): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!is_null($userRepo->findOneByUsernameIgnoreCase($user->getUsername()))) {
                $this->addFlash('error', 'That username already exists.');
            else if (!is_null($userRepo->findOneByEmailIgnoreCase($user->getEmail()))) {
                $this->addFlash('error', 'User like this already exists';
            } else {
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                            $user, $form->get('plainPassword')->getData()));
                $userRepo->register($user);
                $targetUrl = $request->server->get('HTTP_ORIGIN')
                    . $this->generateUrl('app_activate')
                    . '?key=' . $user->getActivationKey();
                $email = (new Email())
                    ->to($user->getEmail())
                    ->subject('Account Activation')
                    ->text('Activate your account here: ' . $targetUrl);
                $transport->send($email);
                $this->addFlash('success', 'Please find the activation link in your email inbox.');
                return $this->redirectToRoute('app_home_index');
            }
        }

        return $this->render('auth/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/activate', name: 'app_activate', methods: ['GET'])]
    public function activate(Request $request, UserRepository $userRepo): Response
    {
        $key = $request->query->get('key');
        $user = $userRepo->fulfillActivationKey($key, true);
        if (is_null($user)) {
            return new Response('', 403);
        }
        $this->addFlash('success', 'Your account has been activated. You can now log in.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
    }

    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        if (!is_null($this->getUser())) {
            $this->addFlash('success', 'You are logged in.');
            return $this->redirectToRoute('app_home_index');
        }
        return $this->render('auth/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/reset', name: 'app_reset')]
    public function reset(Request $request,
                          UserPasswordHasherInterface $userPasswordHasher,
                          UserRepository $userRepo): Response
    {
        $key = $request->query->get('key');
        if (is_null($key) || is_null($user = $userRepo->findOneByActivationKey($key))) {
            return new Response('', 403);
        }
        $form = $this->createForm(PasswordResetFormType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (is_null($user)) {
                return new Response('', 403);
            }
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                        $user, $form->get('plainPassword')->getData()));
            $user->setActivationKey(null);
            $userRepo->add($user, true);
            $this->addFlash('success', 'Your password has been updated. Log in now.');
            return $this->redirectToRoute('app_login');
        }
        return $this->render('auth/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    #[Route('/forgotten', name: 'app_forgotten')]
    public function forgotten(Request $request,
                             TransportInterface $transport,
                              UserRepository $userRepo): Response
    {
        $form = $this->createForm(PasswordForgottenFormType::class, new User());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $upd = $userRepo->updateActivationKey($form->get('email')->getData());
            if (!is_null($upd)) {
                $targetUrl = $request->server->get('HTTP_ORIGIN')
                    . $this->generateUrl('app_reset')
                    . '?key=' . $upd->getActivationKey();
                $mail = (new Email())
                    ->to($upd->getEmail())
                    ->subject('Password Reset')
                    ->text('Reset your password here: ' . $targetUrl);
                $transport->send($mail);
            }
            $this->addFlash('success', 'Check your inbox for instructions.');
            return $this->redirectToRoute('app_home_index');
        }

        return $this->render('auth/forgotten.html.twig', [
            'forgottenForm' => $form->createView(),
        ]);
    }
}

