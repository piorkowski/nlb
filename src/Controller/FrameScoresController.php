<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Admin\FrameCrudController;
use App\Entity\Frame;
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

    #[Route('/{frameId}/save-scores', name: 'admin_frame_save_scores', methods: ['POST'])]
    public function saveScores(int $frameId, Request $request): Response
    {
        $frame = $this->em->getRepository(Frame::class)->find($frameId);

        if (!$frame) {
            throw $this->createNotFoundException('Frame not found');
        }

        // Sprawdź CSRF token
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('frame_scores_' . $frameId, $token)) {
            $this->addFlash('error', 'Nieprawidłowy token CSRF');
            return $this->redirectToRoute('admin', [
                'crudAction' => 'enterScores',
                'crudControllerFqcn' => FrameCrudController::class,
                'entityId' => $frameId,
            ]);
        }

        try {
            // Pobierz dane z formularza
            $teamAData = $request->request->all('teamA');
            $teamBData = $request->request->all('teamB');

            // Zapisz rzuty Team A
            foreach ($teamAData as $playerId => $rolls) {
                $player = $this->em->getRepository(User::class)->find($playerId);
                if (!$player) {
                    continue;
                }

                $this->savePlayerRolls($frame, $player, $rolls);
            }

            // Zapisz rzuty Team B
            foreach ($teamBData as $playerId => $rolls) {
                $player = $this->em->getRepository(User::class)->find($playerId);
                if (!$player) {
                    continue;
                }

                $this->savePlayerRolls($frame, $player, $rolls);
            }

            $this->em->flush();
            $this->addFlash('success', 'Wyniki zostały zapisane pomyślnie!');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Błąd podczas zapisywania: ' . $e->getMessage());
        }

        // Redirect z powrotem do formularza
        return $this->redirectToRoute('admin', [
            'crudAction' => 'enterScores',
            'crudControllerFqcn' => FrameCrudController::class,
            'entityId' => $frameId,
        ]);
    }

    private function savePlayerRolls(Frame $frame, User $player, array $rolls): void
    {
        // Usuń istniejące rzuty gracza w tym framie
        $existingRolls = $frame->getPlayerRolls($player);
        foreach ($existingRolls as $roll) {
            $this->em->remove($roll);
        }

        // Dodaj nowe rzuty
        foreach ($rolls as $rollNumber => $pinsKnocked) {
            // Konwertuj 'roll1' -> 1, 'roll2' -> 2, etc.
            $rollNum = (int) str_replace('roll', '', $rollNumber);

            if ($pinsKnocked === '' || $pinsKnocked === null) {
                continue; // Pomiń puste rzuty
            }

            $pinsKnocked = (int) $pinsKnocked;

            try {
                $this->rollService->addRoll($frame, $player, $rollNum, $pinsKnocked);
            } catch (\Exception $e) {
                // Loguj błąd ale kontynuuj
                $this->addFlash('warning', sprintf(
                    'Błąd dla gracza %s, rzut %d: %s',
                    $player->getFullName(),
                    $rollNum,
                    $e->getMessage()
                ));
            }
        }
    }
}
