<?php

namespace App\Controller\Admin;

use App\Entity\Frame;
use App\Entity\Game;
use App\Entity\League;
use App\Entity\Roll;
use App\Entity\Team;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        parent::index();
        return $this->render('admin/dashboard.html.twig');
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
        yield MenuItem::linkToCrud('Framy', 'fa fa-list', Frame::class);
        yield MenuItem::linkToCrud('Rzuty', 'fa fa-bowling-ball', Roll::class);

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
