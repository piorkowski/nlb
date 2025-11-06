<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\GameRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/games')]
class GameViewController extends AbstractController
{
    public function __construct(
        private GameRepository $gameRepository,
        private AdminUrlGenerator $adminUrlGenerator
    ) {}

    #[Route('/team', name: 'admin_games_team')]
    public function teamGames(): Response
    {
        $games = $this->gameRepository->createQueryBuilder('g')
            ->where('g.teamA IS NOT NULL')
            ->andWhere('g.teamB IS NOT NULL')
            ->orderBy('g.gameDate', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/game/list.html.twig', [
            'games' => $games,
            'title' => 'Mecze drużynowe',
            'type' => 'team',
        ]);
    }

    #[Route('/individual', name: 'admin_games_individual')]
    public function individualGames(): Response
    {
        $games = $this->gameRepository->createQueryBuilder('g')
            ->where('g.teamA IS NULL')
            ->andWhere('g.teamB IS NULL')
            ->orderBy('g.gameDate', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/game/list.html.twig', [
            'games' => $games,
            'title' => 'Mecze indywidualne',
            'type' => 'individual',
        ]);
    }
}
