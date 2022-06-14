<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\Security\Core\Security;
use App\Repository\UserRepository;

class ControllerListener
{
    public function __construct(private UserRepository $userRepo,
                                private Security $security,)
    {
    }

    public function onKernelController(ControllerEvent $event)
    {
        if (!is_null(($user = $this->security->getUser()))) {
            $this->userRepo->updateLastActive($user);
        }
    }
}

