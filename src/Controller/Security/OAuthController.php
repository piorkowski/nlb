<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OAuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TokenStorageInterface $tokenStorage,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    #[Route('/login/check-facebook', name: 'hwi_oauth_service_check_facebook')]
    public function connectCheckFacebook(Request $request): Response
    {
        return $this->handleOAuthCallback($request, 'facebook');
    }

    #[Route('/login/check-google', name: 'hwi_oauth_service_check_google')]
    public function connectCheckGoogle(Request $request): Response
    {
        return $this->handleOAuthCallback($request, 'google');
    }

    private function handleOAuthCallback(Request $request, string $service): Response
    {
        $token = $this->tokenStorage->getToken();

        if ($token instanceof OAuthToken) {
            $user = $token->getUser();

            if ($user instanceof User && !$user->isVerified()) {
                $user->setIsVerified(true);
                $this->em->flush();
            }

            $loginEvent = new InteractiveLoginEvent($request, $token);
            $this->eventDispatcher->dispatch($loginEvent);
        }

        return $this->redirectToRoute('admin');
    }
}
