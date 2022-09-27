<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220927161733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE playoff_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE playoff (id INT NOT NULL, step INT NOT NULL, spot INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE game ADD playoff_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CA2B8211C FOREIGN KEY (playoff_id) REFERENCES playoff (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232B318CA2B8211C ON game (playoff_id)');
        $this->addSql('ALTER TABLE replay_data DROP data_bak');
        $this->addSql('DROP INDEX season_active_uidx');
        $this->addSql('CREATE UNIQUE INDEX season_active_uidx ON season (active) WHERE active = true');
        $this->addSql('ALTER TABLE messenger_messages ALTER queue_name TYPE VARCHAR(190)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CA2B8211C');
        $this->addSql('DROP SEQUENCE playoff_id_seq CASCADE');
        $this->addSql('DROP TABLE playoff');
        $this->addSql('ALTER TABLE replay_data ADD data_bak JSON DEFAULT NULL');
        $this->addSql('DROP INDEX season_active_uidx');
        $this->addSql('CREATE UNIQUE INDEX season_active_uidx ON season (active) WHERE (active = true)');
        $this->addSql('ALTER TABLE messenger_messages ALTER queue_name TYPE VARCHAR(255)');
        $this->addSql('DROP INDEX UNIQ_232B318CA2B8211C');
        $this->addSql('ALTER TABLE game DROP playoff_id');
    }
}
