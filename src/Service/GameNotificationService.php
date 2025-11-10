<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class GameNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {}

    public function notifyGameScheduled(Game $game): void
    {
        $players = $game->getAllPlayers();

        foreach ($players as $player) {
            if (!$player->getEmail()) {
                continue;
            }

            $email = (new Email())
                ->from('no-reply@nyskaligabowlingowa.pl')
                ->to($player->getEmail())
                ->subject('Zaplanowano mecz - Nyska Liga Bowlingowa')
                ->html($this->twig->render('emails/game_scheduled.html.twig', [
                    'game' => $game,
                    'player' => $player,
                ]));

            try {
                $this->mailer->send($email);
            } catch (\Exception $e) {
                // Log error but don't break the flow
            }
        }
    }

    public function notifyGameCancelled(Game $game): void
    {
        $players = $game->getAllPlayers();

        foreach ($players as $player) {
            if (!$player->getEmail()) {
                continue;
            }

            $email = (new Email())
                ->from('no-reply@nyskaligabowlingowa.pl')
                ->to($player->getEmail())
                ->subject('Mecz anulowany - Nyska Liga Bowlingowa')
                ->html($this->twig->render('emails/game_cancelled.html.twig', [
                    'game' => $game,
                    'player' => $player,
                ]));

            try {
                $this->mailer->send($email);
            } catch (\Exception $e) {
                // Log error but don't break the flow
            }
        }
    }

    public function notifyGameDateChanged(Game $game, \DateTimeImmutable $oldDate): void
    {
        $players = $game->getAllPlayers();

        foreach ($players as $player) {
            if (!$player->getEmail()) {
                continue;
            }

            $email = (new Email())
                ->from('no-reply@nyskaligabowlingowa.pl')
                ->to($player->getEmail())
                ->subject('Zmiana terminu meczu - Nyska Liga Bowlingowa')
                ->html($this->twig->render('emails/game_date_changed.html.twig', [
                    'game' => $game,
                    'player' => $player,
                    'oldDate' => $oldDate,
                ]));

            try {
                $this->mailer->send($email);
            } catch (\Exception $e) {
                // Log error but don't break the flow
            }
        }
    }
}
