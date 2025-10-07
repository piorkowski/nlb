<?php

namespace App\Controller\Admin;


use App\Entity\Roll;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class RollCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Roll::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Rzut')
            ->setEntityLabelInPlural('Rzuty')
            ->setSearchFields(['id', 'rollNumber', 'pinsKnocked', 'notes'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(50);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('frame', 'Fram'))
            ->add(EntityFilter::new('player', 'Gracz'))
            ->add('rollNumber')
            ->add('pinsKnocked')
            ->add('isStrike')
            ->add('isSpare');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield AssociationField::new('frame', 'Fram')
            ->autocomplete()
            ->setRequired(true);

        yield AssociationField::new('player', 'Gracz')
            ->autocomplete()
            ->setRequired(true);

        yield IntegerField::new('rollNumber', 'Numer rzutu')
            ->setHelp('1, 2 lub 3 (tylko dla 10 framu)')
            ->setRequired(true);

        yield IntegerField::new('pinsKnocked', 'Zbite kręgle')
            ->setHelp('0-10')
            ->setRequired(true);

        yield BooleanField::new('isStrike', 'Strike')
            ->hideOnForm()
            ->renderAsSwitch(false);

        yield BooleanField::new('isSpare', 'Spare')
            ->hideOnForm()
            ->renderAsSwitch(false);

        yield DateTimeField::new('createdAt', 'Utworzono')
            ->setFormat('dd.MM.yyyy HH:mm:ss')
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Zaktualizowano')
            ->setFormat('dd.MM.yyyy HH:mm:ss')
            ->hideOnForm()
            ->hideOnIndex();
    }
}
