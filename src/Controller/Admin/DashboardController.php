<?php

namespace App\Controller\Admin;

use App\Entity\Frame;
use App\Entity\Game;
use App\Entity\GameStatus;
use App\Entity\League;
use App\Entity\Roll;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $em,
        private GameRepository $gameRepository
    ) {}

    public function index(): Response
    {
        parent::index();

        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $upcomingGames = $this->gameRepository->createQueryBuilder('g')
            ->where('g.status IN (:statuses)')
            ->setParameter('statuses', [GameStatus::PLANNED, GameStatus::IN_PROGRESS])
            ->orderBy('g.gameDate', 'ASC')
            ->setMaxResults(5);

        $finishedGames = $this->gameRepository->createQueryBuilder('g')
            ->where('g.status = :status')
            ->setParameter('status', GameStatus::FINISHED)
            ->orderBy('g.gameDate', 'DESC')
            ->setMaxResults(5);

        if (!$isAdmin && $user instanceof User) {
            $upcomingGames
                ->join('g.frames', 'f')
                ->leftJoin('f.teamAPlayers', 'tap')
                ->leftJoin('f.teamBPlayers', 'tbp')
                ->andWhere('tap.id = :userId OR tbp.id = :userId')
                ->setParameter('userId', $user->getId());

            $finishedGames
                ->join('g.frames', 'f2')
                ->leftJoin('f2.teamAPlayers', 'tap2')
                ->leftJoin('f2.teamBPlayers', 'tbp2')
                ->andWhere('tap2.id = :userId OR tbp2.id = :userId')
                ->setParameter('userId', $user->getId());
        }

        $playerInfo = null;
        if (!$isAdmin && $user instanceof User) {
            $playerInfo = [
                'leagues' => $user->getLeagues(),
                'teams' => $user->getTeams(),
            ];
        }

        return $this->render('admin/dashboard.html.twig', [
            'upcomingGames' => $upcomingGames->getQuery()->getResult(),
            'finishedGames' => $finishedGames->getQuery()->getResult(),
            'isAdmin' => $isAdmin,
            'playerInfo' => $playerInfo,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Nyska Liga Bowlingowa')
            ->setFaviconPath('favicon.ico')
            ->setTranslationDomain('admin')
            ->setLocales(['pl']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Mecze');
        yield MenuItem::linkToCrud('Mecze', 'fa fa-gamepad', Game::class);

        yield MenuItem::section('Ligi i drużyny');
        yield MenuItem::linkToCrud('Ligi', 'fa fa-trophy', League::class);
        yield MenuItem::linkToCrud('Drużyny', 'fa fa-users', Team::class);

        yield MenuItem::section('Użytkownicy');
        yield MenuItem::linkToCrud('Zawodnicy', 'fa fa-user', User::class);

        yield MenuItem::section('Statystyki');
        yield MenuItem::linkToRoute('Ranking', 'fa fa-chart-bar', 'admin_stats_ranking');
        yield MenuItem::linkToRoute('Statystyki graczy', 'fa fa-chart-line', 'admin_stats_players');
    }
}
