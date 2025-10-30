<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Game;
use App\Service\GameGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/game')]
class GameGenerateController extends AbstractController
{
    public function __construct(
        private GameGeneratorService $gameGenerator,
        private EntityManagerInterface $em
    ) {}

    #[Route('/{gameId}/process-generate', name: 'admin_game_process_generate', methods: ['POST'])]
    public function processGenerate(int $gameId, Request $request): Response
    {
        $game = $this->em->getRepository(Game::class)->find($gameId);

        if (!$game) {
            throw $this->createNotFoundException('Game not found');
        }

        // Sprawdź czy mecz nie ma już framów
        if ($game->getFrames()->count() > 0) {
            $this->addFlash('error', 'Ten mecz ma już wygenerowane framy!');
            return $this->redirectToRoute('admin', [
                'crudAction' => 'detail',
                'crudControllerFqcn' => GameCrudController::class,
                'entityId' => $gameId,
            ]);
        }

        // Sprawdź CSRF token
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('game_generate_' . $gameId, $token)) {
            $this->addFlash('error', 'Nieprawidłowy token CSRF');
            return $this->redirectToRoute('admin', [
                'crudAction' => 'generateGame',
                'crudControllerFqcn' => GameCrudController::class,
                'entityId' => $gameId,
            ]);
        }

        try {
            // Pobierz dane z requesta
            $data = [
                'gameType' => $request->request->get('gameType'),
                'individual' => $request->request->all('individual'),
                'team' => $request->request->all('team'),
            ];

            // Walidacja
            $errors = $this->gameGenerator->validateGameData($data);
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('admin', [
                    'crudAction' => 'generateGame',
                    'crudControllerFqcn' => GameCrudController::class,
                    'entityId' => $gameId,
                ]);
            }

            // Generuj framy używając serwisu
            $this->gameGenerator->generateFramesFromRequest($game, $data);

            $this->addFlash('success', 'Struktura meczu została wygenerowana pomyślnie!');

            return $this->redirectToRoute('admin', [
                'crudAction' => 'viewFrames',
                'crudControllerFqcn' => GameCrudController::class,
                'entityId' => $gameId,
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Błąd podczas generowania: ' . $e->getMessage());

            return $this->redirectToRoute('admin', [
                'crudAction' => 'generateGame',
                'crudControllerFqcn' => GameCrudController::class,
                'entityId' => $gameId,
            ]);
        }
    }
}
