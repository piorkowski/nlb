<?php

namespace App\Controller\Admin;

use App\Entity\Frame;
use App\Entity\Roll;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\HttpFoundation\Response;

class FrameCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Frame::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Fram')
            ->setEntityLabelInPlural('Framy')
            ->setSearchFields(['id', 'frameNumber', 'laneNumber', 'gameNumber'])
            ->setDefaultSort(['game' => 'DESC', 'gameNumber' => 'ASC', 'frameNumber' => 'ASC'])
            ->setPaginatorPageSize(30);
    }

    public function configureActions(Actions $actions): Actions
    {
        $enterScores = Action::new('enterScores', 'Wprowadź wyniki', 'fa fa-edit')
            ->linkToCrudAction('enterScores')
            ->setCssClass('btn btn-primary');

        $viewScores = Action::new('viewScores', 'Zobacz wyniki', 'fa fa-eye')
            ->linkToCrudAction('viewScores')
            ->setCssClass('btn btn-info');

        return $actions
            ->add(Crud::PAGE_INDEX, $enterScores)
            ->add(Crud::PAGE_DETAIL, $enterScores)
            ->add(Crud::PAGE_INDEX, $viewScores)
            ->disable(Action::NEW) // Framy są tworzone automatycznie
            ->disable(Action::DELETE); // Nie można usuwać framów osobno
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('game', 'Mecz'))
            ->add('frameNumber')
            ->add('laneNumber')
            ->add('gameNumber');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->hideOnForm();

        yield AssociationField::new('game', 'Mecz')
            ->autocomplete()
            ->setRequired(true)
            ->hideOnForm();

        yield IntegerField::new('frameNumber', 'Numer framu')
            ->setHelp('1-10');

        yield IntegerField::new('laneNumber', 'Tor');

        yield IntegerField::new('gameNumber', 'Gra')
            ->setHelp('1 lub 2 (dwumecz)');

        yield AssociationField::new('teamA', 'Drużyna A')
            ->hideOnIndex();

        yield AssociationField::new('teamB', 'Drużyna B')
            ->hideOnIndex();

        if ($pageName === Crud::PAGE_DETAIL) {
            yield CollectionField::new('teamAPlayers', 'Gracze Team A')
                ->setEntryIsComplex();

            yield CollectionField::new('teamBPlayers', 'Gracze Team B')
                ->setEntryIsComplex();

            yield TextField::new('teamAScore', 'Wynik Team A')
                ->formatValue(fn ($value, Frame $frame) => $frame->getTeamAScore());

            yield TextField::new('teamBScore', 'Wynik Team B')
                ->formatValue(fn ($value, Frame $frame) => $frame->getTeamBScore());

            yield TextField::new('complete', 'Kompletny')
                ->formatValue(fn ($value, Frame $frame) => $frame->isComplete() ? 'Tak' : 'Nie');
        }
    }

    /**
     * Akcja do wprowadzania wyników
     */
    public function enterScores(AdminContext $context): Response
    {
        $frame = $context->getEntity()->getInstance();

        return $this->render('admin/frame/enter_scores.html.twig', [
            'frame' => $frame,
        ]);
    }

    /**
     * Akcja do wyświetlania wyników
     */
    public function viewScores(AdminContext $context): Response
    {
        $frame = $context->getEntity()->getInstance();

        return $this->render('admin/frame/view_scores.html.twig', [
            'frame' => $frame,
        ]);
    }
}
