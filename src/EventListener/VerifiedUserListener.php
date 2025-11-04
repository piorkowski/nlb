<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: RequestEvent::class)]
class VerifiedUserListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private RouterInterface $router
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Nie blokuj tych routów
        $allowedRoutes = [
            'app_login',
            'app_logout',
            'app_register',
            'app_verify_email',
            'app_verify_resend_email',
            'app_forgot_password_request',
            'app_check_email',
            'app_reset_password',
        ];

        if (in_array($route, $allowedRoutes)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            return;
        }

        $user = $token->getUser();

        if (!$user->isVerified()) {
            $event->setResponse(
                new RedirectResponse($this->router->generate('app_verify_resend_email'))
            );
        }
    }
}
