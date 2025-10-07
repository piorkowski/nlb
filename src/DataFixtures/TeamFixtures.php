<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Team;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TeamFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $teamsData = [
            ['name' => 'Emeryci Jasienica Dolna', 'summary' => 'Drużyna doświadczonych graczy z Jasienicy Dolnej.'],
            ['name' => 'Myszka Miki', 'summary' => 'Zespół pełen energii i kreatywności.'],
            ['name' => 'Zielone Koguty', 'summary' => 'Dynamiczna drużyna z zielonym duchem walki.'],
            ['name' => 'Dzikie Koty', 'summary' => 'Zwinni i nieustępliwi zawodnicy.'],
            ['name' => 'Foto Truck', 'summary' => 'Ekipa pasjonatów fotografii i sportu.'],
            ['name' => 'Malmur', 'summary' => 'Solidny zespół z tradycjami.'],
            ['name' => 'Bambiki', 'summary' => 'Młoda i ambitna drużyna.'],
            ['name' => 'Szach Mat', 'summary' => 'Strategiczni gracze z szachowym podejściem.'],
            ['name' => 'Piękna i Bestie', 'summary' => 'Zgrana ekipa łącząca styl i siłę.'],
            ['name' => 'Glob Plast', 'summary' => 'Drużyna z międzynarodowym zacięciem.'],
            ['name' => 'Elementriks', 'summary' => 'Zespół z iskrą innowacyjności.'],
        ];

        foreach ($teamsData as $index => $teamData) {
            $team = new Team();
            $team->setName($teamData['name']);
            $team->setSummary($teamData['summary']);

            $manager->persist($team);
            $this->addReference('team_' . $index, $team);
        }

        $manager->flush();
    }
}
