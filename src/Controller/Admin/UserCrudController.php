<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Zawodnik')
            ->setEntityLabelInPlural('Zawodnicy')
            ->setSearchFields(['firstname', 'lastname', 'email', 'username'])
            ->setDefaultSort(['lastname' => 'ASC'])
            ->setPaginatorPageSize(30)
            ->setPageTitle('index', 'Lista zawodników')
            ->setPageTitle('new', 'Dodaj zawodnika')
            ->setPageTitle('edit', 'Edytuj zawodnika')
            ->setPageTitle('detail', 'Szczegóły zawodnika');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isVerified', 'Zweryfikowany'))
            ->add('teams')
            ->add('leagues');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
            ->hideOnIndex();

        yield TextField::new('firstname', 'Imię')
            ->setColumns(6)
            ->setRequired(false);

        yield TextField::new('lastname', 'Nazwisko')
            ->setColumns(6)
            ->setRequired(false);

        yield EmailField::new('email', 'Email')
            ->setColumns(6)
            ->setHelp('Adres email do logowania');

        yield TextField::new('username', 'Login')
            ->setColumns(6)
            ->setRequired(false)
            ->setHelp('Alternatywny login zamiast email');

        yield BooleanField::new('isVerified', 'Zweryfikowany')
            ->renderAsSwitch(false)
            ->setHelp('Czy email został zweryfikowany');

        yield ChoiceField::new('roles', 'Role')
            ->setChoices([
                'Gracz' => 'ROLE_USER',
                'Admin' => 'ROLE_ADMIN',
            ])
            ->allowMultipleChoices()
            ->renderExpanded()
            ->setHelp('ROLE_USER jest automatycznie przypisywany')
            ->hideOnIndex();

        yield AssociationField::new('teams', 'Drużyny')
            ->autocomplete()
            ->setTemplatePath('admin/field/teams_badges.html.twig')
            ->formatValue(function ($value, User $user) {
                return $user->getTeams()->count() . ' ' . ($user->getTeams()->count() === 1 ? 'drużyna' : 'drużyn');
            });

        yield AssociationField::new('leagues', 'Ligi')
            ->autocomplete()
            ->setTemplatePath('admin/field/leagues_badges.html.twig')
            ->formatValue(function ($value, User $user) {
                return $user->getLeagues()->count() . ' ' . ($user->getLeagues()->count() === 1 ? 'liga' : 'lig');
            });

        if ($pageName === Crud::PAGE_DETAIL) {
            yield DateTimeField::new('createdAt', 'Data utworzenia')
                ->setFormat('dd.MM.yyyy HH:mm');
            
            yield DateTimeField::new('updatedAt', 'Ostatnia aktualizacja')
                ->setFormat('dd.MM.yyyy HH:mm');

            yield TextField::new('googleId', 'Google ID')
                ->setTemplatePath('admin/field/oauth_id.html.twig');

            yield TextField::new('facebookId', 'Facebook ID')
                ->setTemplatePath('admin/field/oauth_id.html.twig');
        }
    }
}
