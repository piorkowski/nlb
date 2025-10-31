<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\GameStatus;
use App\Entity\User;
use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/player')]
class PlayerViewController extends AbstractController
{
    public function __construct(
        private GameRepository $gameRepository
    ) {}

    #[Route('/my-games', name: 'admin_player_my_games')]
    public function myGames(): Response
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $upcomingGames = $this->gameRepository->createQueryBuilder('g')
            ->join('g.frames', 'f')
            ->leftJoin('f.teamAPlayers', 'tap')
            ->leftJoin('f.teamBPlayers', 'tbp')
            ->where('tap.id = :userId OR tbp.id = :userId')
            ->andWhere('g.status IN (:statuses)')
            ->setParameter('userId', $user->getId())
            ->setParameter('statuses', [GameStatus::PLANNED, GameStatus::IN_PROGRESS])
            ->orderBy('g.gameDate', 'ASC')
            ->getQuery()
            ->getResult();

        $finishedGames = $this->gameRepository->createQueryBuilder('g')
            ->join('g.frames', 'f')
            ->leftJoin('f.teamAPlayers', 'tap')
            ->leftJoin('f.teamBPlayers', 'tbp')
            ->where('tap.id = :userId OR tbp.id = :userId')
            ->andWhere('g.status = :status')
            ->setParameter('userId', $user->getId())
            ->setParameter('status', GameStatus::FINISHED)
            ->orderBy('g.gameDate', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        return $this->render('admin/player/my_games.html.twig', [
            'upcomingGames' => $upcomingGames,
            'finishedGames' => $finishedGames,
            'user' => $user,
        ]);
    }

    #[Route('/my-profile', name: 'admin_player_my_profile')]
    public function myProfile(): Response
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('admin/player/my_profile.html.twig', [
            'user' => $user,
        ]);
    }
}
