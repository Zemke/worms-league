<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221014164513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game ADD reported_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('update game set reported_at=created');
        $this->addSql('COMMENT ON COLUMN game.reported_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX season_active_uidx');
        $this->addSql('CREATE UNIQUE INDEX season_active_uidx ON season (active) WHERE active = true');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX season_active_uidx');
        $this->addSql('CREATE UNIQUE INDEX season_active_uidx ON season (active) WHERE (active = true)');
        $this->addSql('ALTER TABLE game DROP reported_at');
    }
}
