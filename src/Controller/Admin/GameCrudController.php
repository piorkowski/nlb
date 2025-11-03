<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Game;
use App\Entity\GameStatus;
use App\Entity\User;
use App\Service\GameGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;

class GameCrudController extends AbstractCrudController
{
    public function __construct(
        private GameGeneratorService $gameGenerator,
        private EntityManagerInterface $em
    ) {}

    public static function getEntityFqcn(): string
    {
        return Game::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Mecz')
            ->setEntityLabelInPlural('Mecze')
            ->setSearchFields(['id', 'notes', 'teamA.name', 'teamB.name', 'status'])
            ->setDefaultSort(['gameDate' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setPageTitle('index', 'Zarządzanie meczami')
            ->setPageTitle('new', 'Dodaj nowy mecz')
            ->setPageTitle('edit', 'Edytuj mecz')
            ->setPageTitle('detail', 'Szczegóły meczu')
            ->overrideTemplate('crud/detail', 'admin/game/detail.html.twig');
    }

    public function configureActions(Actions $actions): Actions
    {
        $enterScores = Action::new('enterScores', 'Wpisz wyniki', 'fa fa-edit')
            ->linkToRoute('admin_game_scores_edit', fn (Game $game) => ['id' => $game->getId()])
            ->displayIf(fn (Game $game) => !$game->getFrames()->isEmpty())
            ->setCssClass('btn btn-warning');

        $generateGame = Action::new('generateGame', 'Generuj mecz', 'fa fa-magic')
            ->linkToCrudAction('generateGame')
            ->displayIf(fn (Game $game) => $game->getFrames()->isEmpty())
            ->setCssClass('btn btn-success');

        $actions = $actions
            ->add(Crud::PAGE_INDEX, $enterScores)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $enterScores)
            ->add(Crud::PAGE_DETAIL, $generateGame);

        if (!$this->isGranted('ROLE_ADMIN')) {
             $actions
                ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
        }

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnIndex()
            ->hideOnForm();

        yield DateTimeField::new('gameDate', 'Data meczu')
            ->setColumns(6)
            ->setFormat('dd.MM.yyyy HH:mm')
            ->setRequired(true)
            ->setHelp('Kiedy odbędzie się mecz');

        yield AssociationField::new('league', 'Liga')
            ->setColumns(6)
            ->setRequired(true)
            ->autocomplete()
            ->setHelp('W jakiej lidze rozgrywany jest mecz');

        yield ChoiceField::new('status', 'Status')
            ->setColumns(12)
            ->setChoices([
                'Szkic' => GameStatus::DRAFT,
                'Planowany' => GameStatus::PLANNED,
                'W trakcie' => GameStatus::IN_PROGRESS,
                'Zakończony' => GameStatus::FINISHED,
                'Anulowany' => GameStatus::CANCELLED,
            ])
            ->renderExpanded()
            ->renderAsBadges([
                GameStatus::DRAFT->value => 'secondary',
                GameStatus::PLANNED->value => 'info',
                GameStatus::IN_PROGRESS->value => 'primary',
                GameStatus::FINISHED->value => 'success',
                GameStatus::CANCELLED->value => 'danger',
            ]);

        yield AssociationField::new('teamA', 'Drużyna A')
            ->setColumns(6)
            ->autocomplete()
            ->setHelp('Zostaw puste dla gry indywidualnej');

        yield AssociationField::new('teamB', 'Drużyna B')
            ->setColumns(6)
            ->autocomplete()
            ->setHelp('Zostaw puste dla gry indywidualnej');

        yield TextareaField::new('notes', 'Notatki')
            ->setColumns(12)
            ->hideOnIndex()
            ->setHelp('Dodatkowe informacje o meczu');

        if ($pageName === Crud::PAGE_DETAIL) {
            yield TextField::new('gameType', 'Typ gry')
                ->formatValue(function ($value, Game $game) {
                    return $game->isTeamGame() ? 'Drużynowa' : 'Indywidualna';
                });

            yield IntegerField::new('framesCount', 'Liczba framów')
                ->formatValue(fn ($value, Game $game) => $game->getFrames()->count());

            yield TextField::new('teamAScoreDisplay', 'Wynik Team A')
                ->setVirtual(true)
                ->formatValue(fn ($value, Game $game) =>
                $game->isTeamGame() ? (string)$game->getTeamAScore() : 'N/A'
                );

            yield TextField::new('teamBScoreDisplay', 'Wynik Team B')
                ->setVirtual(true)
                ->formatValue(fn ($value, Game $game) =>
                $game->isTeamGame() ? (string)$game->getTeamBScore() : 'N/A'
                );

            yield TextField::new('winner', 'Zwycięzca')
                ->formatValue(fn ($value, Game $game) => $game->getWinner() ?? 'Nierozstrzygnięte');

            yield DateTimeField::new('createdAt', 'Data utworzenia')
                ->setFormat('dd.MM.yyyy HH:mm');

            yield DateTimeField::new('updatedAt', 'Ostatnia aktualizacja')
                ->setFormat('dd.MM.yyyy HH:mm');
        }
    }

    /**
     * Akcja do wyświetlania framów meczu
     */
    public function viewFrames(AdminContext $context): Response
    {
        // Pobierz ID z requesta
        $entityId = $context->getRequest()->query->get('entityId');

        if (!$entityId) {
            throw $this->createNotFoundException('Entity ID not provided');
        }

        // Pobierz encję bezpośrednio z EntityManager
        $game = $this->em->getRepository(Game::class)->find($entityId);

        if (!$game) {
            throw $this->createNotFoundException('Game not found');
        }

        // Pobierz informacje o strukturze z serwisu
        $structureInfo = $this->gameGenerator->getGameStructureInfo($game);

        return $this->render('admin/game/frames.html.twig', [
            'game' => $game,
            'frames' => $game->getFrames(),
            'structureInfo' => $structureInfo,
        ]);
    }

    /**
     * Akcja do generowania struktury meczu
     */
    public function generateGame(AdminContext $context): Response
    {
        // Pobierz ID z requesta
        $entityId = $context->getRequest()->query->get('entityId');

        if (!$entityId) {
            throw $this->createNotFoundException('Entity ID not provided');
        }

        $game = $this->em->getRepository(Game::class)->find($entityId);

        if (!$game) {
            throw $this->createNotFoundException('Game not found');
        }

        // Sprawdź czy mecz jest gotowy do generowania
        $isReady = $this->gameGenerator->isGameReady($game);

        // Pobierz wszystkich graczy z bazy
        $players = $this->em->getRepository(User::class)->findBy(['isVerified' => true], ['lastname' => 'ASC', 'firstname' => 'ASC']);

        return $this->render('admin/game/generate.html.twig', [
            'game' => $game,
            'isReady' => $isReady,
            'players' => $players,
        ]);
    }
}
