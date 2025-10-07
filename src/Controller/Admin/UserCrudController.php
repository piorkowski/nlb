<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnIndex()->hideOnForm(),
            TextField::new('firstName'),
            TextField::new('lastName'),
            TextField::new('email'),
            TextField::new('username'),
            BooleanField::new('isVerified'),
            TextField::new('password')->hideOnIndex()->hideOnForm(),
            ChoiceField::new('roles')->setChoices([
                'ROLE_ADMIN' => 'Admin',
                'ROLE_USER' => 'Player',
            ])->allowMultipleChoices(),
            AssociationField::new('teams')->autocomplete(),
            AssociationField::new('leagues')->autocomplete(),
        ];
    }
}
