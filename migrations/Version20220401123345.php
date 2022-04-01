<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220401123345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE replay_map_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE replay_map (id INT NOT NULL, name VARCHAR(255) NOT NULL, size INT NOT NULL, mime_type VARCHAR(255) DEFAULT NULL, original_name VARCHAR(255) NOT NULL, created TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, modified TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE replay ADD replay_map_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE replay ADD CONSTRAINT FK_D937F4F29F495A9D FOREIGN KEY (replay_map_id) REFERENCES replay_map (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D937F4F29F495A9D ON replay (replay_map_id)');
        $this->addSql('DROP INDEX season_active_uidx');
        $this->addSql('CREATE UNIQUE INDEX season_active_uidx ON season (active) WHERE active = true');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE replay DROP CONSTRAINT FK_D937F4F29F495A9D');
        $this->addSql('DROP SEQUENCE replay_map_id_seq CASCADE');
        $this->addSql('DROP TABLE replay_map');
        $this->addSql('DROP INDEX season_active_uidx');
        $this->addSql('CREATE UNIQUE INDEX season_active_uidx ON season (active) WHERE (active = true)');
        $this->addSql('DROP INDEX UNIQ_D937F4F29F495A9D');
        $this->addSql('ALTER TABLE replay DROP replay_map_id');
    }
}
