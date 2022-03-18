<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220318164424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE game_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE season_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE game (id INT NOT NULL, home_id INT DEFAULT NULL, away_id INT DEFAULT NULL, reporter_id INT DEFAULT NULL, season_id INT DEFAULT NULL, score_home INT DEFAULT NULL, score_away INT DEFAULT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, modified TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, voided BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_232B318C28CDC89C ON game (home_id)');
        $this->addSql('CREATE INDEX IDX_232B318C8DEF089F ON game (away_id)');
        $this->addSql('CREATE INDEX IDX_232B318CE1CFE6F5 ON game (reporter_id)');
        $this->addSql('CREATE INDEX IDX_232B318C4EC001D1 ON game (season_id)');
        $this->addSql('CREATE TABLE season (id INT NOT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, modified TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, start TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ending TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C28CDC89C FOREIGN KEY (home_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C8DEF089F FOREIGN KEY (away_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CE1CFE6F5 FOREIGN KEY (reporter_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318C4EC001D1');
        $this->addSql('DROP SEQUENCE game_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE season_id_seq CASCADE');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE season');
    }
}
