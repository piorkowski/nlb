<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251106120112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE frame ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE frame ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE frame ADD CONSTRAINT FK_B5F83CCDEA3FA723 FOREIGN KEY (team_a_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE frame ADD CONSTRAINT FK_B5F83CCDF88A08CD FOREIGN KEY (team_b_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE frame ADD CONSTRAINT FK_B5F83CCDB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE frame ADD CONSTRAINT FK_B5F83CCD896DBBDE FOREIGN KEY (updated_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_B5F83CCDB03A8386 ON frame (created_by_id)');
        $this->addSql('CREATE INDEX IDX_B5F83CCD896DBBDE ON frame (updated_by_id)');
        $this->addSql('ALTER TABLE game ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CEA3FA723 FOREIGN KEY (team_a_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CF88A08CD FOREIGN KEY (team_b_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C896DBBDE FOREIGN KEY (updated_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_232B318CB03A8386 ON game (created_by_id)');
        $this->addSql('CREATE INDEX IDX_232B318C896DBBDE ON game (updated_by_id)');
        $this->addSql('ALTER TABLE league ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE league ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE league ADD CONSTRAINT FK_3EB4C318B03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE league ADD CONSTRAINT FK_3EB4C318896DBBDE FOREIGN KEY (updated_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3EB4C318B03A8386 ON league (created_by_id)');
        $this->addSql('CREATE INDEX IDX_3EB4C318896DBBDE ON league (updated_by_id)');
        $this->addSql('ALTER TABLE league_team ADD CONSTRAINT FK_2E3F061E296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE roll ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE roll ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE roll ADD CONSTRAINT FK_2EB532CEB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE roll ADD CONSTRAINT FK_2EB532CE896DBBDE FOREIGN KEY (updated_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_2EB532CEB03A8386 ON roll (created_by_id)');
        $this->addSql('CREATE INDEX IDX_2EB532CE896DBBDE ON roll (updated_by_id)');
        $this->addSql('ALTER TABLE team ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE team ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61FB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F896DBBDE FOREIGN KEY (updated_by_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C4E0A61FB03A8386 ON team (created_by_id)');
        $this->addSql('CREATE INDEX IDX_C4E0A61F896DBBDE ON team (updated_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE frame DROP CONSTRAINT FK_B5F83CCDEA3FA723');
        $this->addSql('ALTER TABLE frame DROP CONSTRAINT FK_B5F83CCDF88A08CD');
        $this->addSql('ALTER TABLE frame DROP CONSTRAINT FK_B5F83CCDB03A8386');
        $this->addSql('ALTER TABLE frame DROP CONSTRAINT FK_B5F83CCD896DBBDE');
        $this->addSql('DROP INDEX IDX_B5F83CCDB03A8386');
        $this->addSql('DROP INDEX IDX_B5F83CCD896DBBDE');
        $this->addSql('ALTER TABLE frame DROP created_by_id');
        $this->addSql('ALTER TABLE frame DROP updated_by_id');
        $this->addSql('ALTER TABLE team DROP CONSTRAINT FK_C4E0A61FB03A8386');
        $this->addSql('ALTER TABLE team DROP CONSTRAINT FK_C4E0A61F896DBBDE');
        $this->addSql('DROP INDEX IDX_C4E0A61FB03A8386');
        $this->addSql('DROP INDEX IDX_C4E0A61F896DBBDE');
        $this->addSql('ALTER TABLE team DROP created_by_id');
        $this->addSql('ALTER TABLE team DROP updated_by_id');
        $this->addSql('ALTER TABLE roll DROP CONSTRAINT FK_2EB532CEB03A8386');
        $this->addSql('ALTER TABLE roll DROP CONSTRAINT FK_2EB532CE896DBBDE');
        $this->addSql('DROP INDEX IDX_2EB532CEB03A8386');
        $this->addSql('DROP INDEX IDX_2EB532CE896DBBDE');
        $this->addSql('ALTER TABLE roll DROP created_by_id');
        $this->addSql('ALTER TABLE roll DROP updated_by_id');
        $this->addSql('ALTER TABLE league_team DROP CONSTRAINT FK_2E3F061E296CD8AE');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CEA3FA723');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CF88A08CD');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CB03A8386');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318C896DBBDE');
        $this->addSql('DROP INDEX IDX_232B318CB03A8386');
        $this->addSql('DROP INDEX IDX_232B318C896DBBDE');
        $this->addSql('ALTER TABLE game DROP created_by_id');
        $this->addSql('ALTER TABLE game DROP updated_by_id');
        $this->addSql('ALTER TABLE league DROP CONSTRAINT FK_3EB4C318B03A8386');
        $this->addSql('ALTER TABLE league DROP CONSTRAINT FK_3EB4C318896DBBDE');
        $this->addSql('DROP INDEX IDX_3EB4C318B03A8386');
        $this->addSql('DROP INDEX IDX_3EB4C318896DBBDE');
        $this->addSql('ALTER TABLE league DROP created_by_id');
        $this->addSql('ALTER TABLE league DROP updated_by_id');
    }
}
