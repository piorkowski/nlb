<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

class OAuthUserProvider implements OAuthAwareUserProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function loadUserByOAuthUserResponse(UserResponseInterface $response): UserInterface
    {
        $resourceOwnerName = $response->getResourceOwner()->getName();
        $userId = $response->getUsername();
        $email = $response->getEmail();

        $user = $this->em->getRepository(User::class)->findOneBy([
            $resourceOwnerName . 'Id' => $userId
        ]);

        if (!$user) {
            $user = $this->em->getRepository(User::class)->findOneBy([
                'email' => $email
            ]);

            if ($user) {
                $setter = 'set' . ucfirst($resourceOwnerName) . 'Id';
                $user->$setter($userId);
            } else {
                $user = new User();
                $user->setEmail($email);

                $realName = $response->getRealName();
                if ($realName) {
                    $nameParts = explode(' ', $realName, 2);
                    $user->setFirstname($nameParts[0]);
                    $user->setLastname($nameParts[1] ?? '');
                }

                $setter = 'set' . ucfirst($resourceOwnerName) . 'Id';
                $user->$setter($userId);
                $user->setIsVerified(true);
                $user->setRoles(['ROLE_USER']);
            }

            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        throw new \RuntimeException('Not implemented');
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException();
        }

        return $this->em->getRepository(User::class)->find($user->getId());
    }
}
