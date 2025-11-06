<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\League;
use App\Service\StatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/stats')]
class StatsController extends AbstractController
{
    public function __construct(
        private StatsService $statsService,
        private EntityManagerInterface $em
    ) {}

    #[Route('/ranking', name: 'admin_stats_ranking')]
    public function ranking(Request $request): Response
    {
        $leagueId = $request->query->get('league');
        $league = null;

        if ($leagueId) {
            $league = $this->em->getRepository(League::class)->find($leagueId);
        }

        $type = $request->query->get('type', 'individual');

        if ($type === 'team') {
            $ranking = $this->statsService->getTeamRanking($league);
        } else {
            $ranking = $this->statsService->getIndividualRanking($league);
        }

        return $this->render('admin/stats/ranking.html.twig', [
            'ranking' => $ranking,
            'type' => $type,
            'leagues' => $this->statsService->getAllLeagues(),
            'selectedLeague' => $league,
        ]);
    }

    #[Route('/players', name: 'admin_stats_players')]
    public function players(Request $request): Response
    {
        $leagueId = $request->query->get('league');
        $league = null;

        if ($leagueId) {
            $league = $this->em->getRepository(League::class)->find($leagueId);
        }

        $stats = $this->statsService->getPlayerStats($league);

        return $this->render('admin/stats/players.html.twig', [
            'stats' => $stats,
            'leagues' => $this->statsService->getAllLeagues(),
            'selectedLeague' => $league,
        ]);
    }
}
