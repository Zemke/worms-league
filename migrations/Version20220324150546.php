<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220324150546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ranking ADD season_id INT NOT NULL');
        $this->addSql('ALTER TABLE ranking ADD CONSTRAINT FK_80B839D04EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_80B839D04EC001D1 ON ranking (season_id)');
        $this->addSql('DROP INDEX season_active_uidx');
        $this->addSql('CREATE UNIQUE INDEX season_active_uidx ON season (active) WHERE active = true');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX season_active_uidx');
        $this->addSql('CREATE UNIQUE INDEX season_active_uidx ON season (active) WHERE (active = true)');
        $this->addSql('ALTER TABLE ranking DROP CONSTRAINT FK_80B839D04EC001D1');
        $this->addSql('DROP INDEX IDX_80B839D04EC001D1');
        $this->addSql('ALTER TABLE ranking DROP season_id');
    }
}
