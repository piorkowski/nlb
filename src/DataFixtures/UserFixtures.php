<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Gender;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $usersData = [
            // Men
            ['firstname' => 'Andrzej', 'lastname' => 'Pajestka', 'email' => 'andrzej.pajestka@example.com', 'username' => 'andrzejp', 'gender' => Gender::MALE],
            ['firstname' => 'Sławomir', 'lastname' => 'Kawał', 'email' => 'slawomir.kawal@example.com', 'username' => 'slawomirk', 'gender' => Gender::MALE],
            ['firstname' => 'Stanisław', 'lastname' => 'Wierzbanowski', 'email' => 'stanislaw.wierzbanowski@example.com', 'username' => 'stanislaww', 'gender' => Gender::MALE],
            ['firstname' => 'Mieczysław', 'lastname' => 'Pierzchała', 'email' => 'mieczyslaw.pierzchala@example.com', 'username' => 'mieczyslawp', 'gender' => Gender::MALE],
            ['firstname' => 'Grzegorz', 'lastname' => 'Glezner', 'email' => 'grzegorz.glezner@example.com', 'username' => 'grzegorzg', 'gender' => Gender::MALE],
            ['firstname' => 'Tomasz', 'lastname' => 'Rajmus', 'email' => 'tomasz.rajmus@example.com', 'username' => 'tomaszr', 'gender' => Gender::MALE],
            ['firstname' => 'Paweł', 'lastname' => 'Worach', 'email' => 'pawel.worach@example.com', 'username' => 'pawelw', 'gender' => Gender::MALE],
            ['firstname' => 'Damian', 'lastname' => 'Krechowicz', 'email' => 'damian.krechowicz@example.com', 'username' => 'damiank', 'gender' => Gender::MALE],
            ['firstname' => 'Jan', 'lastname' => 'Malinowski', 'email' => 'jan.malinowski@example.com', 'username' => 'janm', 'gender' => Gender::MALE],
            ['firstname' => 'Sergiusz', 'lastname' => 'Muraszew', 'email' => 'sergiusz.muraszew@example.com', 'username' => 'sergiuszm', 'gender' => Gender::MALE],
            ['firstname' => 'Błażej', 'lastname' => 'Walczak', 'email' => 'blazej.walczak@example.com', 'username' => 'blazejw', 'gender' => Gender::MALE],
            ['firstname' => 'Kamil', 'lastname' => 'Zoń', 'email' => 'kamil.zon@example.com', 'username' => 'kamilz', 'gender' => Gender::MALE],
            ['firstname' => 'Marcin', 'lastname' => 'Holczyński', 'email' => 'marcin.holczynski@example.com', 'username' => 'marcinh', 'gender' => Gender::MALE],
            ['firstname' => 'Marian', 'lastname' => 'Kowalski', 'email' => 'marian.kowalski@example.com', 'username' => 'mariank', 'gender' => Gender::MALE],
            ['firstname' => 'Mariusz', 'lastname' => 'Mikulski', 'email' => 'mariusz.mikulski@example.com', 'username' => 'mariuszm', 'gender' => Gender::MALE],
            ['firstname' => 'Krzysztof', 'lastname' => 'Centner', 'email' => 'krzysztof.centner@example.com', 'username' => 'krzysztofc', 'gender' => Gender::MALE],
            ['firstname' => 'Radosław', 'lastname' => 'Nawrot', 'email' => 'radoslaw.nawrot@example.com', 'username' => 'radoslawn', 'gender' => Gender::MALE],
            ['firstname' => 'Jakub', 'lastname' => 'Bartosiewicz', 'email' => 'jakub.bartosiewicz@example.com', 'username' => 'jakubb', 'gender' => Gender::MALE],
            ['firstname' => 'Michał', 'lastname' => 'Grabiński', 'email' => 'michal.grabinski@example.com', 'username' => 'michalg', 'gender' => Gender::MALE],
            ['firstname' => 'Paweł', 'lastname' => 'Pipiro', 'email' => 'pawel.pipiro@example.com', 'username' => 'pawelp', 'gender' => Gender::MALE],
            ['firstname' => 'Sławomir', 'lastname' => 'Kadróg', 'email' => 'slawomir.kadrog@example.com', 'username' => 'slawomirk2', 'gender' => Gender::MALE],
            ['firstname' => 'Władysław', 'lastname' => 'Janocha', 'email' => 'wladyslaw.janocha@example.com', 'username' => 'wladyslawj', 'gender' => Gender::MALE],
            ['firstname' => 'Kamil', 'lastname' => 'Bałasz', 'email' => 'kamil.balasz@example.com', 'username' => 'kamilb', 'gender' => Gender::MALE],
            ['firstname' => 'Grzegorz', 'lastname' => 'Sobkowiak', 'email' => 'grzegorz.sobkowiak@example.com', 'username' => 'grzegorzs', 'gender' => Gender::MALE],
            ['firstname' => 'Zbigniew', 'lastname' => 'Włodarski', 'email' => 'zbigniew.wlodarski@example.com', 'username' => 'zbignieww', 'gender' => Gender::MALE],
            ['firstname' => 'Szczepan', 'lastname' => 'Miazga', 'email' => 'szczepan.miazga@example.com', 'username' => 'szczepanm', 'gender' => Gender::MALE],
            ['firstname' => 'Piotr', 'lastname' => 'Romanik', 'email' => 'piotr.romanik@example.com', 'username' => 'piotrr', 'gender' => Gender::MALE],
            ['firstname' => 'Dawid', 'lastname' => 'Błądziński', 'email' => 'dawid.bladzinski@example.com', 'username' => 'dawidb', 'gender' => Gender::MALE],
            ['firstname' => 'Piotr', 'lastname' => 'Wandzel', 'email' => 'piotr.wandzel@example.com', 'username' => 'piotrw', 'gender' => Gender::MALE],
            ['firstname' => 'Artur', 'lastname' => 'Wąsowicz', 'email' => 'artur.wasowicz@example.com', 'username' => 'arturw', 'gender' => Gender::MALE],
            ['firstname' => 'Tomasz', 'lastname' => 'Janik', 'email' => 'tomasz.janik@example.com', 'username' => 'tomaszj', 'gender' => Gender::MALE],
            ['firstname' => 'Krzysztof', 'lastname' => 'Martyna', 'email' => 'krzysztof.martyna@example.com', 'username' => 'krzysztofm', 'gender' => Gender::MALE],
            ['firstname' => 'Piotr', 'lastname' => 'Gromala', 'email' => 'piotr.gromala@example.com', 'username' => 'piotrg', 'gender' => Gender::MALE],
            ['firstname' => 'Leszek', 'lastname' => 'Hermann', 'email' => 'leszek.hermann@example.com', 'username' => 'leszekh', 'gender' => Gender::MALE],
            ['firstname' => 'Renisław', 'lastname' => 'Kulczycki', 'email' => 'renislaw.kulczycki@example.com', 'username' => 'renislawk', 'gender' => Gender::MALE],
            ['firstname' => 'Paweł', 'lastname' => 'Piórkowski', 'email' => 'pawel.piorkowski@example.com', 'username' => 'pawelpi', 'gender' => Gender::MALE],
            ['firstname' => 'Rafał', 'lastname' => 'Bojanowicz', 'email' => 'rafal.bojanowicz@example.com', 'username' => 'rafalb', 'gender' => Gender::MALE],
            ['firstname' => 'Dawid', 'lastname' => 'Leśniewski', 'email' => 'dawid.lesniewski@example.com', 'username' => 'dawidl', 'gender' => Gender::MALE],
            // Women
            ['firstname' => 'Bożena', 'lastname' => 'Pierzchała', 'email' => 'bozena.pierzchala@example.com', 'username' => 'bozenap', 'gender' => Gender::FEMALE],
            ['firstname' => 'Anna', 'lastname' => 'Socha', 'email' => 'anna.socha@example.com', 'username' => 'annas', 'gender' => Gender::FEMALE],
            ['firstname' => 'Urszula', 'lastname' => 'Konieczna', 'email' => 'urszula.konieczna@example.com', 'username' => 'urszulak', 'gender' => Gender::FEMALE],
            ['firstname' => 'Nicola', 'lastname' => 'Sowińska', 'email' => 'nicola.sowinska@example.com', 'username' => 'nicolas', 'gender' => Gender::FEMALE],
            ['firstname' => 'Anna', 'lastname' => 'Worach', 'email' => 'anna.worach@example.com', 'username' => 'annaw', 'gender' => Gender::FEMALE],
            ['firstname' => 'Maria', 'lastname' => 'Kowalska', 'email' => 'maria.kowalska@example.com', 'username' => 'mariak', 'gender' => Gender::FEMALE],
            ['firstname' => 'Barbara', 'lastname' => 'Czarnecka', 'email' => 'barbara.czarnecka@example.com', 'username' => 'barbarac', 'gender' => Gender::FEMALE],
            ['firstname' => 'Magdalena', 'lastname' => 'Nowak', 'email' => 'magdalena.nowak@example.com', 'username' => 'magdalenan', 'gender' => Gender::FEMALE],
            ['firstname' => 'Joanna', 'lastname' => 'Nawrot', 'email' => 'joanna.nawrot@example.com', 'username' => 'joannan', 'gender' => Gender::FEMALE],
            ['firstname' => 'Paulina', 'lastname' => 'Bartosiewicz', 'email' => 'paulina.bartosiewicz@example.com', 'username' => 'paulinab', 'gender' => Gender::FEMALE],
            ['firstname' => 'Aneta', 'lastname' => 'Wąsowicz', 'email' => 'aneta.wasowicz@example.com', 'username' => 'anetaw', 'gender' => Gender::FEMALE],
            ['firstname' => 'Agnieszka', 'lastname' => 'Lewicka', 'email' => 'agnieszka.lewicka@example.com', 'username' => 'agnieszkal', 'gender' => Gender::FEMALE],
            ['firstname' => 'Anna', 'lastname' => 'Muraszew', 'email' => 'anna.muraszew@example.com', 'username' => 'annam', 'gender' => Gender::FEMALE],
            ['firstname' => 'Paulina', 'lastname' => 'Janiczek', 'email' => 'paulina.janiczek@example.com', 'username' => 'paulinaj', 'gender' => Gender::FEMALE],
            ['firstname' => 'Aleksandra', 'lastname' => 'Pytlińska', 'email' => 'aleksandra.pytlinska@example.com', 'username' => 'aleksandrap', 'gender' => Gender::FEMALE],
            ['firstname' => 'Julia', 'lastname' => 'Jodko', 'email' => 'julia.jodko@example.com', 'username' => 'juliaj', 'gender' => Gender::FEMALE],
            ['firstname' => 'Anna', 'lastname' => 'Bojanowicz', 'email' => 'anna.bojanowicz@example.com', 'username' => 'annab', 'gender' => Gender::FEMALE],
        ];

        foreach ($usersData as $index => $userData) {
            $user = new User();
            $user->setFirstname($userData['firstname']);
            $user->setLastname($userData['lastname']);
            $user->setEmail($userData['email']);
            $user->setUsername($userData['username']);
            $user->setGender($userData['gender']);
            $user->setRoles(['ROLE_USER']);
            $user->setIsVerified(true);
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123'); // Default password
            $user->setPassword($hashedPassword);

            $manager->persist($user);
            $this->addReference('user_' . $index, $user);
        }

        $manager->flush();
    }
}
