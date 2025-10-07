<?php

namespace App\Controller\Admin;

use App\Entity\League;
use App\Entity\Team;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

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
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield TextField::new('name', 'Nazwa')
            ->setRequired(true);

        yield TextareaField::new('description', 'Opis')
            ->hideOnIndex();

        yield DateTimeField::new('startDate', 'Data rozpoczęcia')
            ->setFormat('dd.MM.yyyy');

        yield DateTimeField::new('endDate', 'Data zakończenia')
            ->setFormat('dd.MM.yyyy')
            ->hideOnIndex();

        yield BooleanField::new('isActive', 'Aktywna')
            ->renderAsSwitch(true);

//        if ($pageName === Crud::PAGE_DETAIL || $pageName === Crud::PAGE_INDEX) {
//            yield TextField::new('gamesCount', 'Liczba meczów')
//                ->formatValue(fn ($value) => $value?->getGames()->count() ?? 0);
//        }
    }
}
