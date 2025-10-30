<?php

namespace App\Controller\Admin;

use App\Entity\League;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;

class LeagueCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return League::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Liga')
            ->setEntityLabelInPlural('Ligi')
            ->setSearchFields(['name', 'description'])
            ->setDefaultSort(['startDate' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setPageTitle('index', 'Zarządzanie ligami')
            ->setPageTitle('new', 'Dodaj nową ligę')
            ->setPageTitle('edit', 'Edytuj ligę')
            ->setPageTitle('detail', 'Szczegóły ligi');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(BooleanFilter::new('isActive', 'Aktywna'))
            ->add('type');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
            ->hideOnIndex();

        yield TextField::new('name', 'Nazwa')
            ->setColumns(8)
            ->setRequired(true)
            ->setHelp('Nazwa ligi (np. "Sezon Jesień 2024")');

        yield ChoiceField::new('type', 'Typ')
            ->setChoices([
                'Pojedyncza' => 'single',
                'Drużynowa' => 'team',
                'Mieszana' => 'mixed',
            ])
            ->setColumns(4)
            ->renderAsBadges([
                'single' => 'primary',
                'team' => 'success',
                'mixed' => 'info',
            ]);

        yield TextareaField::new('description', 'Opis')
            ->setColumns(12)
            ->hideOnIndex()
            ->setHelp('Dodatkowe informacje o lidze');

        yield DateTimeField::new('startDate', 'Data rozpoczęcia')
            ->setColumns(6)
            ->setFormat('dd.MM.yyyy')
            ->setRequired(true);

        yield DateTimeField::new('endDate', 'Data zakończenia')
            ->setColumns(6)
            ->setFormat('dd.MM.yyyy')
            ->setRequired(false)
            ->hideOnIndex();

        yield BooleanField::new('isActive', 'Aktywna')
            ->setColumns(12)
            ->renderAsSwitch(true)
            ->setHelp('Czy liga jest obecnie aktywna');

        yield AssociationField::new('teams', 'Drużyny')
            ->autocomplete()
            ->setColumns(6)
            ->hideOnIndex()
            ->setHelp('Drużyny biorące udział w lidze');

        yield AssociationField::new('players', 'Zawodnicy')
            ->autocomplete()
            ->setColumns(6)
            ->hideOnIndex()
            ->setHelp('Zawodnicy biorący udział w lidze');

        if ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            yield IntegerField::new('gamesCount', 'Mecze')
                ->formatValue(fn ($value, League $league) => $league->getGames()->count());

            yield IntegerField::new('teamsCount', 'Drużyny')
                ->formatValue(fn ($value, League $league) => $league->getTeams()->count());

            yield IntegerField::new('playersCount', 'Zawodnicy')
                ->formatValue(fn ($value, League $league) => $league->getPlayers()->count());
        }

        if ($pageName === Crud::PAGE_DETAIL) {
            yield DateTimeField::new('createdAt', 'Data utworzenia')
                ->setFormat('dd.MM.yyyy HH:mm');

            yield DateTimeField::new('updatedAt', 'Ostatnia aktualizacja')
                ->setFormat('dd.MM.yyyy HH:mm');
        }
    }
}
