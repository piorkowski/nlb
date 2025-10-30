<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Team;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class TeamCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Team::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Drużyna')
            ->setEntityLabelInPlural('Drużyny')
            ->setSearchFields(['name', 'summary'])
            ->setDefaultSort(['name' => 'ASC'])
            ->setPaginatorPageSize(20)
            ->setPageTitle('index', 'Zarządzanie drużynami')
            ->setPageTitle('new', 'Dodaj nową drużynę')
            ->setPageTitle('edit', 'Edytuj drużynę')
            ->setPageTitle('detail', 'Szczegóły drużyny');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('leagues')
            ->add('players');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
            ->hideOnIndex();

        yield TextField::new('name', 'Nazwa')
            ->setColumns(12)
            ->setRequired(true)
            ->setHelp('Nazwa drużyny');

        yield TextareaField::new('summary', 'Opis')
            ->setColumns(12)
            ->hideOnIndex()
            ->setHelp('Krótki opis drużyny');

        yield AssociationField::new('players', 'Zawodnicy')
            ->setColumns(6)
            ->autocomplete()
            ->setHelp('Wybierz zawodników w drużynie')
            ->formatValue(function ($value, Team $team) {
                return $team->getPlayers()->count() . ' ' . ($team->getPlayers()->count() === 1 ? 'zawodnik' : 'zawodników');
            });

        yield AssociationField::new('leagues', 'Ligi')
            ->setColumns(6)
            ->autocomplete()
            ->setHelp('Ligi w których uczestniczy drużyna')
            ->formatValue(function ($value, Team $team) {
                return $team->getLeagues()->count() . ' ' . ($team->getLeagues()->count() === 1 ? 'liga' : 'lig');
            });

        if ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            yield IntegerField::new('gamesCount', 'Rozegranych meczów')
                ->formatValue(fn ($value, Team $team) => 
                    $team->getGamesAsTeamA()->count() + $team->getGamesAsTeamB()->count()
                );
        }

        if ($pageName === Crud::PAGE_DETAIL) {
            yield DateTimeField::new('createdAt', 'Data utworzenia')
                ->setFormat('dd.MM.yyyy HH:mm');
            
            yield DateTimeField::new('updatedAt', 'Ostatnia aktualizacja')
                ->setFormat('dd.MM.yyyy HH:mm');
        }
    }
}
