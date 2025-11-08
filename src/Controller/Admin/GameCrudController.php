<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Game;
use App\Entity\GameStatus;
use App\Entity\User;
use App\Service\GameGeneratorService;
use App\Service\GameNotificationService;
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
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class GameCrudController extends AbstractCrudController
{
    public function __construct(
        private GameGeneratorService    $gameGenerator,
        private EntityManagerInterface  $em,
        private GameNotificationService $notificationService,
        private AdminUrlGenerator       $adminUrlGenerator
    )
    {
    }

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
            ->linkToRoute('admin_game_scores_edit', fn(Game $game) => ['id' => $game->getId()])
            ->displayIf(fn(Game $game) => !$game->getFrames()->isEmpty() && $game->getStatus() !== GameStatus::FINISHED && $game->getStatus() !== GameStatus::CANCELLED)
            ->setCssClass('btn btn-warning');

        $generateGame = Action::new('generateGame', 'Generuj mecz', 'fa fa-magic')
            ->linkToCrudAction('generateGame')
            ->displayIf(fn(Game $game) => $game->getFrames()->isEmpty() && $game->getStatus() === GameStatus::DRAFT)
            ->setCssClass('btn btn-success');

        $finishGame = Action::new('finishGame', 'Zakończ mecz', 'fa fa-check-circle')
            ->linkToCrudAction('finishGame')
            ->displayIf(fn(Game $game) => $game->getStatus() === GameStatus::IN_PROGRESS && $game->isComplete())
            ->setCssClass('btn btn-primary');

        $cancelGame = Action::new('cancelGame', 'Anuluj mecz', 'fa fa-ban')
            ->linkToCrudAction('cancelGame')
            ->displayIf(fn(Game $game) => $game->getStatus() !== GameStatus::FINISHED && $game->getStatus() !== GameStatus::CANCELLED)
            ->setCssClass('btn btn-danger');

        $actions = $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $enterScores);

        if ($this->isGranted('ROLE_ADMIN')) {
            $actions
                ->add(Crud::PAGE_INDEX, $enterScores)
                ->add(Crud::PAGE_INDEX, $generateGame)
                ->add(Crud::PAGE_INDEX, $cancelGame)
                ->add(Crud::PAGE_DETAIL, $generateGame)
                ->add(Crud::PAGE_DETAIL, $finishGame)
                ->add(Crud::PAGE_DETAIL, $cancelGame);
        } else {
            $actions->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
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
            ])
            ->setHelp('Status ustawiany automatycznie przez system')
            ->hideOnForm()
            ->formatValue(function ($value, Game $game) use ($pageName) {
                if ($pageName === Crud::PAGE_INDEX) {
                    $statusLabels = [
                        GameStatus::DRAFT->value => 'Szkic',
                        GameStatus::PLANNED->value => 'Planowany',
                        GameStatus::IN_PROGRESS->value => 'W trakcie',
                        GameStatus::FINISHED->value => 'Zakończony',
                        GameStatus::CANCELLED->value => 'Anulowany',
                    ];
                    return $statusLabels[$game->getStatus()->value] ?? $game->getStatus()->value;
                }
                return $value;
            });

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

        if ($pageName === Crud::PAGE_INDEX) {
            yield TextField::new('pointsDisplay', 'Punkty')
                ->setVirtual(true)
                ->formatValue(function ($value, Game $game) {
                    if ($game->getStatus() !== GameStatus::FINISHED || $game->getTeamAPoints() === null) {
                        return '<span class="text-muted">-</span>';
                    }

                    return sprintf(
                        '<span class="badge bg-success">%d</span> : <span class="badge bg-success">%d</span>',
                        $game->getTeamAPoints(),
                        $game->getTeamBPoints()
                    );
                });
        }

        if ($pageName === Crud::PAGE_DETAIL) {
            yield TextField::new('statusDisplay', 'Status')
                ->setVirtual(true)
                ->formatValue(function ($value, Game $game) {
                    $badges = [
                        'DRAFT' => '<span class="badge badge-secondary">Szkic</span>',
                        'PLANNED' => '<span class="badge badge-info">Planowany</span>',
                        'IN_PROGRESS' => '<span class="badge badge-primary">W trakcie</span>',
                        'FINISHED' => '<span class="badge badge-success">Zakończony</span>',
                        'CANCELLED' => '<span class="badge badge-danger">Anulowany</span>',
                    ];
                    return $badges[$game->getStatus()->value] ?? $game->getStatus()->value;
                });

            yield TextField::new('gameTypeDisplay', 'Typ gry')
                ->setVirtual(true)
                ->formatValue(function ($value, Game $game) {
                    return $game->isTeamGame() ? 'Drużynowa' : 'Indywidualna';
                });

            yield IntegerField::new('framesCountDisplay', 'Liczba framów')
                ->setVirtual(true)
                ->formatValue(fn($value, Game $game) => $game->getFrames()->count());

            yield TextField::new('winnerDisplay', 'Zwycięzca')
                ->setVirtual(true)
                ->formatValue(function ($value, Game $game) {
                    if ($game->getStatus() !== GameStatus::FINISHED) {
                        return '<span class="text-muted">Mecz w trakcie</span>';
                    }

                    $winner = $game->getWinner();
                    if (!$winner) {
                        return '<span class="badge badge-warning">Remis</span>';
                    }

                    if ($game->isTeamGame()) {
                        $teamAScore = $game->getTeamAScore();
                        $teamBScore = $game->getTeamBScore();

                        if ($teamAScore > $teamBScore) {
                            return '<strong class="text-success">' . $game->getTeamA()->getName() . '</strong> (' . $teamAScore . ' - ' . $teamBScore . ')';
                        } elseif ($teamBScore > $teamAScore) {
                            return '<strong class="text-success">' . $game->getTeamB()->getName() . '</strong> (' . $teamBScore . ' - ' . $teamAScore . ')';
                        }
                    } else {
                        $players = $game->getAllPlayers();
                        $scores = [];

                        foreach ($players as $player) {
                            $scores[$player->getId()] = [
                                'name' => $player->getFullName(),
                                'score' => $game->getPlayerTotalScore($player)
                            ];
                        }

                        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

                        if (count($scores) >= 2 && $scores[0]['score'] === $scores[1]['score']) {
                            return '<span class="badge badge-warning">Remis</span> (' . $scores[0]['score'] . ' pkt)';
                        }

                        return '<strong class="text-success">' . $scores[0]['name'] . '</strong> (' . $scores[0]['score'] . ' pkt)';
                    }

                    return $winner;
                });

            yield TextField::new('pointsDisplay', 'Punkty za mecz')
                ->setVirtual(true)
                ->formatValue(function ($value, Game $game) {
                    if ($game->getStatus() !== GameStatus::FINISHED || $game->getTeamAPoints() === null) {
                        return '<span class="text-muted">Mecz nie zakończony</span>';
                    }

                    if ($game->isTeamGame()) {
                        return sprintf(
                            '<strong>%s:</strong> <span class="badge badge-success" style="font-size: 1.2rem;">%d pkt</span> | <strong>%s:</strong> <span class="badge badge-success" style="font-size: 1.2rem;">%d pkt</span>',
                            $game->getTeamA()->getName(),
                            $game->getTeamAPoints(),
                            $game->getTeamB()->getName(),
                            $game->getTeamBPoints()
                        );
                    } else {
                        $players = $game->getAllPlayers();
                        if (count($players) === 2) {
                            return sprintf(
                                '<strong>%s:</strong> <span class="badge badge-success" style="font-size: 1.2rem;">%d pkt</span> | <strong>%s:</strong> <span class="badge badge-success" style="font-size: 1.2rem;">%d pkt</span>',
                                $players[0]->getFullName(),
                                $game->getPlayerPoints($players[0]),
                                $players[1]->getFullName(),
                                $game->getPlayerPoints($players[1])
                            );
                        }
                    }

                    return '<span class="text-muted">-</span>';
                });
        }
    }


    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Game) {
            $entityInstance->setStatus(GameStatus::DRAFT);
        }
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function generateGame(AdminContext $context): Response
    {
        $entityId = $context->getRequest()->query->get('entityId');

        if (!$entityId) {
            throw $this->createNotFoundException('Entity ID not provided');
        }

        $game = $this->em->getRepository(Game::class)->find($entityId);

        if (!$game) {
            throw $this->createNotFoundException('Game not found');
        }

        $isReady = $this->gameGenerator->isGameReady($game);

        $playersQuery = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.isVerified = :verified')
            ->setParameter('verified', true)
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC');

        $players = $playersQuery->getQuery()->getResult();

        return $this->render('admin/game/generate.html.twig', [
            'game' => $game,
            'isReady' => $isReady,
            'players' => $players,
        ]);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Game) {
            $unitOfWork = $entityManager->getUnitOfWork();
            $unitOfWork->computeChangeSets();
            $changeSet = $unitOfWork->getEntityChangeSet($entityInstance);

            if (isset($changeSet['gameDate'])) {
                $oldDate = $changeSet['gameDate'][0];
                $newDate = $changeSet['gameDate'][1];

                if ($oldDate != $newDate &&
                    ($entityInstance->getStatus() === GameStatus::PLANNED ||
                        $entityInstance->getStatus() === GameStatus::IN_PROGRESS)) {
                    parent::updateEntity($entityManager, $entityInstance);

                    $this->notificationService->notifyGameDateChanged($entityInstance, $oldDate);

                    $this->addFlash('success', 'Mecz zaktualizowany. Gracze otrzymali powiadomienie o zmianie terminu.');
                    return;
                }
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function finishGame(AdminContext $context): Response
    {
        $entityId = $context->getRequest()->query->get('entityId');

        if (!$entityId) {
            throw $this->createNotFoundException('Entity ID not provided');
        }

        $game = $this->em->getRepository(Game::class)->find($entityId);

        if (!$game) {
            throw $this->createNotFoundException('Game not found');
        }

        if ($game->getStatus() !== GameStatus::IN_PROGRESS) {
            $this->addFlash('error', 'Można zakończyć tylko mecz w trakcie');
            return $this->redirect(
                $this->adminUrlGenerator
                    ->setController(self::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($entityId)
                    ->generateUrl()
            );
        }

        if (!$game->isComplete()) {
            $this->addFlash('error', 'Nie można zakończyć meczu - nie wszystkie wyniki są wpisane');
            return $this->redirect(
                $this->adminUrlGenerator
                    ->setController(self::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($entityId)
                    ->generateUrl()
            );
        }

        $game->setStatus(GameStatus::FINISHED);
        $game->calculatePoints();
        $this->em->flush();

        $this->addFlash('success', 'Mecz został zakończony. Punkty: ' .
            ($game->isTeamGame()
                ? $game->getTeamA()->getName() . ' (' . $game->getTeamAPoints() . ') vs ' . $game->getTeamB()->getName() . ' (' . $game->getTeamBPoints() . ')'
                : $game->getTeamAPoints() . ' - ' . $game->getTeamBPoints()
            )
        );

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($entityId)
                ->generateUrl()
        );
    }

    public function cancelGame(AdminContext $context): Response
    {
        $entityId = $context->getRequest()->query->get('entityId');

        if (!$entityId) {
            throw $this->createNotFoundException('Entity ID not provided');
        }

        $game = $this->em->getRepository(Game::class)->find($entityId);

        if (!$game) {
            throw $this->createNotFoundException('Game not found');
        }

        if ($game->getStatus() === GameStatus::FINISHED || $game->getStatus() === GameStatus::CANCELLED) {
            $this->addFlash('error', 'Nie można anulować zakończonego lub już anulowanego meczu');
            return $this->redirect(
                $this->adminUrlGenerator
                    ->setController(self::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($entityId)
                    ->generateUrl()
            );
        }

        $game->setStatus(GameStatus::CANCELLED);
        $this->em->flush();

        $this->notificationService->notifyGameCancelled($game);

        $this->addFlash('success', 'Mecz został anulowany. Gracze otrzymali powiadomienia email.');

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($entityId)
                ->generateUrl()
        );
    }
}
