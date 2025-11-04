<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Game;
use App\Entity\GameStatus;
use App\Service\GameGeneratorService;
use App\Service\GameNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/game')]
class GameGenerateController extends AbstractController
{
    public function __construct(
        private GameGeneratorService $gameGenerator,
        private GameNotificationService $notificationService,
        private EntityManagerInterface $em,
        private AdminUrlGenerator $adminUrlGenerator
    ) {}

    #[Route('/{gameId}/process-generate', name: 'admin_game_process_generate', methods: ['POST'])]
    public function processGenerate(int $gameId, Request $request): Response
    {
        $game = $this->em->getRepository(Game::class)->find($gameId);

        if (!$game) {
            throw $this->createNotFoundException('Game not found');
        }

        if ($game->getFrames()->count() > 0) {
            $this->addFlash('error', 'Ten mecz ma już wygenerowane framy!');
            return $this->redirect(
                $this->adminUrlGenerator
                    ->setController(GameCrudController::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($gameId)
                    ->generateUrl()
            );
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('game_generate_' . $gameId, $token)) {
            $this->addFlash('error', 'Nieprawidłowy token CSRF');
            return $this->redirect(
                $this->adminUrlGenerator
                    ->setController(GameCrudController::class)
                    ->setAction('generateGame')
                    ->setEntityId($gameId)
                    ->generateUrl()
            );
        }

        try {
            $data = [
                'gameType' => $request->request->get('gameType'),
                'individual' => $request->request->all('individual'),
                'team' => $request->request->all('team'),
            ];

            $errors = $this->gameGenerator->validateGameData($data);
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirect(
                    $this->adminUrlGenerator
                        ->setController(GameCrudController::class)
                        ->setAction('generateGame')
                        ->setEntityId($gameId)
                        ->generateUrl()
                );
            }

            $this->gameGenerator->generateFramesFromRequest($game, $data);

            $game->setStatus(GameStatus::PLANNED);
            $this->em->flush();

            $this->notificationService->notifyGameScheduled($game);

            $this->addFlash('success', 'Struktura meczu została wygenerowana! Status zmieniony na "Planowany". Gracze otrzymali powiadomienia email.');

            return $this->redirect(
                $this->adminUrlGenerator
                    ->setController(GameCrudController::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($gameId)
                    ->generateUrl()
            );

        } catch (\Exception $e) {
            $this->addFlash('error', 'Błąd podczas generowania: ' . $e->getMessage());

            return $this->redirect(
                $this->adminUrlGenerator
                    ->setController(GameCrudController::class)
                    ->setAction('generateGame')
                    ->setEntityId($gameId)
                    ->generateUrl()
            );
        }
    }
}
