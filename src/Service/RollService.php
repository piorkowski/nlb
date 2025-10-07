<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Frame;
use App\Entity\Roll;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class RollService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Dodaje pojedynczy rzut do framu
     */
    public function addRoll(
        Frame $frame,
        User $player,
        int $rollNumber,
        int $pinsKnocked,
        ?string $notes = null
    ): Roll {
        // Walidacja
        $this->validateRoll($frame, $player, $rollNumber, $pinsKnocked);

        $roll = new Roll();
        $roll->setFrame($frame);
        $roll->setPlayer($player);
        $roll->setRollNumber($rollNumber);
        $roll->setPinsKnocked($pinsKnocked);

        $frame->addRoll($roll);

        $this->em->persist($roll);

        return $roll;
    }

    /**
     * Aktualizuje istniejący rzut
     */
    public function updateRoll(Roll $roll, int $newPinsKnocked, ?string $notes = null): Roll
    {
        // Walidacja po zmianie
        $this->validateRoll(
            $roll->getFrame(),
            $roll->getPlayer(),
            $roll->getRollNumber(),
            $newPinsKnocked
        );

        $roll->setPinsKnocked($newPinsKnocked);

        if ($notes !== null) {
            $roll->setNotes($notes);
        }

        $this->em->flush();

        return $roll;
    }

    /**
     * Usuwa rzut
     */
    public function deleteRoll(Roll $roll): void
    {
        $frame = $roll->getFrame();

        $frame->removeRoll($roll);
        $this->em->remove($roll);
        $this->em->flush();
    }

    /**
     * Usuwa wszystkie rzuty gracza w danym framie
     */
    public function deletePlayerRollsInFrame(Frame $frame, User $player): void
    {
        $rolls = $frame->getPlayerRolls($player);

        foreach ($rolls as $roll) {
            $frame->removeRoll($roll);
            $this->em->remove($roll);
        }

        $this->em->flush();
    }

    /**
     * Szybkie dodanie wszystkich rzutów dla gracza w framie
     *
     * @param Frame $frame
     * @param User $player
     * @param array $pins - tablica z liczbą kręgli, np. [10] dla strike, [7, 3] dla spare
     */
    public function addPlayerFrameRolls(Frame $frame, User $player, array $pins): void
    {
        foreach ($pins as $index => $pinsKnocked) {
            if ($pinsKnocked === null || $pinsKnocked === '') {
                continue; // Pomiń puste wartości
            }

            $this->addRoll($frame, $player, $index + 1, (int) $pinsKnocked);
        }

        $this->em->flush();
    }

    /**
     * Dodanie rzutów dla wszystkich graczy w framie
     *
     * @param Frame $frame
     * @param array $rollsData - struktura: ['player_id' => [pins...], ...]
     * Przykład: [
     *   123 => [10],      // gracz 123 zrobił strike
     *   456 => [7, 3],    // gracz 456 zrobił spare
     * ]
     */
    public function addFrameRolls(Frame $frame, array $rollsData): void
    {
        foreach ($rollsData as $playerId => $pins) {
            $player = $this->em->getRepository(User::class)->find($playerId);
            if (!$player) {
                continue;
            }

            $this->addPlayerFrameRolls($frame, $player, $pins);
        }
    }

    /**
     * Zamienia rzuty gracza w framie (usuwa stare i dodaje nowe)
     */
    public function replacePlayerRolls(Frame $frame, User $player, array $pins): void
    {
        // Usuń istniejące rzuty
        $this->deletePlayerRollsInFrame($frame, $player);

        // Dodaj nowe
        $this->addPlayerFrameRolls($frame, $player, $pins);
    }

    /**
     * Pobiera rzuty gracza jako tablicę [rollNumber => pinsKnocked]
     */
    public function getPlayerRollsArray(Frame $frame, User $player): array
    {
        $rolls = $frame->getPlayerRolls($player);
        $result = [];

        foreach ($rolls as $roll) {
            $result[$roll->getRollNumber()] = $roll->getPinsKnocked();
        }

        ksort($result);
        return $result;
    }

    /**
     * Sprawdza czy gracz ukończył fram (ma wszystkie wymagane rzuty)
     */
    public function isPlayerFrameComplete(Frame $frame, User $player): bool
    {
        $rollsCount = $frame->getPlayerRolls($player)->count();

        // Frame 10 może mieć 2-3 rzuty
        if ($frame->getFrameNumber() === 10) {
            if ($rollsCount < 2) {
                return false;
            }
            // Jeśli był strike lub spare, musi być 3 rzut
            if (($frame->isPlayerStrike($player) || $frame->isPlayerSpare($player)) && $rollsCount < 3) {
                return false;
            }
            return true;
        }

        // Framy 1-9
        if ($frame->isPlayerStrike($player)) {
            return $rollsCount >= 1;
        }

        return $rollsCount >= 2;
    }

    /**
     * Kopiuje rzuty z jednego framu do drugiego dla danego gracza
     * Przydatne przy kopiowaniu wyników
     */
    public function copyPlayerRolls(Frame $sourceFrame, Frame $targetFrame, User $player): void
    {
        $sourceRolls = $this->getPlayerRollsArray($sourceFrame, $player);
        $this->replacePlayerRolls($targetFrame, $player, $sourceRolls);
    }

    /**
     * Waliduje poprawność rzutu
     */
    private function validateRoll(Frame $frame, User $player, int $rollNumber, int $pinsKnocked): void
    {
        // Sprawdź czy pinsKnocked jest w zakresie 0-10
        if ($pinsKnocked < 0 || $pinsKnocked > 10) {
            throw new \InvalidArgumentException(
                sprintf('Liczba zbitych kręgli musi być między 0 a 10, otrzymano: %d', $pinsKnocked)
            );
        }

        // Sprawdź czy gracz należy do tego framu
        $isTeamA = $frame->getTeamAPlayers()->contains($player);
        $isTeamB = $frame->getTeamBPlayers()->contains($player);

        if (!$isTeamA && !$isTeamB) {
            throw new \InvalidArgumentException(
                sprintf('Gracz %s nie należy do tego framu', $player->getFullName())
            );
        }

        // Sprawdź numer rzutu
        if ($rollNumber < 1 || $rollNumber > 3) {
            throw new \InvalidArgumentException(
                sprintf('Numer rzutu musi być między 1 a 3, otrzymano: %d', $rollNumber)
            );
        }

        // Sprawdź czy to właściwa kolejność rzutów dla tego gracza
        $existingRolls = $frame->getPlayerRolls($player);

        // Sprawdź czy nie ma już takiego rzutu
        foreach ($existingRolls as $existingRoll) {
            if ($existingRoll->getRollNumber() === $rollNumber) {
                throw new \InvalidArgumentException(
                    sprintf('Rzut numer %d już istnieje dla tego gracza', $rollNumber)
                );
            }
        }

        // Dla rzutu 2 lub 3 sprawdź czy są poprzednie rzuty
        if ($rollNumber > 1) {
            $hasPreviousRoll = false;
            foreach ($existingRolls as $existingRoll) {
                if ($existingRoll->getRollNumber() === $rollNumber - 1) {
                    $hasPreviousRoll = true;
                    break;
                }
            }

            if (!$hasPreviousRoll) {
                throw new \InvalidArgumentException(
                    sprintf('Musisz najpierw dodać rzut numer %d', $rollNumber - 1)
                );
            }
        }

        // Sprawdź czy liczba kręgli jest poprawna względem poprzedniego rzutu
        if ($rollNumber === 2 && $frame->getFrameNumber() !== 10) {
            $firstRoll = $frame->getPlayerRoll($player, 1);

            if ($firstRoll) {
                // Jeśli pierwszy rzut był strike'iem, drugi rzut nie powinien istnieć (oprócz 10 framu)
                if ($firstRoll->getPinsKnocked() === 10) {
                    throw new \InvalidArgumentException(
                        'Po strike\'u nie powinno być drugiego rzutu (chyba że to fram 10)'
                    );
                }

                // Suma nie może przekroczyć 10
                $totalPins = $firstRoll->getPinsKnocked() + $pinsKnocked;
                if ($totalPins > 10) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Suma rzutów nie może przekroczyć 10. Pierwszy rzut: %d, ten rzut: %d, suma: %d',
                            $firstRoll->getPinsKnocked(),
                            $pinsKnocked,
                            $totalPins
                        )
                    );
                }
            }
        }

        // Sprawdź czy 3 rzut jest dozwolony (tylko 10 fram ze strike/spare)
        if ($rollNumber === 3) {
            if ($frame->getFrameNumber() !== 10) {
                throw new \InvalidArgumentException('Trzeci rzut jest dozwolony tylko w 10 framie');
            }

            $hasStrikeOrSpare = $frame->isPlayerStrike($player) || $frame->isPlayerSpare($player);

            if (!$hasStrikeOrSpare) {
                throw new \InvalidArgumentException(
                    'Trzeci rzut wymaga strike\'a lub spare\'a w 10 framie'
                );
            }
        }

        // Specjalna walidacja dla 10 framu
        if ($frame->getFrameNumber() === 10 && $rollNumber === 3) {
            $roll1 = $frame->getPlayerRoll($player, 1);
            $roll2 = $frame->getPlayerRoll($player, 2);

            // Jeśli pierwszy rzut był strike'iem
            if ($roll1 && $roll1->getPinsKnocked() === 10) {
                // Drugi rzut może być też strike
                if ($roll2 && $roll2->getPinsKnocked() === 10) {
                    // Trzeci rzut może być dowolny (0-10)
                    return;
                }

                // Jeśli drugi nie był strike'iem, sprawdź sumę 2 i 3 rzutu
                if ($roll2 && ($roll2->getPinsKnocked() + $pinsKnocked > 10)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'W 10 framie po strike\'u suma rzutów 2 i 3 nie może przekroczyć 10 (chyba że rzut 2 też był strike\'iem). Rzut 2: %d, rzut 3: %d',
                            $roll2->getPinsKnocked(),
                            $pinsKnocked
                        )
                    );
                }
            }
        }
    }

    /**
     * Generuje czytelny opis rzutu (X, /, -, lub liczba)
     */
    public function getRollDisplay(Roll $roll): string
    {
        if ($roll->isStrike()) {
            return 'X';
        }

        if ($roll->isSpare()) {
            return '/';
        }

        if ($roll->getPinsKnocked() === 0) {
            return '-';
        }

        return (string) $roll->getPinsKnocked();
    }

    /**
     * Zwraca statystyki rzutów dla gracza w całej grze
     */
    public function getPlayerGameStats(User $player, \App\Entity\Game $game): array
    {
        $frames = $game->getFrames();

        $totalStrikes = 0;
        $totalSpares = 0;
        $totalPins = 0;
        $rollsCount = 0;
        $perfectFrames = 0;
        $gutterBalls = 0;

        foreach ($frames as $frame) {
            if (!$frame->getTeamAPlayers()->contains($player) &&
                !$frame->getTeamBPlayers()->contains($player)) {
                continue;
            }

            $rolls = $frame->getPlayerRolls($player);

            foreach ($rolls as $roll) {
                $totalPins += $roll->getPinsKnocked();
                $rollsCount++;

                if ($roll->getPinsKnocked() === 0) {
                    $gutterBalls++;
                }
            }

            if ($frame->isPlayerStrike($player)) {
                $totalStrikes++;
                $perfectFrames++;
            } elseif ($frame->isPlayerSpare($player)) {
                $totalSpares++;
            }
        }

        return [
            'strikes' => $totalStrikes,
            'spares' => $totalSpares,
            'totalPins' => $totalPins,
            'rollsCount' => $rollsCount,
            'averagePinsPerRoll' => $rollsCount > 0 ? round($totalPins / $rollsCount, 2) : 0,
            'perfectFrames' => $perfectFrames,
            'gutterBalls' => $gutterBalls,
            'strikePercentage' => $rollsCount > 0 ? round(($totalStrikes / $rollsCount) * 100, 2) : 0,
        ];
    }
}
