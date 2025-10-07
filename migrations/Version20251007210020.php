<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251007210020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE frame (id SERIAL NOT NULL, game_id INT NOT NULL, team_a_id INT DEFAULT NULL, team_b_id INT DEFAULT NULL, frame_number SMALLINT DEFAULT NULL, lane_number SMALLINT DEFAULT NULL, game_number SMALLINT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B5F83CCDE48FD905 ON frame (game_id)');
        $this->addSql('CREATE INDEX IDX_B5F83CCDEA3FA723 ON frame (team_a_id)');
        $this->addSql('CREATE INDEX IDX_B5F83CCDF88A08CD ON frame (team_b_id)');
        $this->addSql('COMMENT ON COLUMN frame.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN frame.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE frame_team_a_players (frame_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(frame_id, user_id))');
        $this->addSql('CREATE INDEX IDX_A4C837CA3FA3C347 ON frame_team_a_players (frame_id)');
        $this->addSql('CREATE INDEX IDX_A4C837CAA76ED395 ON frame_team_a_players (user_id)');
        $this->addSql('CREATE TABLE frame_team_b_players (frame_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(frame_id, user_id))');
        $this->addSql('CREATE INDEX IDX_9D450B0F3FA3C347 ON frame_team_b_players (frame_id)');
        $this->addSql('CREATE INDEX IDX_9D450B0FA76ED395 ON frame_team_b_players (user_id)');
        $this->addSql('CREATE TABLE game (id SERIAL NOT NULL, league_id INT DEFAULT NULL, team_a_id INT DEFAULT NULL, team_b_id INT DEFAULT NULL, game_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(20) NOT NULL, notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_232B318C58AFC4DE ON game (league_id)');
        $this->addSql('CREATE INDEX IDX_232B318CEA3FA723 ON game (team_a_id)');
        $this->addSql('CREATE INDEX IDX_232B318CF88A08CD ON game (team_b_id)');
        $this->addSql('COMMENT ON COLUMN game.game_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN game.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN game.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE league (id SERIAL NOT NULL, name VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN league.start_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN league.end_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN league.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN league.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE league_team (league_id INT NOT NULL, team_id INT NOT NULL, PRIMARY KEY(league_id, team_id))');
        $this->addSql('CREATE INDEX IDX_2E3F061E58AFC4DE ON league_team (league_id)');
        $this->addSql('CREATE INDEX IDX_2E3F061E296CD8AE ON league_team (team_id)');
        $this->addSql('CREATE TABLE league_user (league_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(league_id, user_id))');
        $this->addSql('CREATE INDEX IDX_674C764858AFC4DE ON league_user (league_id)');
        $this->addSql('CREATE INDEX IDX_674C7648A76ED395 ON league_user (user_id)');
        $this->addSql('CREATE TABLE roll (id SERIAL NOT NULL, frame_id INT NOT NULL, player_id INT NOT NULL, roll_number SMALLINT NOT NULL, pins_knocked SMALLINT NOT NULL, is_strike BOOLEAN NOT NULL, is_spare BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2EB532CE3FA3C347 ON roll (frame_id)');
        $this->addSql('CREATE INDEX IDX_2EB532CE99E6F5DF ON roll (player_id)');
        $this->addSql('COMMENT ON COLUMN roll.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN roll.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE team (id SERIAL NOT NULL, name VARCHAR(255) DEFAULT NULL, summary VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN team.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN team.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE team_user (team_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(team_id, user_id))');
        $this->addSql('CREATE INDEX IDX_5C722232296CD8AE ON team_user (team_id)');
        $this->addSql('CREATE INDEX IDX_5C722232A76ED395 ON team_user (user_id)');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, firstname VARCHAR(180) DEFAULT NULL, lastname VARCHAR(180) DEFAULT NULL, username VARCHAR(60) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, google_id VARCHAR(191) DEFAULT NULL, facebook_id VARCHAR(191) DEFAULT NULL, avatar_url VARCHAR(255) DEFAULT NULL, roles JSON DEFAULT \'[]\' NOT NULL, password VARCHAR(255) DEFAULT NULL, is_verified BOOLEAN NOT NULL, gender VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON "user" (google_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6499BE8FD98 ON "user" (facebook_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON "user" (username)');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE frame ADD CONSTRAINT FK_B5F83CCDE48FD905 FOREIGN KEY (game_id) REFERENCES game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE frame ADD CONSTRAINT FK_B5F83CCDEA3FA723 FOREIGN KEY (team_a_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE frame ADD CONSTRAINT FK_B5F83CCDF88A08CD FOREIGN KEY (team_b_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE frame_team_a_players ADD CONSTRAINT FK_A4C837CA3FA3C347 FOREIGN KEY (frame_id) REFERENCES frame (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE frame_team_a_players ADD CONSTRAINT FK_A4C837CAA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE frame_team_b_players ADD CONSTRAINT FK_9D450B0F3FA3C347 FOREIGN KEY (frame_id) REFERENCES frame (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE frame_team_b_players ADD CONSTRAINT FK_9D450B0FA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C58AFC4DE FOREIGN KEY (league_id) REFERENCES league (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CEA3FA723 FOREIGN KEY (team_a_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CF88A08CD FOREIGN KEY (team_b_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE league_team ADD CONSTRAINT FK_2E3F061E58AFC4DE FOREIGN KEY (league_id) REFERENCES league (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE league_team ADD CONSTRAINT FK_2E3F061E296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE league_user ADD CONSTRAINT FK_674C764858AFC4DE FOREIGN KEY (league_id) REFERENCES league (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE league_user ADD CONSTRAINT FK_674C7648A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE roll ADD CONSTRAINT FK_2EB532CE3FA3C347 FOREIGN KEY (frame_id) REFERENCES frame (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE roll ADD CONSTRAINT FK_2EB532CE99E6F5DF FOREIGN KEY (player_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_user ADD CONSTRAINT FK_5C722232296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_user ADD CONSTRAINT FK_5C722232A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE frame DROP CONSTRAINT FK_B5F83CCDE48FD905');
        $this->addSql('ALTER TABLE frame DROP CONSTRAINT FK_B5F83CCDEA3FA723');
        $this->addSql('ALTER TABLE frame DROP CONSTRAINT FK_B5F83CCDF88A08CD');
        $this->addSql('ALTER TABLE frame_team_a_players DROP CONSTRAINT FK_A4C837CA3FA3C347');
        $this->addSql('ALTER TABLE frame_team_a_players DROP CONSTRAINT FK_A4C837CAA76ED395');
        $this->addSql('ALTER TABLE frame_team_b_players DROP CONSTRAINT FK_9D450B0F3FA3C347');
        $this->addSql('ALTER TABLE frame_team_b_players DROP CONSTRAINT FK_9D450B0FA76ED395');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318C58AFC4DE');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CEA3FA723');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CF88A08CD');
        $this->addSql('ALTER TABLE league_team DROP CONSTRAINT FK_2E3F061E58AFC4DE');
        $this->addSql('ALTER TABLE league_team DROP CONSTRAINT FK_2E3F061E296CD8AE');
        $this->addSql('ALTER TABLE league_user DROP CONSTRAINT FK_674C764858AFC4DE');
        $this->addSql('ALTER TABLE league_user DROP CONSTRAINT FK_674C7648A76ED395');
        $this->addSql('ALTER TABLE roll DROP CONSTRAINT FK_2EB532CE3FA3C347');
        $this->addSql('ALTER TABLE roll DROP CONSTRAINT FK_2EB532CE99E6F5DF');
        $this->addSql('ALTER TABLE team_user DROP CONSTRAINT FK_5C722232296CD8AE');
        $this->addSql('ALTER TABLE team_user DROP CONSTRAINT FK_5C722232A76ED395');
        $this->addSql('DROP TABLE frame');
        $this->addSql('DROP TABLE frame_team_a_players');
        $this->addSql('DROP TABLE frame_team_b_players');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE league');
        $this->addSql('DROP TABLE league_team');
        $this->addSql('DROP TABLE league_user');
        $this->addSql('DROP TABLE roll');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_user');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
