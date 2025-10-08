<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Frame;
use App\Entity\Game;
use App\Entity\User;
use App\Service\RollService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/frame')]
class FrameScoresController extends AbstractController
{
    public function __construct(
        private RollService $rollService,
        private EntityManagerInterface $em
    ) {}

    #[Route('/{gameId}/double-match/{startLane}/save', name: 'admin_frame_save_double_match', methods: ['POST'])]
    public function saveDoubleMatch(int $gameId, int $startLane, Request $request): Response
    {
        $game = $this->em->getRepository(\App\Entity\Game::class)->find($gameId);

        if (!$game) {
            throw $this->createNotFoundException('Game not found');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('double_match_' . $gameId . '_' . $startLane, $token)) {
            $this->addFlash('error', 'Nieprawidłowy token CSRF');
            return $this->redirectToRoute('admin', [
                'crudAction' => 'viewFrames',
                'crudControllerFqcn' => \App\Controller\Admin\GameCrudController::class,
                'entityId' => $gameId,
            ]);
        }

        try {
            $framesData = $request->request->all('frames');
            $savedCount = 0;

            foreach ($framesData as $frameId => $playersData) {
                $frame = $this->em->getRepository(Frame::class)->find($frameId);

                if (!$frame) {
                    continue;
                }

                foreach ($playersData as $playerId => $rolls) {
                    $player = $this->em->getRepository(User::class)->find($playerId);

                    if (!$player) {
                        continue;
                    }

                    $this->savePlayerRolls($frame, $player, $rolls);
                    $savedCount++;
                }
            }

            $this->em->flush();
            $this->addFlash('success', "✅ Zapisano dwumecz! ({$savedCount} wyników)");

        } catch (\Exception $e) {
            $this->addFlash('error', 'Błąd: ' . $e->getMessage());
        }

        // Znajdź dowolny fram z pierwszego toru
        $anyFrame = null;
        foreach ($game->getFrames() as $f) {
            if ($f->getLaneNumber() === $startLane) {
                $anyFrame = $f;
                break;
            }
        }

        if ($anyFrame) {
            return $this->redirectToRoute('admin', [
                'crudAction' => 'enterScores',
                'crudControllerFqcn' => FrameCrudController::class,
                'entityId' => $anyFrame->getId(),
            ]);
        }

        return $this->redirectToRoute('admin', [
            'crudAction' => 'viewFrames',
            'crudControllerFqcn' => \App\Controller\Admin\GameCrudController::class,
            'entityId' => $gameId,
        ]);
    }
    #[Route('/{gameId}/save-match', name: 'admin_frame_save_all_scores_match', methods: ['POST'])]
    public function saveAllMatchScores(int $gameId, Request $request): Response
    {
        $game = $this->em->getRepository(\App\Entity\Game::class)->find($gameId);

        if (!$game) {
            throw $this->createNotFoundException('Game not found');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('all_match_scores_' . $gameId, $token)) {
            $this->addFlash('error', 'Nieprawidłowy token CSRF');
            return $this->redirectToRoute('admin', [
                'crudAction' => 'viewFrames',
                'crudControllerFqcn' => \App\Controller\Admin\GameCrudController::class,
                'entityId' => $gameId,
            ]);
        }

        try {
            $savedCount = 0;

            // Przetwórz dane z game1 i game2
            foreach (['game1', 'game2'] as $gameKey) {
                $gameData = $request->request->all($gameKey);

                if (!$gameData) {
                    continue;
                }

                foreach ($gameData as $frameId => $playersData) {
                    $currentFrame = $this->em->getRepository(Frame::class)->find($frameId);

                    if (!$currentFrame) {
                        continue;
                    }

                    foreach ($playersData as $playerId => $inputText) {
                        $player = $this->em->getRepository(User::class)->find($playerId);

                        if (!$player || empty(trim($inputText))) {
                            continue;
                        }

                        // Parsuj tekst (np. "X", "7/", "7,2") na rzuty
                        $rolls = $this->parseFrameInput($inputText, $currentFrame->getFrameNumber());

                        if ($rolls === null) {
                            $this->addFlash('warning', "Nieprawidłowy format dla gracza {$player->getFullName()}, fram {$currentFrame->getFrameNumber()}: {$inputText}");
                            continue;
                        }

                        $this->savePlayerRolls($currentFrame, $player, $rolls);
                        $savedCount++;
                    }
                }
            }

            $this->em->flush();
            $this->addFlash('success', "✅ Zapisano wyniki! ({$savedCount} wpisów)");

        } catch (\Exception $e) {
            $this->addFlash('error', 'Błąd: ' . $e->getMessage());
        }

        // Wróć do widoku framów meczu
        return $this->redirectToRoute('admin', [
            'crudAction' => 'viewFrames',
            'crudControllerFqcn' => \App\Controller\Admin\GameCrudController::class,
            'entityId' => $gameId,
        ]);
    }

    /**
     * Parsuje tekst wejściowy (X, 7/, 7,2) na tablicę rzutów [roll1 => pins, roll2 => pins, ...]
     */
    private function parseFrameInput(string $input, int $frameNumber): ?array
    {
        $input = strtoupper(trim($input));

        if (empty($input)) {
            return null;
        }

        // Strike
        if ($input === 'X') {
            return ['roll1' => 10];
        }

        // Pudło
        if ($input === '-' || $input === '0') {
            return ['roll1' => 0];
        }

        // Spare z pierwszym rzutem (np. "7/")
        if (preg_match('/^(\d|-)\/$/', $input, $matches)) {
            $first = $matches[1] === '-' ? 0 : (int)$matches[1];
            return ['roll1' => $first, 'roll2' => 10 - $first];
        }

        // Dwa lub trzy rzuty oddzielone przecinkiem lub spacją
        $parts = preg_split('/[,\s]+/', $input);
        $rolls = [];
        $rollIndex = 1;

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === 'X') {
                $rolls['roll' . $rollIndex] = 10;
                $rollIndex++;
            } elseif ($part === '-') {
                $rolls['roll' . $rollIndex] = 0;
                $rollIndex++;
            } elseif (preg_match('/^(\d|-)\/$/', $part, $matches)) {
                // Spare w środku (np. "X,5/" w 10 framie)
                $first = $matches[1] === '-' ? 0 : (int)$matches[1];
                $rolls['roll' . $rollIndex] = $first;
                $rollIndex++;
                $rolls['roll' . $rollIndex] = 10 - $first;
                $rollIndex++;
            } elseif (is_numeric($part)) {
                $pins = (int)$part;
                if ($pins >= 0 && $pins <= 10) {
                    $rolls['roll' . $rollIndex] = $pins;
                    $rollIndex++;
                }
            }
        }

        // Walidacja
        if ($frameNumber === 10) {
            // Fram 10 może mieć 2-3 rzuty
            if (count($rolls) < 2 || count($rolls) > 3) {
                return null;
            }
        } else {
            // Framy 1-9: max 2 rzuty
            if (count($rolls) > 2) {
                return null;
            }
            // Suma nie może przekroczyć 10 (chyba że strike)
            if (count($rolls) === 2 && isset($rolls['roll1']) && $rolls['roll1'] !== 10) {
                if ($rolls['roll1'] + $rolls['roll2'] > 10) {
                    return null;
                }
            }
        }

        return $rolls;
    }
    #[Route('/game/{gameId}/save-all-scores', name: 'admin_frame_save_all_scores', methods: ['POST'])]
    public function saveAllScores(int $gameId, Request $request): Response
    {
        $game = $this->em->getRepository(Game::class)->find($gameId);

        if (!$game) {
            throw $this->createNotFoundException('Game not found');
        }

        // Sprawdź CSRF token
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('game_scores_' . $gameId, $token)) {
            $this->addFlash('error', 'Nieprawidłowy token CSRF');
            return $this->redirectToRoute('admin', [
                'crudAction' => 'viewFrames',
                'crudControllerFqcn' => \App\Controller\Admin\GameCrudController::class,
                'entityId' => $gameId,
            ]);
        }

        try {
            // Struktura: frames[frameId][playerId][roll1/roll2/roll3]
            $framesData = $request->request->all('frames');
            $savedCount = 0;

            foreach ($framesData as $frameId => $playersData) {
                $frame = $this->em->getRepository(Frame::class)->find($frameId);

                if (!$frame) {
                    continue;
                }

                foreach ($playersData as $playerId => $rolls) {
                    $player = $this->em->getRepository(User::class)->find($playerId);

                    if (!$player) {
                        continue;
                    }

                    $this->savePlayerRolls($frame, $player, $rolls);
                    $savedCount++;
                }
            }

            $this->em->flush();
            $this->addFlash('success', sprintf('Zapisano wyniki dla %d graczy we wszystkich framach!', $savedCount));

        } catch (\Exception $e) {
            $this->addFlash('error', 'Błąd podczas zapisywania: ' . $e->getMessage());
        }

        // Redirect z powrotem do widoku framów
        return $this->redirectToRoute('admin', [
            'crudAction' => 'viewFrames',
            'crudControllerFqcn' => \App\Controller\Admin\GameCrudController::class,
            'entityId' => $gameId,
        ]);
    }

    private function savePlayerRolls(Frame $frame, User $player, array $rolls): void
    {
        // Usuń TYLKO rzuty tego gracza w TYM framie
        $existingRolls = $frame->getPlayerRolls($player);
        foreach ($existingRolls as $roll) {
            $this->em->remove($roll);
        }

        // Flush żeby usunąć przed dodaniem nowych
        $this->em->flush();

        // Dodaj nowe rzuty
        foreach ($rolls as $rollKey => $pinsKnocked) {
            // rollKey to 'roll1', 'roll2', 'roll3'
            $rollNum = (int) str_replace('roll', '', $rollKey);

            if ($pinsKnocked === '' || $pinsKnocked === null) {
                continue;
            }

            try {
                $this->rollService->addRoll($frame, $player, $rollNum, (int)$pinsKnocked);
            } catch (\Exception $e) {
                $this->addFlash('warning', sprintf(
                    'Błąd: %s, fram %d, rzut %d: %s',
                    $player->getFullName(),
                    $frame->getFrameNumber(),
                    $rollNum,
                    $e->getMessage()
                ));
            }
        }
    }
}
