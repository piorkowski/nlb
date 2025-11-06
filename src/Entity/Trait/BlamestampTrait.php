<?php
declare(strict_types=1);

namespace App\Entity\Trait;

use App\Entity\User;

use Doctrine\ORM\Mapping as ORM;

trait BlamestampTrait
{
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $createdBy;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $updatedBy;

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    #[ORM\PrePersist]
    public function setCreatedBy(User $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getUpdatedBy(): User
    {
        return $this->updatedBy;
    }

    #[ORM\PreUpdate]
    public function setUpdatedBy(User $updatedBy): void
    {
        $this->updatedBy = $updatedBy;
    }
}
