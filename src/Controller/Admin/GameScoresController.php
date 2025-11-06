<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Game;
use App\Entity\GameStatus;
use App\Entity\User;
use App\Service\RollService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/game-scores')]
class GameScoresController extends AbstractController
{
    public function __construct(
        private RollService            $rollService,
        private EntityManagerInterface $em,
        private AdminUrlGenerator      $adminUrlGenerator
    )
    {
    }

    #[Route('/{id}/edit', name: 'admin_game_scores_edit')]
    public function edit(Game $game): Response
    {
        // Zmień status na IN_PROGRESS przy pierwszym otwarciu edycji wyników
        if ($game->getStatus() === GameStatus::PLANNED) {
            $game->setStatus(GameStatus::IN_PROGRESS);
            $this->em->flush();
        }

        $players = $game->getAllPlayers();
        $frames = $game->getFrames();

        $gamesByNumber = [];
        foreach ($frames as $frame) {
            $gameNumber = $frame->getGameNumber();
            $frameNumber = $frame->getFrameNumber();

            if (!isset($gamesByNumber[$gameNumber])) {
                $gamesByNumber[$gameNumber] = [];
            }

            $gamesByNumber[$gameNumber][$frameNumber] = $frame;
        }

        ksort($gamesByNumber);
        foreach ($gamesByNumber as &$framesInGame) {
            ksort($framesInGame);
        }

        return $this->render('admin/game_scores/edit.html.twig', [
            'game' => $game,
            'players' => $players,
            'gamesByNumber' => $gamesByNumber,
        ]);
    }

    #[Route('/{id}/save', name: 'admin_game_scores_save', methods: ['POST'])]
    public function save(Game $game, Request $request): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('game_scores_' . $game->getId(), $token)) {
            $this->addFlash('error', 'Nieprawidłowy token CSRF');
            return $this->redirectToRoute('admin_game_scores_edit', ['id' => $game->getId()]);
        }

        try {
            $scoresData = $request->request->all('scores');
            $savedCount = 0;

            foreach ($game->getFrames() as $frame) {
                $frameId = $frame->getId();

                if (!isset($scoresData[$frameId])) {
                    continue;
                }

                foreach ($scoresData[$frameId] as $playerId => $inputText) {
                    $player = $this->em->getRepository(User::class)->find($playerId);

                    if (!$player || empty(trim($inputText))) {
                        continue;
                    }

                    $rolls = $this->parseFrameInput($inputText, $frame->getFrameNumber());

                    if ($rolls === null) {
                        $this->addFlash('warning', "Nieprawidłowy format dla {$player->getFullName()}, frame {$frame->getFrameNumber()}: {$inputText}");
                        continue;
                    }

                    $this->savePlayerRolls($frame, $player, $rolls);
                    $savedCount++;
                }
            }
            $this->em->flush();

            if ($game->isComplete()) {
                $game->setStatus(GameStatus::FINISHED);

                $game->calculatePoints();

                $this->em->flush();
                $this->addFlash('success', "✅ Zapisano {$savedCount} wyników! Mecz został zakończony.");
            } else {
                $this->addFlash('success', "✅ Zapisano {$savedCount} wyników!");
            }

        } catch (\Exception $e) {
            $this->addFlash('error', 'Błąd: ' . $e->getMessage());
        }

        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(GameCrudController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($game->getId())
                ->generateUrl()
        );
    }

    private function parseFrameInput(string $input, int $frameNumber): ?array
    {
        $input = strtoupper(trim($input));

        if (empty($input)) {
            return null;
        }

        if ($input === 'X') {
            return ['roll1' => 10];
        }

        if ($input === '-' || $input === '0') {
            return ['roll1' => 0];
        }

        if (preg_match('/^(\d|-)\/$/', $input, $matches)) {
            $first = $matches[1] === '-' ? 0 : (int)$matches[1];
            return ['roll1' => $first, 'roll2' => 10 - $first];
        }

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

        if ($frameNumber === 10) {
            if (count($rolls) < 2 || count($rolls) > 3) {
                return null;
            }
        } else {
            if (count($rolls) > 2) {
                return null;
            }
            if (count($rolls) === 2 && isset($rolls['roll1']) && $rolls['roll1'] !== 10) {
                if ($rolls['roll1'] + $rolls['roll2'] > 10) {
                    return null;
                }
            }
        }

        return $rolls;
    }

    private function savePlayerRolls($frame, User $player, array $rolls): void
    {
        $existingRolls = $frame->getPlayerRolls($player);
        foreach ($existingRolls as $roll) {
            $this->em->remove($roll);
        }
        $this->em->flush();

        foreach ($rolls as $rollKey => $pinsKnocked) {
            $rollNum = (int)str_replace('roll', '', $rollKey);

            if ($pinsKnocked === '' || $pinsKnocked === null) {
                continue;
            }

            try {
                $this->rollService->addRoll($frame, $player, $rollNum, (int)$pinsKnocked);
            } catch (\Exception $e) {
                $this->addFlash('warning', sprintf(
                    'Błąd: %s, frame %d, rzut %d: %s',
                    $player->getFullName(),
                    $frame->getFrameNumber(),
                    $rollNum,
                    $e->getMessage()
                ));
            }
        }
    }
}
