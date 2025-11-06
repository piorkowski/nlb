<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Trait\BlamestampTrait;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityListener(event: Events::prePersist)]
#[AsEntityListener(event: Events::preUpdate)]
class BlamestampListener
{
    public function __construct(
        private Security $security
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$this->usesTrait($entity, BlamestampTrait::class)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        if (method_exists($entity, 'setCreatedBy') && $entity->getCreatedBy() === null) {
            $entity->setCreatedBy($user);
        }

        if (method_exists($entity, 'setUpdatedBy')) {
            $entity->setUpdatedBy($user);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$this->usesTrait($entity, BlamestampTrait::class)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        if (method_exists($entity, 'setUpdatedBy')) {
            $entity->setUpdatedBy($user);
        }
    }

    private function usesTrait(object $entity, string $traitName): bool
    {
        $traits = class_uses($entity);
        if ($traits === false) {
            return false;
        }

        return in_array($traitName, $traits, true);
    }
}
