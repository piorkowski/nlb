<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\GameRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/games')]
class GameViewController extends AbstractController
{
    public function __construct(
        private GameRepository     $gameRepository,
        private PaginatorInterface $paginator
    )
    {
    }

    #[Route('/team', name: 'admin_games_team')]
    public function teamGames(Request $request): Response
    {
        $queryBuilder = $this->gameRepository->createQueryBuilder('g')
            ->where('g.teamA IS NOT NULL')
            ->andWhere('g.teamB IS NOT NULL')
            ->orderBy('g.gameDate', 'DESC');

        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/game/list.html.twig', [
            'games' => $pagination,
            'title' => 'Mecze drużynowe',
            'type' => 'team',
        ]);
    }

    #[Route('/individual', name: 'admin_games_individual')]
    public function individualGames(Request $request): Response
    {
        $queryBuilder = $this->gameRepository->createQueryBuilder('g')
            ->where('g.teamA IS NULL')
            ->andWhere('g.teamB IS NULL')
            ->orderBy('g.gameDate', 'DESC');

        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/game/list.html.twig', [
            'games' => $pagination,
            'title' => 'Mecze indywidualne',
            'type' => 'individual',
        ]);
    }
}
